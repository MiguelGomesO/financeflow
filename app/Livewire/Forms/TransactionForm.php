<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use App\Support\SampleData;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TransactionForm extends Form
{
    public string $type = 'expense';

    public string $description = '';

    /** Texto digitado pelo usuário, ex.: "1.234,56". */
    public string $amount = '';

    public string $category = 'Mercado';

    public string $account = 'Conta corrente';

    public string $date = '';

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
            'category' => ['required', Rule::in(SampleData::CATEGORIES)],
            'account' => ['required', Rule::in(SampleData::ACCOUNTS)],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function messages(): array
    {
        return [
            'description.required' => 'Descreva a transação.',
            'description.max' => 'Use no máximo 120 caracteres.',
            'amount.required' => 'Informe o valor.',
            'date.required' => 'Informe a data.',
            'date.date_format' => 'Data inválida.',
            '*.in' => 'Escolha uma opção da lista.',
        ];
    }

    /** Valida e devolve a transação pronta para entrar na lista. */
    public function toTransaction(int $id): array
    {
        $this->validate();

        $amount = Money::parse($this->amount);

        return [
            'id' => $id,
            'description' => trim($this->description),
            'category' => $this->category,
            'account' => $this->account,
            'date' => $this->date,
            'amount' => $this->type === 'income' ? $amount : -$amount,
        ];
    }

    public function clear(): void
    {
        $this->reset('description', 'amount');
        $this->resetErrorBag();
    }
}
