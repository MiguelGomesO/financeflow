<?php

namespace App\Livewire;

use App\Queries\DashboardQuery;
use App\Queries\ReportsQuery;
use App\Services\RecurringGenerator;
use App\Support\Dates;
use App\Support\Money;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Relatórios · FinanceFlow')]
class Reports extends Component
{
    public const PERIODS = [
        'month' => 'Este mês',
        'quarter' => 'Últimos 3 meses',
        'semester' => 'Últimos 6 meses',
        'year' => 'Últimos 12 meses',
    ];

    /** Quantos meses (contando do atual) cada período cobre. */
    private const PERIOD_MONTHS = [
        'month' => 1,
        'quarter' => 3,
        'semester' => 6,
        'year' => 12,
    ];

    /** O gráfico de evolução mostra pelo menos isto, para um mês só não virar uma barra solta. */
    private const MIN_CHART_MONTHS = 6;

    #[Url]
    public string $period = 'month';

    public function mount(RecurringGenerator $recurring): void
    {
        $recurring->run();

        $this->normalizePeriod();
    }

    public function updatedPeriod(): void
    {
        $this->normalizePeriod();
    }

    public function render()
    {
        $months = self::PERIOD_MONTHS[$this->period];
        $report = new ReportsQuery(now(), $months);
        $range = $report->ranges();
        $data = $report->compare();

        $chartMonths = (new DashboardQuery(now()))->cashFlow(max($months, self::MIN_CHART_MONTHS));
        $firstInPeriod = count($chartMonths) - $months;
        foreach ($chartMonths as $i => &$month) {
            $month['in_period'] = $i >= $firstInPeriod;
        }
        unset($month);

        return view('livewire.reports', [
            'periods' => self::PERIODS,
            'rangeLabel' => Dates::rangeLabel($range['start']->format('Y-m'), $range['end']->format('Y-m')),
            'comparedTo' => $range['previousStart']->format('d/m/y').' a '.$range['previousEnd']->format('d/m/y'),
            'summary' => $this->summary($data),
            'categories' => $data['categories'],
            'expense' => $data['current']['expense'],
            'months' => $chartMonths,
        ]);
    }

    /** Os três cartões do topo, cada um com a diferença para o período anterior. */
    private function summary(array $data): array
    {
        ['current' => $now, 'previous' => $before] = $data;

        return [
            ['label' => 'Entradas', 'tone' => 'income', 'value' => $now['income'], 'hint' => $this->change($now['income'], $before['income'])],
            ['label' => 'Saídas', 'tone' => 'expense', 'value' => $now['expense'], 'hint' => $this->change($now['expense'], $before['expense'])],
            ['label' => 'Resultado', 'tone' => 'dark', 'value' => $now['net'], 'hint' => $this->change($now['net'], $before['net'], percent: false)],
        ];
    }

    /** "12% a mais (R$ 300,00) que no período anterior". */
    private function change(float $now, float $before, bool $percent = true): string
    {
        $diff = round($now - $before, 2);

        if ($diff == 0.0) {
            return $now == 0.0 ? 'Sem movimento nos dois períodos' : 'Igual ao período anterior';
        }

        $amount = Money::brl(abs($diff));
        $direction = $diff > 0 ? 'a mais' : 'a menos';
        $delta = ReportsQuery::delta($now, $before);

        // Sem base (período anterior zerado ou negativo) a porcentagem não diz nada: fica só o valor.
        if (! $percent || $delta === null || $before < 0) {
            return "{$amount} {$direction} que no período anterior";
        }

        return round(abs($delta)).'% '.$direction." ({$amount}) que no período anterior";
    }

    private function normalizePeriod(): void
    {
        if (! array_key_exists($this->period, self::PERIODS)) {
            $this->period = 'month';
        }
    }
}
