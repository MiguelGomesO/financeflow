<?php

namespace App\Support;

/**
 * Situação de um orçamento: dentro do limite, em atenção (a partir de 90%) ou estourado.
 * As classes ficam aqui para Dashboard e Orçamentos usarem as mesmas cores.
 */
class BudgetStatus
{
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
