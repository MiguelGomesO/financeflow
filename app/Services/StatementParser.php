<?php

namespace App\Services;

use App\Support\Money;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Lê extratos e faturas (Excel, PDF ou OFX) e devolve os lançamentos num formato único:
 * ['date' => 'Y-m-d', 'description' => string, 'amount' => float (negativo = saída)].
 */
class StatementParser
{
    public const EXTENSIONS = ['xlsx', 'xls', 'pdf', 'ofx', 'qfx'];

    /** Palavras que identificam cada coluna da planilha (já sem acento e em minúsculas). */
    private const COLUMNS = [
        'date' => ['data', 'date', 'dt'],
        'description' => ['descricao', 'historico', 'lancamento', 'memo', 'title', 'estabelecimento', 'detalhes', 'nome'],
        'amount' => ['valor', 'amount', 'quantia'],
        'credit' => ['credito', 'entrada'],
        'debit' => ['debito', 'saida'],
    ];

    /** Valor em reais no fim de uma linha de PDF: "1.234,56", "-50,00", "50,00 D". */
    private const MONEY = '-?\s?(?:R\$\s?)?-?\d{1,3}(?:\.\d{3})*,\d{2}(?:\s?[DC]\b)?-?';

    /**
     * @return array{rows: list<array{date: string, description: string, amount: float}>, skipped: int}
     *
     * @throws InvalidArgumentException quando o arquivo não é um extrato reconhecível
     */
    public function parseFile(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        try {
            $result = match ($extension) {
                'xlsx', 'xls' => $this->parseTable($this->spreadsheetRows($path)),
                'pdf' => $this->parsePdfText($this->pdfText($path)),
                'ofx', 'qfx' => $this->parseOfx($this->toUtf8((string) file_get_contents($path))),
                default => throw new InvalidArgumentException('Use um arquivo Excel, PDF ou OFX.'),
            };
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable) {
            throw new InvalidArgumentException('Não consegui abrir esse arquivo. Confira se ele não está protegido por senha ou corrompido.');
        }

        if ($result['rows'] === []) {
            throw new InvalidArgumentException('Não encontrei nenhum lançamento nesse arquivo.');
        }

        return $result;
    }

    /** Linhas de uma planilha: primeira aba, valores crus (datas e números como o Excel guarda). */
    private function spreadsheetRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(false);

        return $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    private function pdfText(string $path): string
    {
        return (new PdfParser)->parseFile($path)->getText();
    }

    /**
     * Lê o texto de um PDF: cada linha que começa com uma data (dd/mm ou dd/mm/aaaa)
     * e tem um valor em reais vira um lançamento.
     */
    public function parsePdfText(string $text): array
    {
        $text = $this->toUtf8($text);
        $year = $this->dominantYear($text);
        $rows = [];

        foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));

            if (! preg_match('/^(\d{2})\/(\d{2})(?:\/(\d{4}|\d{2}))?\s+(.+)$/u', $line, $m)) {
                continue;
            }

            [, $day, $month, $lineYear, $rest] = $m + [3 => ''];
            $date = $this->parseDate($lineYear !== '' ? "$day/$month/$lineYear" : "$day/$month/$year");

            // Primeiro valor da linha; em extratos o último costuma ser o saldo.
            if ($date === null || ! preg_match('/^(.*?)\s*('.self::MONEY.')(?:\s|$)/u', $rest, $parts)) {
                continue;
            }

            $amount = $this->parseAmount($parts[2]);

            if ($amount === null || $amount == 0.0 || trim($parts[1]) === '') {
                continue;
            }

            $rows[] = ['date' => $date, 'description' => $this->clean($parts[1]), 'amount' => $amount];
        }

        return ['rows' => $rows, 'skipped' => 0];
    }

    /** Ano que mais aparece em datas completas do texto (para linhas só com dia e mês). */
    private function dominantYear(string $text): int
    {
        preg_match_all('/\b\d{2}\/\d{2}\/(20\d{2})\b/', $text, $m);

        if ($m[1] === []) {
            return (int) now()->year;
        }

        $counts = array_count_values($m[1]);
        arsort($counts);

        return (int) array_key_first($counts);
    }

    private function parseOfx(string $content): array
    {
        preg_match_all('/<STMTTRN>(.*?)(?=<\/STMTTRN>|<STMTTRN>|<\/BANKTRANLIST>|<\/CCSTMTRS>|\z)/is', $content, $blocks);

        $rows = [];
        $skipped = 0;

        foreach ($blocks[1] as $block) {
            $date = $this->parseDate($this->ofxField($block, 'DTPOSTED'));
            $amount = $this->parseAmount($this->ofxField($block, 'TRNAMT'));
            $description = $this->ofxField($block, 'MEMO') ?: $this->ofxField($block, 'NAME');

            if ($date === null || $amount === null || $amount == 0.0) {
                $skipped++;

                continue;
            }

            $row = ['date' => $date, 'description' => $this->clean($description), 'amount' => $amount];

            // FITID identifica o lançamento no banco; é o que impede duplicar ao reimportar.
            if ($fitid = mb_substr($this->ofxField($block, 'FITID'), 0, 64)) {
                $row['external_id'] = $fitid;
            }

            $rows[] = $row;
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /** Valor de uma tag, tanto em OFX 1.x (sem fechamento) quanto em XML. */
    private function ofxField(string $block, string $tag): string
    {
        if (! preg_match('/<'.$tag.'>\s*([^<\r\n]*)/i', $block, $m)) {
            return '';
        }

        return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_XML1);
    }

    /** Planilha: acha a linha de cabeçalho (pode haver textos antes dela) e lê as de baixo. */
    private function parseTable(array $table): array
    {
        $map = [];
        $headerAt = null;

        foreach (array_slice($table, 0, 30, true) as $i => $cells) {
            $candidate = $this->mapColumns(array_map(fn ($cell) => $this->normalize((string) $cell), $cells));

            if (isset($candidate['date']) && (isset($candidate['amount']) || isset($candidate['credit']) || isset($candidate['debit']))) {
                $map = $candidate;
                $headerAt = $i;
                break;
            }
        }

        if ($headerAt === null) {
            throw new InvalidArgumentException('Não reconheci as colunas. A planilha precisa ter ao menos Data e Valor em uma linha de cabeçalho.');
        }

        $rows = [];
        $skipped = 0;

        foreach (array_slice($table, $headerAt + 1) as $cells) {
            if (count(array_filter($cells, fn ($cell) => $cell !== null && trim((string) $cell) !== '')) === 0) {
                continue;
            }

            $date = $this->parseDate($cells[$map['date']] ?? null);
            $amount = $this->tableAmount($cells, $map);

            if ($date === null || $amount === null || $amount == 0.0) {
                $skipped++;

                continue;
            }

            $rows[] = [
                'date' => $date,
                'description' => $this->clean((string) ($cells[$map['description'] ?? -1] ?? '')),
                'amount' => $amount,
            ];
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /** Valor da linha: coluna única (com sinal) ou colunas separadas de crédito e débito. */
    private function tableAmount(array $cells, array $map): ?float
    {
        if (isset($map['amount'])) {
            return $this->parseAmount($cells[$map['amount']] ?? null);
        }

        $credit = isset($map['credit']) ? $this->parseAmount($cells[$map['credit']] ?? null) : null;
        $debit = isset($map['debit']) ? $this->parseAmount($cells[$map['debit']] ?? null) : null;

        if ($credit !== null && $credit != 0.0) {
            return abs($credit);
        }

        return $debit !== null && $debit != 0.0 ? -abs($debit) : null;
    }

    /** @return array<string, int> */
    private function mapColumns(array $header): array
    {
        $map = [];

        foreach (self::COLUMNS as $field => $words) {
            foreach ($words as $word) {
                // Procura primeiro a coluna com o nome exato e depois a que contém a palavra.
                $index = array_search($word, $header, true);

                if ($index === false) {
                    foreach ($header as $i => $name) {
                        if ($name !== '' && ! in_array($i, $map, true) && str_contains($name, $word)) {
                            $index = $i;
                            break;
                        }
                    }
                }

                if ($index !== false && ! in_array($index, $map, true)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    /** Aceita datas do Excel (objeto ou número de série) e textos dd/mm/aaaa, dd-mm-aa, aaaa-mm-dd, aaaammdd. */
    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_int($value) || is_float($value)) {
            return $value > 20000 && $value < 80000 ? Carbon::instance(ExcelDate::excelToDateTimeObject($value))->toDateString() : null;
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $value, $m) || preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            [, $year, $month, $day] = $m;
        } elseif (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4}|\d{2})\b/', $value, $m)) {
            [, $day, $month, $year] = $m;
            $year = strlen($year) === 2 ? '20'.$year : $year;
        } else {
            return null;
        }

        return checkdate((int) $month, (int) $day, (int) $year)
            ? Carbon::create((int) $year, (int) $month, (int) $day)->toDateString()
            : null;
    }

    /**
     * Lê números do Excel e textos como "-1.234,56", "R$ 50,00", "(50,00)", "50.00", "50,00-" e "50,00 D".
     * Devolve null se não houver número.
     */
    private function parseAmount(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $value = trim((string) $value);

        if (! preg_match('/\d/', $value)) {
            return null;
        }

        $negative = str_contains($value, '-') || str_starts_with($value, '(') || preg_match('/\sD$/', $value) === 1;
        $amount = Money::parse(str_replace('-', '', $value));

        return $negative ? -$amount : $amount;
    }

    private function clean(string $description): string
    {
        $description = trim(preg_replace('/\s+/u', ' ', $description));

        return mb_substr($description !== '' ? $description : 'Sem descrição', 0, 120);
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));

        return strtr($text, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
    }

    /** Remove o BOM e converte textos em Windows-1252/ISO-8859-1 (comuns em bancos brasileiros). */
    private function toUtf8(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        return mb_check_encoding($content, 'UTF-8') ? $content : mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }
}
