<?php

namespace App\Livewire;

use App\Support\SampleData;
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

    public function mount(): void
    {
        $this->normalizePeriod();
    }

    public function updatedPeriod(): void
    {
        $this->normalizePeriod();
    }

    private function normalizePeriod(): void
    {
        if (! array_key_exists($this->period, self::PERIODS)) {
            $this->period = 'month';
        }
    }

    public function render()
    {
        $accounts = $this->accounts();
        $months = $this->markPeriod($this->cashFlow());
        $inPeriod = array_filter($months, fn (array $m) => $m['in_period']);

        $income = array_sum(array_column($inPeriod, 'income'));
        $expense = array_sum(array_column($inPeriod, 'expense'));

        return view('livewire.dashboard', [
            'periods' => self::PERIODS,
            'rangeLabel' => $this->rangeLabel($inPeriod),
            'stats' => [
                'balance' => array_sum(array_column($accounts, 'balance')),
                'accounts' => count($accounts),
                'income' => $income,
                'expense' => $expense,
                'savingsRate' => $income > 0 ? (int) round(($income - $expense) / $income * 100) : 0,
            ],
            'months' => $months,
            'budgets' => $this->budgets(),
            'transactions' => $this->recentTransactions(),
            'goals' => $this->goals(),
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

    private function rangeLabel(array $months): string
    {
        $months = array_values($months);
        $first = $months[0]['name'];
        $last = $months[count($months) - 1]['name'];

        return $first === $last ? "{$last} de 2026" : "{$first} a {$last} de 2026";
    }

    // ---------------------------------------------------------------------
    // Dados de exemplo — serão substituídos pelas consultas ao banco.
    // ---------------------------------------------------------------------

    private function accounts(): array
    {
        return SampleData::accounts();
    }

    private function cashFlow(): array
    {
        return [
            ['label' => 'Mai', 'name' => 'maio', 'income' => 7500, 'expense' => 6100],
            ['label' => 'Jun', 'name' => 'junho', 'income' => 7500, 'expense' => 6900],
            ['label' => 'Jul', 'name' => 'julho', 'income' => 7800, 'expense' => 7700],
            ['label' => 'Ago', 'name' => 'agosto', 'income' => 8100, 'expense' => 6300],
            ['label' => 'Set', 'name' => 'setembro', 'income' => 7500, 'expense' => 5400],
            ['label' => 'Out', 'name' => 'outubro', 'income' => 8250, 'expense' => 4297],
        ];
    }

    private function budgets(): array
    {
        return [
            ['name' => 'Mercado', 'spent' => 1120, 'limit' => 1400],
            ['name' => 'Moradia', 'spent' => 1972, 'limit' => 2000],
            ['name' => 'Transporte', 'spent' => 410, 'limit' => 600],
            ['name' => 'Lazer', 'spent' => 520, 'limit' => 450],
            ['name' => 'Saúde', 'spent' => 180, 'limit' => 400],
        ];
    }

    private function recentTransactions(): array
    {
        return array_slice(SampleData::transactions(), 0, 6);
    }

    private function goals(): array
    {
        return [
            ['name' => 'Reserva de emergência', 'saved' => 9000, 'target' => 15000],
            ['name' => 'Viagem de férias', 'saved' => 2400, 'target' => 6000],
            ['name' => 'Notebook novo', 'saved' => 3600, 'target' => 4500],
        ];
    }
}
