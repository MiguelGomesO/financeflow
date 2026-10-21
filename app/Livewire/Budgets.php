<?php

namespace App\Livewire;

use App\Livewire\Forms\BudgetForm;
use App\Support\Dates;
use App\Support\SampleData;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Orçamentos · FinanceFlow')]
class Budgets extends Component
{
    public BudgetForm $form;

    /** Mês exibido, no formato "Y-m". */
    #[Url(except: '')]
    public string $month = '';

    /** Por enquanto os orçamentos vivem no estado do componente (sem banco). */
    public array $budgets = [];

    public function mount(): void
    {
        $this->budgets = SampleData::budgets();
        $this->normalizeMonth();
    }

    public function updatedMonth(): void
    {
        $this->normalizeMonth();
        $this->form->clear();
    }

    public function save(): void
    {
        $this->budgets[$this->month][] = $this->form->toBudget($this->availableCategories());
        $this->form->clear();

        $this->dispatch('close-modal', name: 'new-budget');
    }

    public function render()
    {
        $budgets = $this->budgets[$this->month] ?? [];
        $limit = array_sum(array_column($budgets, 'limit'));
        $spent = array_sum(array_column($budgets, 'spent'));

        return view('livewire.budgets', [
            'months' => array_map(Dates::monthLabel(...), array_combine(array_keys($this->budgets), array_keys($this->budgets))),
            'items' => $budgets,
            'available' => $this->availableCategories(),
            'pace' => $this->pace(),
            'totals' => [
                'limit' => $limit,
                'spent' => $spent,
                'left' => $limit - $spent,
                'percent' => $limit > 0 ? $spent / $limit * 100 : 0,
            ],
        ]);
    }

    /**
     * Onde estamos no mês: no mês atual, o dia de hoje e os dias restantes;
     * meses anteriores já estão encerrados.
     */
    private function pace(): array
    {
        $today = SampleData::today();

        if ($this->month !== $today->format('Y-m')) {
            return ['closed' => true, 'day' => null, 'days' => null, 'daysLeft' => 0, 'percent' => 100];
        }

        $days = $today->daysInMonth;

        return [
            'closed' => false,
            'day' => $today->day,
            'days' => $days,
            'daysLeft' => $days - $today->day,
            'percent' => $today->day / $days * 100,
        ];
    }

    private function availableCategories(): array
    {
        $taken = array_column($this->budgets[$this->month] ?? [], 'name');

        return array_values(array_diff(SampleData::CATEGORIES, SampleData::INCOME_CATEGORIES, $taken));
    }

    private function normalizeMonth(): void
    {
        if (! array_key_exists($this->month, $this->budgets)) {
            $this->month = SampleData::currentMonth();
        }
    }
}
