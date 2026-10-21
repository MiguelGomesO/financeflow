<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Dates
{
    private const MONTHS = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    /** "2026-10" => "Outubro de 2026" */
    public static function monthLabel(string $month): string
    {
        $date = Carbon::createFromFormat('!Y-m', $month);

        return ucfirst(self::MONTHS[$date->month]).' de '.$date->year;
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
