<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Services\StatementParser;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Import extends Component
{
    use WithFileUploads;

    public const MAX_ROWS = 500;

    public $file = null;

    /** "account:{id}" ou "card:{id}". */
    public string $source = '';

    /** Fatura de cartão costuma trazer as compras como valores positivos. */
    public bool $invert = false;

    /** Lançamentos lidos do arquivo, sem ajuste de sinal. */
    public array $parsed = [];

    /** Linhas da prévia: date, description, amount, categoryId, selected, duplicate, error. */
    public array $rows = [];

    public int $unreadable = 0;

    /** Resultado da última importação: ['imported' => n]. */
    public ?array $done = null;

    public function mount(): void
    {
        $this->source = $this->firstSource();
    }

    /** Abre o modal sempre do começo. */
    public function open(): void
    {
        $this->discard();
        $this->source = $this->firstSource();

        $this->dispatch('open-modal', name: 'import');
    }

    public function read(StatementParser $parser): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:'.implode(',', StatementParser::EXTENSIONS)],
            'source' => ['required', fn ($attribute, $value, $fail) => $this->sourceExists((string) $value) || $fail('Escolha uma conta ou cartão.')],
        ], [
            'file.required' => 'Escolha o arquivo do extrato.',
            'file.max' => 'O arquivo pode ter até 5 MB.',
            'file.extensions' => 'Use um arquivo Excel, PDF ou OFX.',
            'source.required' => 'Escolha uma conta ou cartão.',
        ]);

        try {
            $result = $parser->parseFile($this->file->getRealPath(), $this->file->getClientOriginalExtension());
        } catch (InvalidArgumentException $e) {
            $this->addError('file', $e->getMessage());

            return;
        }

        if (count($result['rows']) > self::MAX_ROWS) {
            $this->addError('file', 'O arquivo tem mais de '.self::MAX_ROWS.' lançamentos. Divida-o por período e importe em partes.');

            return;
        }

        $this->parsed = $result['rows'];
        $this->unreadable = $result['skipped'];
        $this->done = null;
        $this->invert = str_starts_with($this->source, 'card:') && $this->mostlyPositive($result['rows']);
        $this->buildRows();
    }

    public function updatedSource(): void
    {
        if ($this->parsed !== []) {
            $this->buildRows();
        }
    }

    public function updatedInvert(): void
    {
        if ($this->parsed !== []) {
            $this->buildRows();
        }
    }

    public function import(): void
    {
        $this->validate(['source' => ['required', fn ($attribute, $value, $fail) => $this->sourceExists((string) $value) || $fail('Escolha uma conta ou cartão.')]]);

        [$sourceType, $sourceId] = explode(':', $this->source);
        $categories = Category::pluck('type', 'id');
        $imported = 0;

        DB::transaction(function () use ($sourceType, $sourceId, $categories, &$imported) {
            foreach ($this->rows as $row) {
                if (! $row['selected'] || $row['error'] !== null) {
                    continue;
                }

                $type = $row['amount'] > 0 ? 'income' : 'expense';

                if (($categories[(int) $row['categoryId']] ?? null) !== $type) {
                    continue;
                }

                Transaction::create([
                    'description' => $row['description'],
                    'amount' => $row['amount'],
                    'date' => $row['date'],
                    'category_id' => (int) $row['categoryId'],
                    'account_id' => $sourceType === 'account' ? (int) $sourceId : null,
                    'credit_card_id' => $sourceType === 'card' ? (int) $sourceId : null,
                ]);

                $imported++;
            }
        });

        $this->discard();
        $this->done = ['imported' => $imported];

        $this->dispatch('transactions-changed');
    }

    /** Volta para a escolha do arquivo. */
    public function discard(): void
    {
        $this->reset('file', 'parsed', 'rows', 'unreadable', 'invert', 'done');
        $this->resetErrorBag();
    }

    public function toggleAll(bool $selected): void
    {
        foreach ($this->rows as $i => $row) {
            $this->rows[$i]['selected'] = $selected && $row['error'] === null;
        }
    }

    public function render()
    {
        $selected = collect($this->rows)->where('selected', true);

        return view('livewire.import', [
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
            'cards' => CreditCard::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'type'])->groupBy('type'),
            'summary' => [
                'count' => $selected->count(),
                'income' => (float) $selected->where('amount', '>', 0)->sum('amount'),
                'expense' => (float) -$selected->where('amount', '<', 0)->sum('amount'),
            ],
        ]);
    }

    /** Monta a prévia: aplica o sinal, sugere a categoria e marca o que já foi lançado antes. */
    private function buildRows(): void
    {
        $duplicates = $this->existingCounts();
        $guesses = $this->categoryGuesses();
        $firstOfType = Category::query()->orderBy('name')->get(['id', 'type'])->groupBy('type')->map(fn ($group) => $group->first()->id);
        $isCard = str_starts_with($this->source, 'card:');

        $this->rows = collect($this->parsed)->map(function (array $row) use (&$duplicates, $guesses, $firstOfType, $isCard) {
            $amount = $this->invert ? -$row['amount'] : $row['amount'];
            $type = $amount > 0 ? 'income' : 'expense';
            $key = $this->key($row['date'], $amount, $row['description']);

            $duplicate = ($duplicates[$key] ?? 0) > 0;
            if ($duplicate) {
                $duplicates[$key]--;
            }

            $error = $isCard && $type === 'income' ? 'Entrada em cartão (pagamento ou estorno)' : null;

            return [
                'date' => $row['date'],
                'description' => $row['description'],
                'amount' => $amount,
                'categoryId' => (string) ($guesses[$type][mb_strtolower($row['description'])] ?? $firstOfType[$type] ?? ''),
                'selected' => ! $duplicate && $error === null,
                'duplicate' => $duplicate,
                'error' => $error,
            ];
        })->all();
    }

    /** Quantos lançamentos iguais (data, valor e descrição) já existem na conta ou cartão. */
    private function existingCounts(): array
    {
        $dates = array_column($this->parsed, 'date');

        if ($dates === []) {
            return [];
        }

        [$sourceType, $sourceId] = explode(':', $this->source);

        return Transaction::query()
            ->where($sourceType === 'account' ? 'account_id' : 'credit_card_id', $sourceId)
            ->whereBetween('date', [min($dates), max($dates)])
            ->get(['date', 'amount', 'description'])
            ->countBy(fn (Transaction $t) => $this->key($t->date->toDateString(), $t->amount, $t->description))
            ->all();
    }

    /** Categoria mais usada em lançamentos anteriores com a mesma descrição, por tipo. */
    private function categoryGuesses(): array
    {
        $descriptions = array_unique(array_map(fn ($row) => $row['description'], $this->parsed));

        $guesses = ['income' => [], 'expense' => []];

        Transaction::query()
            ->whereIn('description', $descriptions)
            ->latestFirst()
            ->get(['description', 'amount', 'category_id'])
            ->each(function (Transaction $t) use (&$guesses) {
                $type = $t->amount > 0 ? 'income' : 'expense';
                $guesses[$type][mb_strtolower($t->description)] ??= $t->category_id;
            });

        return $guesses;
    }

    private function key(string $date, float $amount, string $description): string
    {
        return $date.'|'.number_format($amount, 2, '.', '').'|'.mb_strtolower(trim($description));
    }

    /** Fatura de cartão: se a maioria dos valores é positiva, são compras. */
    private function mostlyPositive(array $rows): bool
    {
        $positive = count(array_filter($rows, fn ($row) => $row['amount'] > 0));

        return $positive > count($rows) / 2;
    }

    private function sourceExists(string $source): bool
    {
        if (! preg_match('/^(account|card):(\d+)$/', $source, $m)) {
            return false;
        }

        return $m[1] === 'account' ? Account::whereKey($m[2])->exists() : CreditCard::whereKey($m[2])->exists();
    }

    private function firstSource(): string
    {
        $id = Account::query()->orderBy('id')->value('id');

        return $id ? "account:{$id}" : '';
    }
}
