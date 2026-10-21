<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** Cadastro de uma conta ou de um cartão de crédito. */
class AccountForm extends Form
{
    public const TYPES = ['Conta corrente', 'Poupança', 'Carteira', 'Investimentos'];

    /** "account" ou "card". */
    public string $kind = 'account';

    public string $name = '';

    // Conta
    public string $type = 'Conta corrente';

    public string $institution = '';

    public string $balance = '';

    // Cartão
    public string $last4 = '';

    public string $limit = '';

    public string $closeDay = '';

    public string $dueDay = '';

    public function isCard(): bool
    {
        return $this->kind === 'card';
    }

    /** Valida e devolve os campos do tipo escolhido. */
    public function validated(): array
    {
        $rules = [
            'kind' => ['required', Rule::in(['account', 'card'])],
            'name' => ['required', 'string', 'max:40'],
        ];

        if ($this->isCard()) {
            $rules += [
                'last4' => ['required', 'digits:4'],
                'limit' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                    if (Money::parse((string) $value) <= 0) {
                        $fail('Informe um limite maior que zero.');
                    }
                }],
                'closeDay' => ['required', 'integer', 'between:1,31'],
                'dueDay' => ['required', 'integer', 'between:1,31'],
            ];
        } else {
            $rules += [
                'type' => ['required', Rule::in(self::TYPES)],
                'institution' => ['nullable', 'string', 'max:40'],
                'balance' => ['nullable', 'regex:/^[\d.,\s]*$/'],
            ];
        }

        $this->validate($rules, [
            'name.required' => 'Dê um nome.',
            'name.max' => 'Use no máximo 40 caracteres.',
            'last4.required' => 'Informe os 4 últimos dígitos.',
            'last4.digits' => 'Use exatamente 4 dígitos.',
            'limit.required' => 'Informe o limite.',
            '*.required' => 'Campo obrigatório.',
            '*.integer' => 'Use um dia entre 1 e 31.',
            '*.between' => 'Use um dia entre 1 e 31.',
            'balance.regex' => 'Valor inválido.',
            'type.in' => 'Escolha um tipo da lista.',
        ]);

        if ($this->isCard()) {
            return [
                'name' => trim($this->name),
                'last4' => $this->last4,
                'bill' => 0,
                'limit' => Money::parse($this->limit),
                'close_day' => (int) $this->closeDay,
                'due_day' => (int) $this->dueDay,
            ];
        }

        return [
            'name' => trim($this->name),
            'type' => $this->type,
            'institution' => trim($this->institution) ?: $this->type,
            'balance' => Money::parse($this->balance),
            'note' => null,
        ];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
