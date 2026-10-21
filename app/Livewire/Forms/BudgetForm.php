<?php

namespace App\Livewire\Forms;

use App\Models\Budget;
use App\Support\Money;
use Closure;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BudgetForm extends Form
{
    public string $categoryId = '';

    /** Texto digitado pelo usuário, ex.: "1.500,00". */
    public string $limit = '';

    public function setBudget(Budget $budget): void
    {
        $this->clear();
        $this->categoryId = (string) $budget->category_id;
        $this->limit = number_format($budget->limit, 2, ',', '.');
    }

    /**
     * Valida e devolve os atributos do orçamento.
     *
     * @param  int|null  $ignoreId  orçamento em edição (a categoria dele não conta como repetida)
     */
    public function validated(string $month, ?int $ignoreId = null): array
    {
        $this->validate(
            [
                'categoryId' => [
                    'required',
                    Rule::exists('categories', 'id')->where('type', 'expense'),
                    Rule::unique('budgets', 'category_id')->where('month', $month)->ignore($ignoreId),
                ],
                'limit' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                    if (Money::parse((string) $value) <= 0) {
                        $fail('Informe um limite maior que zero, ex.: 500,00.');
                    }
                }],
            ],
            [
                'categoryId.required' => 'Escolha uma categoria.',
                'categoryId.exists' => 'Escolha uma categoria de despesa.',
                'categoryId.unique' => 'Essa categoria já tem orçamento neste mês.',
                'limit.required' => 'Informe o limite do mês.',
            ],
        );

        return [
            'category_id' => (int) $this->categoryId,
            'month' => $month,
            'limit' => Money::parse($this->limit),
        ];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
