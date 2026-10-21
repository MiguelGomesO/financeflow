<?php

namespace App\Livewire\Forms;

use App\Support\Money;
use App\Support\SampleData;
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

    public function setGoal(array $goal): void
    {
        $this->name = $goal['name'];
        $this->target = number_format($goal['target'], 2, ',', '.');
        $this->saved = '';
        $this->due = $goal['due'];
        $this->resetErrorBag();
    }

    /**
     * Valida e devolve os campos da meta. Ao editar, o valor guardado não muda
     * por aqui — ele só cresce pelos depósitos.
     */
    public function validated(bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:60'],
            'target' => ['required', $this->positiveAmount('Informe um valor maior que zero, ex.: 5.000,00.')],
            'due' => ['required', 'date_format:Y-m', function (string $attribute, mixed $value, Closure $fail) {
                if ($value < SampleData::currentMonth()) {
                    $fail('O prazo não pode estar no passado.');
                }
            }],
        ];

        if ($creating) {
            $rules['saved'] = ['nullable', function (string $attribute, mixed $value, Closure $fail) {
                if (filled($value) && Money::parse((string) $value) < 0) {
                    $fail('Valor inválido.');
                }
            }];
        }

        $this->validate($rules, [
            'name.required' => 'Dê um nome para a meta.',
            'name.max' => 'Use no máximo 60 caracteres.',
            'target.required' => 'Informe quanto quer juntar.',
            'due.required' => 'Escolha o mês do prazo.',
            'due.date_format' => 'Mês inválido.',
        ]);

        $data = [
            'name' => trim($this->name),
            'target' => Money::parse($this->target),
            'due' => $this->due,
        ];

        if ($creating) {
            $data['saved'] = filled($this->saved) ? Money::parse($this->saved) : 0;
        }

        return $data;
    }

    public function clear(): void
    {
        $this->reset();
        $this->resetErrorBag();
    }

    private function positiveAmount(string $message): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($message) {
            if (Money::parse((string) $value) <= 0) {
                $fail($message);
            }
        };
    }
}
