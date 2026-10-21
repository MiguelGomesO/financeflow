<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BudgetForm extends Form
{
    public string $category = '';

    /** Texto digitado pelo usuário, ex.: "1.500,00". */
    public string $limit = '';

    /**
     * Valida e devolve o orçamento pronto para entrar na lista.
     *
     * @param  array<string>  $available  categorias que ainda não têm orçamento no mês
     */
    public function toBudget(array $available): array
    {
        $this->validate(
            [
                'category' => ['required', Rule::in($available)],
                'limit' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                    if (Money::parse((string) $value) <= 0) {
                        $fail('Informe um limite maior que zero, ex.: 500,00.');
                    }
                }],
            ],
            [
                'category.required' => 'Escolha uma categoria.',
                'category.in' => 'Essa categoria já tem orçamento neste mês.',
                'limit.required' => 'Informe o limite do mês.',
            ],
        );

        return [
            'name' => $this->category,
            'spent' => 0,
            'limit' => Money::parse($this->limit),
        ];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
