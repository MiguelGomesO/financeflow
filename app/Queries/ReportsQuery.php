<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Números da página de Relatórios: o período escolhido (os últimos N meses, até hoje)
 * contra o período anterior de mesmo tamanho, cortado no mesmo ponto. Assim o mês em
 * andamento é comparado com o mesmo trecho do mês passado, e não com ele inteiro.
 */
class ReportsQuery
{
    /** Quantas categorias aparecem antes de o resto virar "Outras". */
    private const TOP_CATEGORIES = 7;

    private CarbonImmutable $today;

    public function __construct(CarbonInterface $today, private int $months)
    {
        $this->today = CarbonImmutable::instance($today)->startOfDay();
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, previousStart: CarbonImmutable, previousEnd: CarbonImmutable} */
    public function ranges(): array
    {
        $start = $this->today->startOfMonth()->subMonthsNoOverflow($this->months - 1);

        return [
            'start' => $start,
            'end' => $this->today,
            'previousStart' => $start->subMonthsNoOverflow($this->months),
            'previousEnd' => $this->today->subMonthsNoOverflow($this->months),
        ];
    }

    /**
     * @return array{
     *     current: array{income: float, expense: float, net: float},
     *     previous: array{income: float, expense: float, net: float},
     *     categories: list<array{name: string, current: float, previous: float, share: float, delta: ?float}>
     * }
     */
    public function compare(): array
    {
        $range = $this->ranges();
        $totals = ['current' => ['income' => 0.0, 'expense' => 0.0], 'previous' => ['income' => 0.0, 'expense' => 0.0]];
        $byCategory = [];

        Transaction::query()
            ->betweenDates($range['previousStart'], $range['end'])
            ->get(['date', 'amount', 'category_id'])
            ->each(function (Transaction $transaction) use ($range, &$totals, &$byCategory) {
                $date = $transaction->date->toDateString();

                if ($date >= $range['start']->toDateString()) {
                    $side = 'current';
                } elseif ($date <= $range['previousEnd']->toDateString()) {
                    $side = 'previous';
                } else {
                    return; // trecho do período anterior depois do ponto de corte
                }

                $kind = $transaction->amount >= 0 ? 'income' : 'expense';
                $totals[$side][$kind] += abs($transaction->amount);

                if ($kind === 'expense') {
                    $byCategory[$transaction->category_id][$side] = ($byCategory[$transaction->category_id][$side] ?? 0) + abs($transaction->amount);
                }
            });

        return [
            'current' => $this->withNet($totals['current']),
            'previous' => $this->withNet($totals['previous']),
            'categories' => $this->categories($byCategory, $totals['current']['expense']),
        ];
    }

    /** Variação em %, ou null quando não há base de comparação. */
    public static function delta(float $current, float $previous): ?float
    {
        return $previous > 0 ? ($current - $previous) / $previous * 100 : null;
    }

    private function withNet(array $totals): array
    {
        $totals = array_map(fn (float $v) => round($v, 2), $totals);

        return $totals + ['net' => round($totals['income'] - $totals['expense'], 2)];
    }

    /** Maiores gastos do período primeiro; o que passar do limite é somado em "Outras". */
    private function categories(array $byCategory, float $totalExpense): array
    {
        $names = Category::query()->whereIn('id', array_keys($byCategory))->pluck('name', 'id');

        $rows = collect($byCategory)
            ->map(fn (array $v, int $id) => [
                'name' => $names[$id] ?? 'Sem categoria',
                'current' => round($v['current'] ?? 0, 2),
                'previous' => round($v['previous'] ?? 0, 2),
            ])
            ->sort(fn (array $a, array $b) => [$b['current'], $b['previous']] <=> [$a['current'], $a['previous']])
            ->values();

        if ($rows->count() > self::TOP_CATEGORIES + 1) {
            $rest = $rows->slice(self::TOP_CATEGORIES);

            $rows = $rows->take(self::TOP_CATEGORIES)->push([
                'name' => 'Outras',
                'current' => round($rest->sum('current'), 2),
                'previous' => round($rest->sum('previous'), 2),
            ]);
        }

        return $rows
            ->map(fn (array $row) => $row + [
                'share' => $totalExpense > 0 ? $row['current'] / $totalExpense * 100 : 0.0,
                'delta' => self::delta($row['current'], $row['previous']),
            ])
            ->all();
    }
}
