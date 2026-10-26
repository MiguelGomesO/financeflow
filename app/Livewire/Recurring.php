<?php

namespace App\Livewire;

use App\Livewire\Forms\RecurringForm;
use App\Models\RecurringTransaction;
use App\Services\RecurringGenerator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Recorrentes · FinanceFlow')]
class Recurring extends Component
{
    public RecurringForm $form;

    /** Recorrência aberta no modal (null = criando uma nova). */
    public ?int $editingId = null;

    public function mount(RecurringGenerator $recurring): void
    {
        $recurring->run();
    }

    public function create(): void
    {
        $this->editingId = null;
        $this->form->setDefaults();

        $this->dispatch('open-modal', name: 'recurring-form');
    }

    public function edit(int $id): void
    {
        $rule = RecurringTransaction::find($id);

        if ($rule === null) {
            return;
        }

        $this->editingId = $rule->id;
        $this->form->setRecurring($rule);

        $this->dispatch('open-modal', name: 'recurring-form');
    }

    public function updatedFormType(): void
    {
        $this->form->syncWithType();
    }

    public function clearEnd(): void
    {
        $this->form->endsOn = '';
    }

    public function save(RecurringGenerator $recurring): void
    {
        $data = $this->form->attributes();

        if ($this->editingId === null) {
            RecurringTransaction::create($data);
        } else {
            RecurringTransaction::whereKey($this->editingId)->first()?->update($data);
        }

        // Já lança o que venceu entre o início e hoje.
        $recurring->run();

        $this->closeForm();
    }

    /** Pausa ou retoma. Ao retomar, o que venceu durante a pausa não é lançado. */
    public function toggle(int $id): void
    {
        $rule = RecurringTransaction::find($id);

        if ($rule === null) {
            return;
        }

        $rule->update($rule->active
            ? ['active' => false]
            : ['active' => true, 'generated_until' => Carbon::yesterday()]);
    }

    /** Exclui a recorrência; os lançamentos que ela já gerou ficam. */
    public function delete(): void
    {
        RecurringTransaction::whereKey($this->editingId)->delete();

        $this->closeForm();
    }

    public function render()
    {
        $rules = RecurringTransaction::query()
            ->with(['category:id,name', 'account:id,name', 'creditCard:id,name'])
            ->orderByDesc('active')
            ->orderBy('day_of_month')
            ->orderBy('id')
            ->get();

        $active = $rules->where('active', true);

        return view('livewire.recurring', [
            'rules' => $rules,
            'editing' => $this->editingId !== null,
            'totals' => [
                'income' => (float) $active->where('amount', '>', 0)->sum('amount'),
                'expense' => (float) -$active->where('amount', '<', 0)->sum('amount'),
            ],
            ...$this->form->options(),
        ]);
    }

    private function closeForm(): void
    {
        $this->form->reset();
        $this->form->resetErrorBag();
        $this->editingId = null;

        $this->dispatch('close-modal', name: 'recurring-form');
    }
}
