<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TransferForm extends Form
{
    public string $from = '';

    public string $to = '';

    public string $amount = '';

    /**
     * Valida e devolve ids e valor da transferência.
     *
     * @param  array<int, float>  $balances  saldo de cada conta, por id
     */
    public function validated(array $balances): array
    {
        $ids = array_keys($balances);

        $this->validate(
            [
                'from' => ['required', Rule::in($ids)],
                'to' => ['required', Rule::in($ids), 'different:from'],
                'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) use ($balances) {
                    $amount = Money::parse((string) $value);

                    if ($amount <= 0) {
                        $fail('Informe um valor maior que zero.');
                    } elseif ($amount > ($balances[(int) $this->from] ?? 0)) {
                        $fail('Saldo insuficiente na conta de origem.');
                    }
                }],
            ],
            [
                'from.required' => 'Escolha a conta de origem.',
                'to.required' => 'Escolha a conta de destino.',
                'to.different' => 'Escolha uma conta diferente da origem.',
                'amount.required' => 'Informe o valor.',
                '*.in' => 'Escolha uma conta da lista.',
            ],
        );

        return ['from' => (int) $this->from, 'to' => (int) $this->to, 'amount' => Money::parse($this->amount)];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
