<?php

namespace App\Livewire\Forms;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\RecurringTransaction;
use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** Campos de uma recorrência, ao criar e ao editar. */
class RecurringForm extends Form
{
    public string $type = 'expense';

    public string $description = '';

    /** Texto digitado, ex.: "1.234,56". */
    public string $amount = '';

    public string $categoryId = '';

    /** "account:{id}" ou "card:{id}". */
    public string $source = '';

    public string $day = '';

    public string $startsOn = '';

    /** Vazio = sem data final. */
    public string $endsOn = '';

    public function setDefaults(): void
    {
        $this->reset();
        $this->startsOn = now()->toDateString();
        $this->day = (string) now()->day;
        $this->source = $this->firstAccountSource();
        $this->categoryId = $this->firstCategoryId();
        $this->resetErrorBag();
    }

    public function setRecurring(RecurringTransaction $rule): void
    {
        $this->type = $rule->amount > 0 ? 'income' : 'expense';
        $this->description = $rule->description;
        $this->amount = number_format(abs($rule->amount), 2, ',', '.');
        $this->categoryId = (string) $rule->category_id;
        $this->source = $rule->account_id ? "account:{$rule->account_id}" : "card:{$rule->credit_card_id}";
        $this->day = (string) $rule->day_of_month;
        $this->startsOn = $rule->starts_on->toDateString();
        $this->endsOn = $rule->ends_on?->toDateString() ?? '';
        $this->resetErrorBag();
    }

    /** Ao trocar o tipo: categoria do tipo novo e, para receita, uma conta (nunca cartão). */
    public function syncWithType(): void
    {
        $matches = $this->categoryId !== '' && Category::query()->whereKey($this->categoryId)->ofType($this->type)->exists();

        if (! $matches) {
            $this->categoryId = $this->firstCategoryId();
        }

        if ($this->type === 'income' && str_starts_with($this->source, 'card:')) {
            $this->source = $this->firstAccountSource();
        }
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
            'category_id' => (int) $this->categoryId,
            'account_id' => $sourceType === 'account' ? (int) $sourceId : null,
            'credit_card_id' => $sourceType === 'card' ? (int) $sourceId : null,
            'day_of_month' => (int) $this->day,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn === '' ? null : $this->endsOn,
        ];
    }

    /** Opções dos selects para o tipo atual. */
    public function options(): array
    {
        return [
            'categories' => Category::query()->ofType($this->type)->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
            'cards' => $this->type === 'expense'
                ? CreditCard::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ];
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'description' => ['required', 'string', 'max:120'],
            'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (Money::parse((string) $value) <= 0) {
                    $fail('Informe um valor maior que zero, ex.: 1.800,00.');
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
            'day' => ['required', 'integer', 'between:1,31'],
            'startsOn' => ['required', 'date_format:Y-m-d'],
            'endsOn' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:startsOn'],
        ];
    }

    protected function messages(): array
    {
        return [
            'description.required' => 'Descreva o lançamento.',
            'description.max' => 'Use no máximo 120 caracteres.',
            'amount.required' => 'Informe o valor.',
            'categoryId.required' => 'Escolha uma categoria.',
            'categoryId.exists' => 'Escolha uma categoria da lista.',
            'source.required' => 'Escolha uma conta ou cartão.',
            'day.required' => 'Informe o dia do mês.',
            'day.integer' => 'Use um dia entre 1 e 31.',
            'day.between' => 'Use um dia entre 1 e 31.',
            'startsOn.required' => 'Informe quando começa.',
            'startsOn.date_format' => 'Data inválida.',
            'endsOn.date_format' => 'Data inválida.',
            'endsOn.after_or_equal' => 'O fim não pode ser antes do início.',
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
