<?php

namespace App\Livewire\Forms;

use App\Models\Account;
use App\Models\CreditCard;
use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** Cadastro e edição de uma conta ou de um cartão de crédito. */
class AccountForm extends Form
{
    public const TYPES = ['Conta corrente', 'Poupança', 'Carteira', 'Investimentos'];

    /** "account" ou "card". */
    public string $kind = 'account';

    public string $name = '';

    // Conta
    public string $type = 'Conta corrente';

    public string $institution = '';

    public string $note = '';

    public string $balance = '';

    // Cartão
    public string $last4 = '';

    public string $limit = '';

    /** Limite usado informado à mão; vazio = usa a fatura em aberto. */
    public string $usedLimit = '';

    public string $closeDay = '';

    public string $dueDay = '';

    public function isCard(): bool
    {
        return $this->kind === 'card';
    }

    public function setAccount(Account $account): void
    {
        $this->clear();
        $this->kind = 'account';
        $this->name = $account->name;
        $this->type = $account->type;
        $this->institution = (string) $account->institution;
        $this->note = (string) $account->note;
        $this->balance = number_format($account->opening_balance, 2, ',', '.');
    }

    public function setCard(CreditCard $card): void
    {
        $this->clear();
        $this->kind = 'card';
        $this->name = $card->name;
        $this->last4 = $card->last4;
        $this->limit = number_format($card->limit, 2, ',', '.');
        $this->usedLimit = $card->used_limit === null ? '' : number_format($card->used_limit, 2, ',', '.');
        $this->closeDay = (string) $card->close_day;
        $this->dueDay = (string) $card->due_day;
    }

    /** Valida e devolve os atributos do model do tipo escolhido. */
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
                'usedLimit' => ['nullable', 'regex:/^[\d.,\s]*$/'],                'closeDay' => ['required', 'integer', 'between:1,31'],
                'dueDay' => ['required', 'integer', 'between:1,31'],
            ];
        } else {
            $rules += [
                'type' => ['required', Rule::in(self::TYPES)],
                'institution' => ['nullable', 'string', 'max:40'],
                'note' => ['nullable', 'string', 'max:120'],
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
            'usedLimit.regex' => 'Valor inválido.',            'type.in' => 'Escolha um tipo da lista.',
            'note.max' => 'Use no máximo 120 caracteres.',
        ]);

        if ($this->isCard()) {
            return [
                'name' => trim($this->name),
                'last4' => $this->last4,
                'limit' => Money::parse($this->limit),
                'used_limit' => trim($this->usedLimit) === '' ? null : Money::parse($this->usedLimit),
                'close_day' => (int) $this->closeDay,
                'due_day' => (int) $this->dueDay,
            ];
        }

        return [
            'name' => trim($this->name),
            'type' => $this->type,
            'institution' => trim($this->institution) ?: null,
            'note' => trim($this->note) ?: null,
            'opening_balance' => Money::parse($this->balance),
        ];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
