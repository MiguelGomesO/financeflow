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
}
