<?php

namespace App\Support;

/**
 * Situação de um orçamento: dentro do limite, em atenção (a partir de 90%) ou estourado.
 * As classes ficam aqui para Dashboard e Orçamentos usarem as mesmas cores.
 */
class BudgetStatus
{
    /** Faixas de alerta, em % do limite: avisa ao chegar em 80% e de novo ao atingir 100%. */
    public const ALERT_LEVELS = [100, 80];

    /** Maior faixa de alerta alcançada (100, 80) ou 0 se ainda está abaixo de todas. */
    public static function alertLevel(float $spent, float $limit): int
    {
        if ($limit <= 0) {
            return 0;
        }

        foreach (self::ALERT_LEVELS as $level) {
            // Compara em centavos para 80,00 de 100,00 contar como 80% sem erro de ponto flutuante.
            if (round($spent * 100) * 100 >= round($limit * 100) * $level) {
                return $level;
            }
        }

        return 0;
    }

    public static function for(float $spent, float $limit): array
    {
        $ratio = $limit > 0 ? $spent / $limit : 0;

        return match (true) {
            $ratio > 1 => [
                'key' => 'over',
                'label' => 'Estourado',
                'bar' => 'bg-expense',
                'tag' => 'bg-expense-light text-expense-dark',
                'percent' => $ratio * 100,
            ],
            $ratio >= 0.9 => [
                'key' => 'warning',
                'label' => 'Atenção',
                'bar' => 'bg-expense-soft',
                'tag' => 'bg-expense-faint text-expense-dark',
                'percent' => $ratio * 100,
            ],
            default => [
                'key' => 'ok',
                'label' => 'No limite',
                'bar' => 'bg-brand',
                'tag' => 'bg-brand-light text-brand-dark',
                'percent' => $ratio * 100,
            ],
        };
    }
}
