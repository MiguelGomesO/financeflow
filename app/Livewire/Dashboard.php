<?php

namespace App\Livewire;

use App\Queries\DashboardQuery;
use App\Services\RecurringGenerator;
use App\Support\Dates;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Visão geral · FinanceFlow')]
class Dashboard extends Component
{
    public const PERIODS = [
        'month' => 'Este mês',
        'quarter' => 'Últimos 3 meses',
        'semester' => 'Últimos 6 meses',
    ];

    /** Quantos meses (contando do atual) cada período cobre. */
    private const PERIOD_MONTHS = [
        'month' => 1,
        'quarter' => 3,
        'semester' => 6,
    ];

    #[Url]
    public string $period = 'month';

    public function mount(RecurringGenerator $recurring): void
    {
        // Garante os lançamentos recorrentes em dia mesmo sem o agendador rodando.
        $recurring->run();

        $this->normalizePeriod();
    }

    public function updatedPeriod(): void
    {
        $this->normalizePeriod();
    }

    /** Recarrega os números quando uma transação é criada, editada ou excluída. */
    #[On('transactions-changed')]
    public function refreshData(): void {}

    public function render()
    {
        $query = new DashboardQuery(now());

        $accounts = $query->accounts();
        $months = $this->markPeriod($query->cashFlow());
        $inPeriod = array_values(array_filter($months, fn (array $m) => $m['in_period']));

        $income = array_sum(array_column($inPeriod, 'income'));
        $expense = array_sum(array_column($inPeriod, 'expense'));

        return view('livewire.dashboard', [
            'periods' => self::PERIODS,
            'rangeLabel' => Dates::rangeLabel($inPeriod[0]['key'], end($inPeriod)['key']),
            'stats' => [
                'balance' => $accounts['balance'],
                'accounts' => $accounts['count'],
                'income' => $income,
                'expense' => $expense,
                'savingsRate' => $income > 0 ? (int) round(($income - $expense) / $income * 100) : 0,
            ],
            'months' => $months,
            'budgets' => $query->budgets(),
            'transactions' => $query->recentTransactions(),
            'goals' => $query->goals(),
        ]);
    }

    private function markPeriod(array $months): array
    {
        $count = self::PERIOD_MONTHS[$this->period] ?? 1;
        $firstInPeriod = count($months) - $count;

        foreach ($months as $i => &$month) {
            $month['in_period'] = $i >= $firstInPeriod;
        }

        return $months;
    }

    private function normalizePeriod(): void
    {
        if (! array_key_exists($this->period, self::PERIODS)) {
            $this->period = 'month';
        }
    }
}
