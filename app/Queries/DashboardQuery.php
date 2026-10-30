<?php

namespace App\Queries;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Goal;
use App\Models\GoalDeposit;
use App\Models\Transaction;
use App\Support\BudgetStatus;
use App\Support\Dates;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Consultas da Visão geral. Devolve arrays simples, no formato que os
 * componentes Blade do dashboard esperam.
 */
class DashboardQuery
{
    private CarbonImmutable $today;

    public function __construct(CarbonInterface $today)
    {
        $this->today = CarbonImmutable::instance($today)->startOfDay();
    }

    /** @return array{count: int, balance: float} */
    public function accounts(): array
    {
        $accounts = Account::query()->withBalance()->get();

        return [
            'count' => $accounts->count(),
            'balance' => round($accounts->sum('balance') - GoalDeposit::totalSaved(), 2),
        ];
    }

    /**
     * Entradas e saídas dos últimos $months meses, do mais antigo ao atual.
     * Agrupado em PHP para funcionar igual em MySQL e SQLite.
     */
    public function cashFlow(int $months = 6): array
    {
        $start = $this->today->startOfMonth()->subMonths($months - 1);
        $end = $this->today->endOfMonth();

        $flow = [];
        for ($month = $start; $month <= $end; $month = $month->addMonth()) {
            $flow[$month->format('Y-m')] = [
                'key' => $month->format('Y-m'),
                'label' => Dates::monthAbbr($month->month),
                'name' => Dates::monthName($month->month),
                'income' => 0.0,
                'expense' => 0.0,
            ];
        }

        Transaction::query()
            ->betweenDates($start, $end)
            ->get(['date', 'amount'])
            ->each(function (Transaction $transaction) use (&$flow) {
                $key = $transaction->date->format('Y-m');

                if ($transaction->amount >= 0) {
                    $flow[$key]['income'] += $transaction->amount;
                } else {
                    $flow[$key]['expense'] += -$transaction->amount;
                }
            });

        return array_values(array_map(fn (array $m) => [
            ...$m,
            'income' => round($m['income'], 2),
            'expense' => round($m['expense'], 2),
        ], $flow));
    }

    /** Orçamentos do mês atual, os mais consumidos primeiro. */
    public function budgets(int $limit = 5): array
    {
        $month = $this->today->format('Y-m');

        return Budget::query()
            ->with('category:id,name')
            ->withSpent($month)
            ->where('month', $month)
            ->get()
            ->sortByDesc(fn (Budget $b) => $b->limit > 0 ? $b->spent / $b->limit : 0)
            ->take($limit)
            ->map(fn (Budget $b) => [
                'name' => $b->category->name,
                'spent' => (float) $b->spent,
                'limit' => $b->limit,
            ])
            ->values()
            ->all();
    }

    /** Orçamentos do mês que chegaram a 80% ou 100% do limite, os mais consumidos primeiro. */
    public function budgetAlerts(): array
    {
        $month = $this->today->format('Y-m');

        return Budget::query()
            ->with('category:id,name')
            ->withSpent($month)
            ->where('month', $month)
            ->get()
            ->map(fn (Budget $b) => [
                'name' => $b->category->name,
                'spent' => (float) $b->spent,
                'limit' => $b->limit,
                'level' => BudgetStatus::alertLevel((float) $b->spent, $b->limit),
            ])
            ->filter(fn (array $a) => $a['level'] > 0)
            ->sortByDesc(fn (array $a) => $a['spent'] / $a['limit'])
            ->values()
            ->all();
    }

    public function recentTransactions(int $limit = 6): array
    {
        return Transaction::query()
            ->with('category:id,name')
            // Parcelas e lançamentos de datas futuras ainda não aconteceram.
            ->where('date', '<=', $this->today->toDateString())
            ->latestFirst()
            ->limit($limit)
            ->get()
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'description' => $t->description,
                'installment' => $t->installment_label,
                'category' => $t->category->name,
                'date' => $t->date->toDateString(),
                'amount' => $t->amount,
            ])
            ->all();
    }

    /** Metas com prazo mais próximo primeiro. */
    public function goals(int $limit = 3): array
    {
        return Goal::query()
            ->withSum('deposits', 'amount')
            ->orderBy('due_month')
            ->limit($limit)
            ->get()
            ->map(fn (Goal $g) => [
                'name' => $g->name,
                'saved' => $g->saved,
                'target' => $g->target,
            ])
            ->all();
    }
}
