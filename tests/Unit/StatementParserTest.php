<?php

namespace Tests\Unit;

use App\Services\StatementParser;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class StatementParserTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->files);
    }

    private function file(string $contents, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ff').'.'.$extension;
        file_put_contents($path, $contents);
        $this->files[] = $path;

        return $path;
    }

    private function sheet(array $rows): array
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);

        $path = tempnam(sys_get_temp_dir(), 'ff').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $this->files[] = $path;

        return (new StatementParser)->parseFile($path, 'xlsx');
    }

    public function test_reads_an_excel_sheet_with_text_cells(): void
    {
        $result = $this->sheet([
            ['Data', 'Histórico', 'Valor'],
            ['05/10/2026', 'PIX RECEBIDO', '1.500,00'],
            ['06/10/2026', 'MERCADO CENTRAL', '-234,56'],
            ['sem data', 'X', '1,00'],
        ]);

        $this->assertSame([
            ['date' => '2026-10-05', 'description' => 'PIX RECEBIDO', 'amount' => 1500.0],
            ['date' => '2026-10-06', 'description' => 'MERCADO CENTRAL', 'amount' => -234.56],
        ], $result['rows']);
        $this->assertSame(1, $result['skipped']);
    }

    public function test_reads_excel_dates_and_numbers_and_skips_text_above_the_header(): void
    {
        $result = $this->sheet([
            ['Extrato da conta 1234-5', null, null],
            [null, null, null],
            ['Data', 'Descrição', 'Valor'],
            [Date::PHPToExcel(strtotime('2026-10-03')), 'Padaria', -24.5],
            [Date::PHPToExcel(strtotime('2026-10-04')), 'Salário', 3000],
        ]);

        $this->assertSame([
            ['date' => '2026-10-03', 'description' => 'Padaria', 'amount' => -24.5],
            ['date' => '2026-10-04', 'description' => 'Salário', 'amount' => 3000.0],
        ], $result['rows']);
    }

    public function test_reads_separate_credit_and_debit_columns(): void
    {
        $result = $this->sheet([
            ['Data', 'Descrição', 'Crédito', 'Débito'],
            ['01/10/2026', 'Salário', '5000,00', null],
            ['02/10/2026', 'Aluguel', null, '1800,00'],
        ]);

        $this->assertSame([5000.0, -1800.0], array_column($result['rows'], 'amount'));
    }

    public function test_rejects_sheets_without_the_needed_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->sheet([['Nome', 'Idade'], ['Ana', 30]]);
    }

    public function test_reads_pdf_text_lines(): void
    {
        $text = <<<'TXT'
Fatura do cartão
Vencimento 10/11/2026
03/10 PADARIA DO ZE 24,50
05/10 UBER *TRIP 1.234,56
06/10/2026 ESTORNO LOJA -50,00
07/10 SALDO ANTERIOR
Total da fatura R$ 1.209,06
TXT;

        $rows = (new StatementParser)->parsePdfText($text)['rows'];

        $this->assertSame([
            ['date' => '2026-10-03', 'description' => 'PADARIA DO ZE', 'amount' => 24.5],
            ['date' => '2026-10-05', 'description' => 'UBER *TRIP', 'amount' => 1234.56],
            ['date' => '2026-10-06', 'description' => 'ESTORNO LOJA', 'amount' => -50.0],
        ], $rows);
    }

    public function test_pdf_debit_and_credit_markers_set_the_sign(): void
    {
        $rows = (new StatementParser)->parsePdfText("01/10/2026 PIX ENVIADO 100,00 D 900,00\n02/10/2026 PIX RECEBIDO 50,00 C 950,00")['rows'];

        $this->assertSame([-100.0, 50.0], array_column($rows, 'amount'));
    }

    public function test_reads_ofx_without_closing_tags(): void
    {
        $ofx = <<<'OFX'
OFXHEADER:100
DATA:OFXSGML

<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS><BANKTRANLIST>
<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20261005120000[-3:BRT]
<TRNAMT>-45.90
<FITID>1
<MEMO>NETFLIX
</STMTTRN>
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20261006
<TRNAMT>3000.00
<FITID>2
<NAME>SALARIO
</STMTTRN>
</BANKTRANLIST></STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>
OFX;

        $this->assertSame([
            ['date' => '2026-10-05', 'description' => 'NETFLIX', 'amount' => -45.9],
            ['date' => '2026-10-06', 'description' => 'SALARIO', 'amount' => 3000.0],
        ], (new StatementParser)->parseFile($this->file($ofx, 'ofx'), 'ofx')['rows']);
    }

    public function test_rejects_unknown_and_unreadable_files(): void
    {
        $parser = new StatementParser;

        try {
            $parser->parseFile($this->file('a;b', 'csv'), 'csv');
            $this->fail('CSV não deveria ser aceito.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        $parser->parseFile($this->file('isto não é uma planilha', 'xlsx'), 'xlsx');
    }
}
