<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Services\RecurringGenerator;
use App\Support\Dates;
use App\Support\TransactionExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /** Id da etiqueta ('' = todas). */
    #[Url(except: '')]
    public string $tag = '';

    /** Quantas transações dá para marcar de uma vez. */
    public const MAX_SELECTION = 1000;

    /** Ids marcados (texto, como vêm dos checkboxes). */
    public array $selected = [];

    /** Categoria escolhida para a troca em massa. */
    public string $bulkCategory = '';

    /** Aviso do resultado da última ação em massa. */
    public ?string $notice = null;

    public function mount(RecurringGenerator $recurring): void
    {
        $recurring->run();

        $this->normalizeFilters();
    }

    public function updated(string $property): void
    {
        $this->notice = null;

        if (in_array($property, ['month', 'search', 'type', 'category', 'tag'], true)) {
            $this->normalizeFilters();
            $this->resetPage();
            $this->clearSelection();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'category', 'tag');
        $this->resetPage();
        $this->clearSelection();
    }

    public function clearSelection(): void
    {
        $this->reset('selected', 'bulkCategory');
        $this->resetErrorBag();
    }

    /** Marca as transações do filtro atual (todas as páginas), até o limite. */
    public function selectFiltered(): void
    {
        $this->selected = $this->filteredQuery()
            ->latestFirst()
            ->limit(self::MAX_SELECTION)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->notice = null;
    }

    public function deleteSelected(): void
    {
        $ids = $this->selectedIds();

        // Pelo model, para comprovantes sem uso serem apagados junto.
        foreach (array_chunk($ids, 500) as $chunk) {
            Transaction::whereKey($chunk)->get()->each->delete();
        }

        $this->finishBulk(count($ids) === 1 ? '1 transação excluída.' : count($ids).' transações excluídas.');
    }

    /** Move as marcadas para a categoria escolhida; ela precisa ser do tipo (despesa ou receita) delas. */
    public function categorizeSelected(): void
    {
        $ids = $this->selectedIds();
        $type = $this->selectionType($ids);
        $category = $type === null ? null : Category::query()->ofType($type)->whereKey((int) $this->bulkCategory)->first();

        if ($ids === [] || $category === null) {
            $this->addError('bulkCategory', 'Escolha uma categoria para o tipo selecionado.');

            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            Transaction::whereKey($chunk)->update(['category_id' => $category->id]);
        }

        $this->finishBulk(count($ids) === 1
            ? "1 transação movida para {$category->name}."
            : count($ids)." transações movidas para {$category->name}.");
    }

    /** Baixa as transações do mês com os filtros atuais (todas, sem paginar). */
    public function export(string $format): StreamedResponse
    {
        abort_unless(in_array($format, TransactionExport::FORMATS, true), 422);

        return TransactionExport::download($this->filteredQuery(), $format, $this->month, $this->filterSummary());
    }

    /** Ex.: "Saídas · Mercado · busca "pão"". Vazio quando não há filtros. */
    private function filterSummary(): string
    {
        $parts = [];

        if ($this->type !== 'all') {
            $parts[] = self::TYPES[$this->type];
        }

        if ($this->category !== '') {
            $parts[] = Category::whereKey((int) $this->category)->value('name');
        }

        if ($this->tag !== '') {
            $parts[] = '#'.Tag::whereKey((int) $this->tag)->value('name');
        }

        if (trim($this->search) !== '') {
            $parts[] = 'busca "'.trim($this->search).'"';
        }

        return implode(' · ', $parts);
    }

    /** Recarrega a lista quando uma transação é criada, editada ou excluída. */
    #[On('transactions-changed')]
    public function refreshList(): void
    {
        $this->selected = array_map('strval', $this->selectedIds());
    }

    public function render()
    {
        $filtered = $this->filteredQuery();

        $income = (float) (clone $filtered)->where('amount', '>', 0)->sum('amount');
        $expense = (float) -(clone $filtered)->where('amount', '<', 0)->sum('amount');

        $selectedIds = $this->selectedIds();
        $bulkType = $this->selectionType($selectedIds);

        return view('livewire.transactions', [
            'selectedCount' => count($selectedIds),
            'bulkType' => $bulkType,
            'bulkCategories' => $bulkType ? Category::query()->ofType($bulkType)->orderBy('name')->get(['id', 'name']) : collect(),
            'rows' => (clone $filtered)
                ->with(['category:id,name', 'account:id,name', 'creditCard:id,name', 'tags:id,name'])
                ->latestFirst()
                ->paginate(20),
            'tags' => Tag::query()->has('transactions')->orderByRaw('lower(name)')->get(['id', 'name']),
            'months' => $this->monthOptions(),
            'types' => self::TYPES,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'hasFilters' => $this->search !== '' || $this->type !== 'all' || $this->category !== '' || $this->tag !== '',
            'totals' => [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ],
        ]);
    }

    /** Ids marcados que ainda existem e são do usuário (o navegador pode mandar qualquer coisa). */
    private function selectedIds(): array
    {
        $ids = array_slice(array_values(array_unique(array_map('intval', $this->selected))), 0, self::MAX_SELECTION);

        return collect(array_chunk($ids, 500))
            ->flatMap(fn (array $chunk) => Transaction::whereKey($chunk)->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** "expense" se todas são saídas, "income" se todas são entradas, null se misturou (ou nada marcado). */
    private function selectionType(array $ids): ?string
    {
        if ($ids === []) {
            return null;
        }

        $amounts = collect(array_chunk($ids, 500))
            ->flatMap(fn (array $chunk) => Transaction::whereKey($chunk)->pluck('amount'));

        return match (true) {
            $amounts->every(fn ($amount) => $amount < 0) => 'expense',
            $amounts->every(fn ($amount) => $amount >= 0) => 'income',
            default => null,
        };
    }

    private function finishBulk(string $notice): void
    {
        $this->clearSelection();
        $this->notice = $notice;
        $this->dispatch('transactions-changed');
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
            ->when($this->category !== '', fn (Builder $q) => $q->where('category_id', (int) $this->category))
            ->when($this->tag !== '', fn (Builder $q) => $q->whereHas('tags', fn (Builder $t) => $t->whereKey((int) $this->tag)));
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

        if ($this->tag !== '' && ! Tag::whereKey((int) $this->tag)->exists()) {
            $this->tag = '';
        }
    }
}
