<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DepositForm extends Form
{
    /** Contas de onde um depósito pode sair (cartão de crédito não entra). */
    public const ACCOUNTS = ['Conta corrente', 'Poupança', 'Carteira'];

    public string $amount = '';

    public string $account = 'Conta corrente';

    /** Valida e devolve o valor do depósito e a conta de origem. */
    public function validated(): array
    {
        $this->validate(
            [
                'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                    if (Money::parse((string) $value) <= 0) {
                        $fail('Informe um valor maior que zero, ex.: 200,00.');
                    }
                }],
                'account' => ['required', Rule::in(self::ACCOUNTS)],
            ],
            [
                'amount.required' => 'Informe o valor do depósito.',
                'account.in' => 'Escolha uma conta da lista.',
            ],
        );

        return ['amount' => Money::parse($this->amount), 'account' => $this->account];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
