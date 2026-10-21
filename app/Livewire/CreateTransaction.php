<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Modal "Nova transação". Grava no banco e avisa a página com o evento
 * "transaction-created". Abrir com: $dispatch('open-modal', { name: 'new-transaction' })
 */
class CreateTransaction extends Component
{
    public string $type = 'expense';

    public string $description = '';

    /** Texto digitado, ex.: "1.234,56". */
    public string $amount = '';

    public string $categoryId = '';

    /** "account:{id}" ou "card:{id}". */
    public string $source = '';

    public string $date = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->source = ($id = Account::query()->orderBy('name')->value('id')) ? "account:{$id}" : '';
        $this->categoryId = $this->firstCategoryId();
    }

    public function updatedType(): void
    {
        $this->categoryId = $this->firstCategoryId();

        // Receita entra numa conta, nunca num cartão.
        if ($this->type === 'income' && str_starts_with($this->source, 'card:')) {
            $this->source = ($id = Account::query()->orderBy('name')->value('id')) ? "account:{$id}" : '';
        }
    }

    public function save(): void
    {
        $this->validate($this->rules(), $this->messages());

        [$sourceType, $sourceId] = explode(':', $this->source);
        $amount = Money::parse($this->amount);

        Transaction::create([
            'description' => trim($this->description),
            'amount' => $this->type === 'income' ? $amount : -$amount,
            'date' => $this->date,
            'category_id' => (int) $this->categoryId,
            'account_id' => $sourceType === 'account' ? (int) $sourceId : null,
            'credit_card_id' => $sourceType === 'card' ? (int) $sourceId : null,
        ]);

        $this->reset('description', 'amount');
        $this->resetErrorBag();

        $this->dispatch('transaction-created');
        $this->dispatch('close-modal', name: 'new-transaction');
    }

    public function render()
    {
        return view('livewire.create-transaction', [
            'categories' => Category::query()->ofType($this->type)->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
            'cards' => $this->type === 'expense' ? CreditCard::query()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
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
        ];
    }

    private function firstCategoryId(): string
    {
        return (string) (Category::query()->ofType($this->type)->orderBy('name')->value('id') ?? '');
    }
}
