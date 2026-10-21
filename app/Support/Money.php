<?php

namespace App\Support;

class Money
{
    /**
     * Formata um valor em reais: 1234.5 => "R$ 1.234,50".
     * Com $signed, prefixa "+ " ou "− " conforme o sinal.
     */
    public static function brl(float $value, bool $signed = false, int $decimals = 2): string
    {
        $formatted = 'R$ '.number_format(abs($value), $decimals, ',', '.');

        if (! $signed) {
            return $value < 0 ? '− '.$formatted : $formatted;
        }

        return ($value < 0 ? '− ' : '+ ').$formatted;
    }

    /** Lê um valor digitado: aceita "1.234,56", "1234,56" e "1234.56". */
    public static function parse(string $value): float
    {
        $value = preg_replace('/[^\d.,]/', '', $value);

        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        }

        return is_numeric($value) ? round((float) $value, 2) : 0.0;
    }
}
