<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Depósito numa meta. Não mexe no saldo da conta: marca dinheiro como
 * reservado para a meta; a conta indica onde ele está guardado.
 */
class DepositForm extends Form
{
    public string $amount = '';

    /** Id da conta onde o dinheiro está guardado ('' = não informar). */
    public string $account = '';

    public string $date = '';

    public function setDefaults(?int $accountId): void
    {
        $this->reset();
        $this->resetErrorBag();
        $this->account = (string) ($accountId ?? '');
        $this->date = now()->toDateString();
    }

    /** Valida e devolve os atributos do depósito. */
    public function validated(): array
    {
        $this->validate(
            [
                'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                    if (Money::parse((string) $value) <= 0) {
                        $fail('Informe um valor maior que zero, ex.: 200,00.');
                    }
                }],
                'account' => ['nullable', Rule::exists('accounts', 'id')->where('user_id', Auth::id())],
                'date' => ['required', 'date_format:Y-m-d'],
            ],
            [
                'amount.required' => 'Informe o valor do depósito.',
                'account.exists' => 'Escolha uma conta da lista.',
                'date.required' => 'Informe a data.',
                'date.date_format' => 'Data inválida.',
            ],
        );

        return [
            'amount' => Money::parse($this->amount),
            'account_id' => $this->account !== '' ? (int) $this->account : null,
            'date' => $this->date,
        ];
    }
}
