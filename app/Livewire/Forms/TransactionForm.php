<?php

namespace App\Livewire\Forms;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Tag;
use App\Models\Transaction;
use App\Support\Installments;
use App\Support\Money;
use App\Support\Receipts;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** Campos de uma transação, usados ao criar e ao editar. */
class TransactionForm extends Form
{
    public string $type = 'expense';

    public string $description = '';

    /** Texto digitado, ex.: "1.234,56". */
    public string $amount = '';

    public string $categoryId = '';

    /** "account:{id}" ou "card:{id}". */
    public string $source = '';

    public string $date = '';

    /** Etiquetas separadas por vírgula, ex.: "viagem, trabalho". */
    public string $tags = '';

    /** Comprovante recém-escolhido (envio temporário do Livewire). */
    public $attachment = null;

    /** Número de parcelas (só no cartão e só ao criar); "1" = à vista. */
    public string $installments = '1';

    public function setDefaults(): void
    {
        $this->reset();
        $this->date = now()->toDateString();
        $this->source = $this->firstAccountSource();
        $this->categoryId = $this->firstCategoryId();
        $this->resetErrorBag();
    }

    public function setTransaction(Transaction $transaction): void
    {
        $this->type = $transaction->amount > 0 ? 'income' : 'expense';
        $this->description = $transaction->description;
        $this->amount = number_format(abs($transaction->amount), 2, ',', '.');
        $this->categoryId = (string) $transaction->category_id;
        $this->source = $transaction->account_id
            ? "account:{$transaction->account_id}"
            : "card:{$transaction->credit_card_id}";
        $this->date = $transaction->date->toDateString();
        $this->tags = $transaction->tags->pluck('name')->implode(', ');
        $this->attachment = null;
        $this->resetErrorBag();
    }

    /** Ao trocar o tipo: categoria do tipo novo e, para receita, uma conta (nunca cartão). */
    public function syncWithType(): void
    {
        $categoryMatches = $this->categoryId !== '' && Category::query()
            ->whereKey($this->categoryId)->ofType($this->type)->exists();

        if (! $categoryMatches) {
            $this->categoryId = $this->firstCategoryId();
        }

        if ($this->type === 'income' && str_starts_with($this->source, 'card:')) {
            $this->source = $this->firstAccountSource();
        }
    }

    /**
     * Etiquetas digitadas, sem espaços sobrando nem repetidas ("Viagem" = "viagem").
     *
     * @return list<string>
     */
    public function tagNames(): array
    {
        return collect(preg_split('/[,;]+/', $this->tags))
            ->map(fn (string $tag) => trim(preg_replace('/\s+/u', ' ', $tag)))
            ->filter()
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->values()
            ->all();
    }

    /** Parcelas efetivas: só despesa no cartão parcela; nos demais casos vale 1. */
    public function installmentCount(): int
    {
        $onCard = $this->type === 'expense' && str_starts_with($this->source, 'card:');

        return $onCard ? max(1, (int) $this->installments) : 1;
    }

    /** Valida e devolve os atributos do model. */
    public function attributes(): array
    {
        $this->validate();

        [$sourceType, $sourceId] = explode(':', $this->source);
        $amount = Money::parse($this->amount);

        return [
            'description' => trim($this->description),
            'amount' => $this->type === 'income' ? $amount : -$amount,
            'date' => $this->date,
            'category_id' => (int) $this->categoryId,
            'account_id' => $sourceType === 'account' ? (int) $sourceId : null,
            'credit_card_id' => $sourceType === 'card' ? (int) $sourceId : null,
        ];
    }

    /** Opções dos selects para o tipo atual. */
    public function options(): array
    {
        return [
            'categories' => Category::query()->ofType($this->type)->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
            'tagSuggestions' => Tag::query()->has('transactions')->withCount('transactions')
                ->orderByDesc('transactions_count')->orderByRaw('lower(name)')->limit(8)->pluck('name')->all(),
            'cards' => $this->type === 'expense'
                ? CreditCard::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    public function clearInputs(): void
    {
        $this->reset('description', 'amount', 'installments', 'tags', 'attachment');
        $this->resetErrorBag();
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'description' => ['required', 'string', 'max:120'],
            'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (Money::parse((string) $value) <= 0) {
                    $fail('Informe um valor maior que zero, ex.: 25,90.');
                }
            }],
            'categoryId' => ['required', Rule::exists('categories', 'id')->where('type', $this->type)],
            'source' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (! preg_match('/^(account|card):(\d+)$/', (string) $value, $m)) {
                    $fail('Escolha uma conta ou cartão.');

                    return;
                }

                if ($m[1] === 'card' && $this->type === 'income') {
                    $fail('Receitas entram numa conta, não num cartão.');
                } elseif ($m[1] === 'account' && ! Account::whereKey($m[2])->exists()) {
                    $fail('Conta não encontrada.');
                } elseif ($m[1] === 'card' && ! CreditCard::whereKey($m[2])->exists()) {
                    $fail('Cartão não encontrado.');
                }
            }],
            'date' => ['required', 'date_format:Y-m-d'],
            'installments' => ['required', 'integer', 'between:1,'.Installments::MAX],
            'tags' => ['nullable', 'string', 'max:400', function (string $attribute, mixed $value, Closure $fail) {
                $names = $this->tagNames();

                if (count($names) > Tag::MAX_PER_TRANSACTION) {
                    $fail('Use no máximo '.Tag::MAX_PER_TRANSACTION.' etiquetas.');
                } elseif (collect($names)->contains(fn (string $name) => mb_strlen($name) > Tag::MAX_LENGTH)) {
                    $fail('Cada etiqueta pode ter no máximo '.Tag::MAX_LENGTH.' caracteres.');
                }
            }],
            'attachment' => ['nullable', 'file', 'mimes:'.Receipts::MIMES, 'max:'.Receipts::MAX_KB],
        ];
    }

    protected function messages(): array
    {
        return [
            'description.required' => 'Descreva a transação.',
            'description.max' => 'Use no máximo 120 caracteres.',
            'amount.required' => 'Informe o valor.',
            'categoryId.required' => 'Escolha uma categoria.',
            'categoryId.exists' => 'Escolha uma categoria da lista.',
            'source.required' => 'Escolha uma conta ou cartão.',
            'date.required' => 'Informe a data.',
            'date.date_format' => 'Data inválida.',
            'attachment.mimes' => 'Envie JPG, PNG, WebP ou PDF.',
            'attachment.max' => 'O comprovante pode ter no máximo 5 MB.',
            'attachment.uploaded' => 'Não foi possível enviar o comprovante (máximo de 5 MB).',
            'attachment.*' => 'Comprovante inválido.',
            'installments.*' => 'Escolha de 1 a '.Installments::MAX.' parcelas.',
        ];
    }

    private function firstAccountSource(): string
    {
        $id = Account::query()->orderBy('id')->value('id');

        return $id ? "account:{$id}" : '';
    }

    private function firstCategoryId(): string
    {
        return (string) (Category::query()->ofType($this->type)->orderBy('name')->value('id') ?? '');
    }
}
