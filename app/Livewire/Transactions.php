<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\RecurringGenerator;
use App\Support\Dates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Transações · FinanceFlow')]
class Transactions extends Component
{
    use WithPagination;

    public const TYPES = [
        'all' => 'Todas',
        'income' => 'Entradas',
        'expense' => 'Saídas',
    ];

    /** Mês exibido, "Y-m". */
    #[Url(except: '')]
    public string $month = '';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $type = 'all';

    /** Id da categoria ('' = todas). */
    #[Url(except: '')]
    public string $category = '';

    public function mount(RecurringGenerator $recurring): void
    {
        $recurring->run();

        $this->normalizeFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['month', 'search', 'type', 'category'], true)) {
            $this->normalizeFilters();
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'category');
        $this->resetPage();
    }

    /** Recarrega a lista quando uma transação é criada, editada ou excluída. */
    #[On('transactions-changed')]
    public function refreshList(): void {}

    public function render()
    {
        $filtered = $this->filteredQuery();

        $income = (float) (clone $filtered)->where('amount', '>', 0)->sum('amount');
        $expense = (float) -(clone $filtered)->where('amount', '<', 0)->sum('amount');

        return view('livewire.transactions', [
            'rows' => (clone $filtered)
                ->with(['category:id,name', 'account:id,name', 'creditCard:id,name'])
                ->latestFirst()
                ->paginate(20),
            'months' => $this->monthOptions(),
            'types' => self::TYPES,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'hasFilters' => $this->search !== '' || $this->type !== 'all' || $this->category !== '',
            'totals' => [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ],
        ]);
    }

    private function filteredQuery(): Builder
    {
        $start = Carbon::createFromFormat('!Y-m', $this->month);
        $search = trim($this->search);

        return Transaction::query()
            ->betweenDates($start, $start->copy()->endOfMonth())
            ->when($search !== '', fn (Builder $q) => $q->where('description', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($this->type === 'income', fn (Builder $q) => $q->where('amount', '>', 0))
            ->when($this->type === 'expense', fn (Builder $q) => $q->where('amount', '<', 0))
            ->when($this->category !== '', fn (Builder $q) => $q->where('category_id', (int) $this->category));
    }

    /** Do mês da transação mais antiga até o mês atual, mais recente primeiro. */
    private function monthOptions(): array
    {
        $current = now()->startOfMonth();
        $oldest = Transaction::query()->min('date');
        $first = $oldest ? Carbon::parse($oldest)->startOfMonth() : $current->copy();

        // Um mês futuro escolhido pela URL continua aparecendo na lista.
        $last = Carbon::createFromFormat('!Y-m', $this->month)->max($current);

        $options = [];
        for ($month = $last->copy(); $month->greaterThanOrEqualTo($first->min($last)); $month->subMonth()) {
            $options[$month->format('Y-m')] = Dates::monthLabel($month->format('Y-m'));
        }

        return $options;
    }

    private function normalizeFilters(): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }

        if (! array_key_exists($this->type, self::TYPES)) {
            $this->type = 'all';
        }

        if ($this->category !== '' && ! Category::whereKey((int) $this->category)->exists()) {
            $this->category = '';
        }
    }
}
