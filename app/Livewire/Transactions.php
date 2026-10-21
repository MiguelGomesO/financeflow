<?php

namespace App\Livewire;

use App\Livewire\Forms\TransactionForm;
use App\Support\SampleData;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Transações · FinanceFlow')]
class Transactions extends Component
{
    public const TYPES = [
        'all' => 'Todas',
        'income' => 'Entradas',
        'expense' => 'Saídas',
    ];

    public TransactionForm $form;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $type = 'all';

    #[Url(except: '')]
    public string $category = '';

    /** Por enquanto a lista vive no estado do componente (sem banco). */
    public array $transactions = [];

    public function mount(): void
    {
        $this->transactions = SampleData::transactions();
        $this->form->date = now()->toDateString();
        $this->normalizeFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['type', 'category'], true)) {
            $this->normalizeFilters();
        }
    }

    public function save(): void
    {
        $nextId = max(array_column($this->transactions, 'id') ?: [0]) + 1;

        $this->transactions[] = $this->form->toTransaction($nextId);
        $this->form->clear();

        $this->dispatch('transaction-saved');
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'category');
    }

    public function render()
    {
        $filtered = $this->filtered();
        $income = array_sum(array_filter(array_column($filtered, 'amount'), fn ($v) => $v > 0));
        $expense = abs(array_sum(array_filter(array_column($filtered, 'amount'), fn ($v) => $v < 0)));

        return view('livewire.transactions', [
            'rows' => $filtered,
            'types' => self::TYPES,
            'categories' => SampleData::CATEGORIES,
            'accounts' => SampleData::ACCOUNTS,
            'hasFilters' => $this->search !== '' || $this->type !== 'all' || $this->category !== '',
            'totals' => [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ],
        ]);
    }

    private function filtered(): array
    {
        $search = mb_strtolower(trim($this->search));

        return collect($this->transactions)
            ->when($search !== '', fn ($c) => $c->filter(
                fn (array $t) => str_contains(mb_strtolower($t['description']), $search)
            ))
            ->when($this->type === 'income', fn ($c) => $c->filter(fn (array $t) => $t['amount'] > 0))
            ->when($this->type === 'expense', fn ($c) => $c->filter(fn (array $t) => $t['amount'] < 0))
            ->when($this->category !== '', fn ($c) => $c->where('category', $this->category))
            ->sortBy([['date', 'desc'], ['id', 'desc']])
            ->values()
            ->all();
    }

    private function normalizeFilters(): void
    {
        if (! array_key_exists($this->type, self::TYPES)) {
            $this->type = 'all';
        }

        if ($this->category !== '' && ! in_array($this->category, SampleData::CATEGORIES, true)) {
            $this->category = '';
        }
    }
}
