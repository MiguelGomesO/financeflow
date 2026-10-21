<?php

namespace App\Livewire\Forms;

use App\Models\Goal;
use App\Support\Money;
use Closure;
use Livewire\Form;

class GoalForm extends Form
{
    public string $name = '';

    /** Valores digitados pelo usuário, ex.: "15.000,00". */
    public string $target = '';

    public string $saved = '';

    /** Mês-limite, "Y-m" (input type="month"). */
    public string $due = '';

    public function setDefaults(): void
    {
        $this->clear();
        $this->due = now()->addYear()->format('Y-m');
    }

    public function setGoal(Goal $goal): void
    {
        $this->clear();
        $this->name = $goal->name;
        $this->target = number_format($goal->target, 2, ',', '.');
        $this->due = $goal->due_month;
    }

    /**
     * Valida e devolve os atributos da meta, mais "initial" (valor já guardado,
     * só ao criar) para virar o primeiro depósito. Ao editar, o valor guardado
     * não muda por aqui — ele só cresce pelos depósitos.
     */
    public function validated(bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:60'],
            'target' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (Money::parse((string) $value) <= 0) {
                    $fail('Informe um valor maior que zero, ex.: 5.000,00.');
                }
            }],
            'due' => ['required', 'date_format:Y-m', function (string $attribute, mixed $value, Closure $fail) use ($creating) {
                // Ao editar, uma meta já vencida pode manter o prazo antigo.
                if ($creating && $value < now()->format('Y-m')) {
                    $fail('O prazo não pode estar no passado.');
                }
            }],
        ];

        if ($creating) {
            $rules['saved'] = ['nullable', 'regex:/^[\d.,\s]*$/'];
        }

        $this->validate($rules, [
            'name.required' => 'Dê um nome para a meta.',
            'name.max' => 'Use no máximo 60 caracteres.',
            'target.required' => 'Informe quanto quer juntar.',
            'due.required' => 'Escolha o mês do prazo.',
            'due.date_format' => 'Mês inválido.',
            'saved.regex' => 'Valor inválido.',
        ]);

        return [
            'name' => trim($this->name),
            'target' => Money::parse($this->target),
            'due_month' => $this->due,
            'initial' => $creating ? Money::parse($this->saved) : 0,
        ];
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }
}
