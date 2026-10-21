<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Dates
{
    private const MONTHS = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    /** 10 => "outubro" */
    public static function monthName(int $month): string
    {
        return self::MONTHS[$month];
    }

    /** 10 => "Out" */
    public static function monthAbbr(int $month): string
    {
        return ucfirst(mb_substr(self::MONTHS[$month], 0, 3));
    }

    /** "2026-10" => "Outubro de 2026" */
    public static function monthLabel(string $month): string
    {
        $date = Carbon::createFromFormat('!Y-m', $month);

        return ucfirst(self::MONTHS[$date->month]).' de '.$date->year;
    }

    /**
     * Intervalo de meses ("Y-m"):
     * "outubro de 2026", "agosto a outubro de 2026" ou "novembro de 2025 a abril de 2026".
     */
    public static function rangeLabel(string $from, string $to): string
    {
        $start = Carbon::createFromFormat('!Y-m', $from);
        $end = Carbon::createFromFormat('!Y-m', $to);

        if ($from === $to) {
            return self::MONTHS[$end->month].' de '.$end->year;
        }

        if ($start->year === $end->year) {
            return self::MONTHS[$start->month].' a '.self::MONTHS[$end->month].' de '.$end->year;
        }

        return self::MONTHS[$start->month].' de '.$start->year.' a '.self::MONTHS[$end->month].' de '.$end->year;
    }

    /** "2027-06" => "jun/2027" */
    public static function shortMonth(string $month): string
    {
        $date = Carbon::createFromFormat('!Y-m', $month);

        return mb_substr(self::MONTHS[$date->month], 0, 3).'/'.$date->year;
    }

    /** Meses de $from ("Y-m") até $to ("Y-m"); negativo se $to já passou. */
    public static function monthsBetween(string $from, string $to): int
    {
        [$fy, $fm] = array_map('intval', explode('-', $from));
        [$ty, $tm] = array_map('intval', explode('-', $to));

        return ($ty * 12 + $tm) - ($fy * 12 + $fm);
    }
}
