<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Exporta uma consulta de transações para CSV (abre direto no Excel em pt-BR), XLSX ou PDF. */
class TransactionExport
{
    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    private const HEADERS = ['Data', 'Descrição', 'Categoria', 'Conta/Cartão', 'Etiquetas', 'Valor'];

    /** @param  string  $filters  Resumo dos filtros aplicados, impresso no cabeçalho do PDF. */
    public static function download(Builder $query, string $format, string $month, string $filters = ''): StreamedResponse
    {
        $transactions = $query
            ->with(['category:id,name', 'account:id,name', 'creditCard:id,name', 'tags:id,name'])
            ->latestFirst()
            ->get();

        $filename = "transacoes-{$month}.{$format}";

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.transactions', [
                'transactions' => $transactions,
                'monthLabel' => Dates::monthLabel($month),
                'filters' => $filters,
                'income' => (float) $transactions->where('amount', '>', 0)->sum('amount'),
                'expense' => (float) -$transactions->where('amount', '<', 0)->sum('amount'),
            ])->setPaper('a4');

            return response()->streamDownload(fn () => print $pdf->output(), $filename, ['Content-Type' => 'application/pdf']);
        }

        return self::spreadsheet($transactions, $format === 'csv', $filename);
    }

    private static function spreadsheet(Collection $transactions, bool $csv, string $filename): StreamedResponse
    {
        $sheet = new Spreadsheet;
        $worksheet = $sheet->getActiveSheet()->setTitle('Transações');

        foreach (self::HEADERS as $i => $header) {
            $worksheet->setCellValueExplicit([$i + 1, 1], $header, DataType::TYPE_STRING);
        }

        foreach ($transactions as $i => $transaction) {
            $row = $i + 2;

            $worksheet->setCellValueExplicit([1, $row], $transaction->date->format('d/m/Y'), DataType::TYPE_STRING);
            $worksheet->setCellValueExplicit([2, $row], self::text($transaction->description, $csv), DataType::TYPE_STRING);
            $worksheet->setCellValueExplicit([3, $row], self::text($transaction->category->name, $csv), DataType::TYPE_STRING);
            $worksheet->setCellValueExplicit([4, $row], self::text($transaction->source_name ?? '', $csv), DataType::TYPE_STRING);

            $worksheet->setCellValueExplicit([5, $row], self::text($transaction->tags->pluck('name')->implode(', '), $csv), DataType::TYPE_STRING);

            // No CSV o valor vai como texto com vírgula, para o Excel em pt-BR reconhecer como número.
            $csv
                ? $worksheet->setCellValueExplicit([6, $row], number_format($transaction->amount, 2, ',', ''), DataType::TYPE_STRING)
                : $worksheet->setCellValueExplicit([6, $row], $transaction->amount, DataType::TYPE_NUMERIC);
        }

        if (! $csv) {
            $worksheet->getStyle('F2:F'.$worksheet->getHighestRow())
                ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            $worksheet->getStyle('A1:F1')->getFont()->setBold(true);

            foreach (range('A', 'F') as $column) {
                $worksheet->getColumnDimension($column)->setAutoSize(true);
            }
        }

        $writer = IOFactory::createWriter($sheet, $csv ? 'Csv' : 'Xlsx');

        if ($csv) {
            $writer->setDelimiter(';')->setEnclosure('"')->setUseBOM(true);
        }

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename);
    }

    /** Evita que o Excel trate texto começado por = + - @ como fórmula ao abrir o CSV. */
    private static function text(string $value, bool $csv): string
    {
        return $csv && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
