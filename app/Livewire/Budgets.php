<?php

namespace App\Livewire;

use App\Livewire\Forms\BudgetForm;
use App\Models\Budget;
use App\Models\Category;
use App\Support\Dates;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Orçamentos · FinanceFlow')]
class Budgets extends Component
{
    public BudgetForm $form;

    /** Mês exibido, "Y-m". */
    #[Url(except: '')]
    public string $month = '';

    /** Orçamento aberto no modal (null = criando). */
    public ?int $editingId = null;

    public function mount(): void
    {
        $this->normalizeMonth();
    }

    public function updatedMonth(): void
    {
        $this->normalizeMonth();
        $this->editingId = null;
        $this->form->clear();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->form->clear();

        $this->dispatch('open-modal', name: 'budget-form');
    }

    public function edit(int $id): void
    {
        $budget = Budget::query()->where('month', $this->month)->find($id);

        if ($budget === null) {
            return;
        }

        $this->editingId = $budget->id;
        $this->form->setBudget($budget);

        $this->dispatch('open-modal', name: 'budget-form');
    }

    public function save(): void
    {
        $data = $this->form->validated($this->month, $this->editingId);

        if ($this->editingId === null) {
            Budget::create($data);
        } else {
            // Ao editar, só o limite muda.
            Budget::whereKey($this->editingId)->update(['limit' => $data['limit']]);
        }

        $this->closeForm();
    }

    public function delete(): void
    {
        Budget::whereKey($this->editingId)->delete();

        $this->closeForm();
    }

    /** Cria no mês exibido os mesmos limites do mês anterior (sem repetir categorias). */
    public function copyPrevious(): void
    {
        Budget::query()
            ->where('month', $this->previousMonth())
            ->get()
            ->each(fn (Budget $budget) => Budget::firstOrCreate(
                ['category_id' => $budget->category_id, 'month' => $this->month],
                ['limit' => $budget->limit],
            ));
    }

    public function render()
    {
        $items = Budget::query()
            ->with('category:id,name')
            ->withSpent($this->month)
            ->where('month', $this->month)
            ->get()
            ->sortBy(fn (Budget $b) => $b->category->name)
            ->values();

        $limit = (float) $items->sum('limit');
        $spent = (float) $items->sum('spent');

        return view('livewire.budgets', [
            'months' => $this->monthOptions(),
            'items' => $items,
            'available' => $this->availableCategories($items),
            'editingName' => $items->firstWhere('id', $this->editingId)?->category->name,
            'previousLabel' => Dates::monthLabel($this->previousMonth()),
            'canCopyPrevious' => $items->isEmpty() && Budget::where('month', $this->previousMonth())->exists(),
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
     * Onde estamos no mês exibido:
     * "current" (mês atual, com o dia de hoje), "closed" (já passou) ou "future" (ainda não começou).
     */
    private function pace(): array
    {
        $today = now();
        $days = Carbon::createFromFormat('!Y-m', $this->month)->daysInMonth;
        $current = $today->format('Y-m');

        return match (true) {
            $this->month === $current => [
                'state' => 'current',
                'day' => $today->day,
                'days' => $days,
                'daysLeft' => $days - $today->day,
                'percent' => $today->day / $days * 100,
            ],
            $this->month < $current => ['state' => 'closed', 'day' => null, 'days' => $days, 'daysLeft' => 0, 'percent' => 100],
            default => ['state' => 'future', 'day' => null, 'days' => $days, 'daysLeft' => $days, 'percent' => 0],
        };
    }

    /** Categorias de despesa ainda sem orçamento no mês. */
    private function availableCategories(Collection $items): Collection
    {
        return Category::query()
            ->ofType('expense')
            ->whereNotIn('id', $items->pluck('category_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Do mês seguinte (para planejar) até o orçamento mais antigo, ou 2 meses atrás. */
    private function monthOptions(): array
    {
        $next = now()->startOfMonth()->addMonth();
        $oldest = Budget::query()->min('month');
        $first = now()->startOfMonth()->subMonths(2);

        if ($oldest !== null && $oldest < $first->format('Y-m')) {
            $first = Carbon::createFromFormat('!Y-m', $oldest);
        }

        $options = [];
        for ($month = $next->copy(); $month->greaterThanOrEqualTo($first); $month->subMonth()) {
            $options[$month->format('Y-m')] = Dates::monthLabel($month->format('Y-m'));
        }

        // Um mês fora da faixa escolhido pela URL continua aparecendo.
        $options[$this->month] ??= Dates::monthLabel($this->month);
        krsort($options);

        return $options;
    }

    private function previousMonth(): string
    {
        return Carbon::createFromFormat('!Y-m', $this->month)->subMonth()->format('Y-m');
    }

    private function closeForm(): void
    {
        $this->editingId = null;
        $this->form->clear();

        $this->dispatch('close-modal', name: 'budget-form');
    }

    private function normalizeMonth(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }
}
