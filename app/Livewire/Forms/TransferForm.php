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

    public string $date = '';

    /** @param  array<int>  $accountIds */
    public function setDefaults(array $accountIds): void
    {
        $this->reset();
        $this->resetErrorBag();
        $this->from = (string) ($accountIds[0] ?? '');
        $this->to = (string) ($accountIds[1] ?? '');
        $this->date = now()->toDateString();
    }

    /**
     * Valida e devolve os atributos da transferência.
     *
     * @param  array<int, float>  $balances  saldo atual de cada conta, por id
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
                'date' => ['required', 'date_format:Y-m-d'],
            ],
            [
                'from.required' => 'Escolha a conta de origem.',
                'to.required' => 'Escolha a conta de destino.',
                'to.different' => 'Escolha uma conta diferente da origem.',
                'amount.required' => 'Informe o valor.',
                'date.required' => 'Informe a data.',
                'date.date_format' => 'Data inválida.',
                '*.in' => 'Escolha uma conta da lista.',
            ],
        );

        return [
            'from_account_id' => (int) $this->from,
            'to_account_id' => (int) $this->to,
            'amount' => Money::parse($this->amount),
            'date' => $this->date,
        ];
    }
}
