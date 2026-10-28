<?php

namespace App\Support;

/**
 * Situação de uma meta: percentual, quanto falta e quanto guardar por mês
 * para chegar ao valor até o prazo.
 */
class GoalProgress
{
    public static function for(array $goal, string $currentMonth): array
    {
        $remaining = max(0, $goal['target'] - $goal['saved']);
        $percent = $goal['target'] > 0 ? min(100, $goal['saved'] / $goal['target'] * 100) : 0;
        $monthsLeft = Dates::monthsBetween($currentMonth, $goal['due']);

        [$status, $tag] = match (true) {
            $remaining <= 0 => ['Concluída', 'bg-brand text-white'],
            $monthsLeft < 0 => ['Prazo vencido', 'bg-expense-light text-expense-dark-fg'],
            $percent >= 75 => ['Quase lá', 'bg-brand-light text-brand-dark-fg'],
            default => ['Em andamento', 'bg-mist text-soft'],
        };

        return [
            'percent' => $percent,
            'remaining' => $remaining,
            // No mês do prazo (ou depois dele) o que falta precisa entrar de uma vez.
            'monthly' => $remaining > 0 ? $remaining / max(1, $monthsLeft) : 0,
            'status' => $status,
            'tag' => $tag,
        ];
    }
}
