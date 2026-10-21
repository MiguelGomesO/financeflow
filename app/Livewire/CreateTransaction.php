<?php

namespace App\Livewire;

use App\Livewire\Forms\TransactionForm;
use App\Models\Transaction;
use Livewire\Component;

/**
 * Formulário "Nova transação": em modal (padrão) ou embutido na página (inline).
 * Modal: abrir com $dispatch('open-modal', { name: 'new-transaction' }).
 * Ao salvar, avisa as páginas com o evento "transactions-changed".
 */
class CreateTransaction extends Component
{
    public TransactionForm $form;

    public bool $inline = false;

    public function mount(bool $inline = false): void
    {
        $this->inline = $inline;
        $this->form->setDefaults();
    }

    public function updated(string $property): void
    {
        if ($property === 'form.type') {
            $this->form->syncWithType();
        }
    }

    public function save(): void
    {
        Transaction::create($this->form->attributes());

        $this->form->clearInputs();
        $this->dispatch('transactions-changed');

        if ($this->inline) {
            $this->dispatch('transaction-saved');
        } else {
            $this->dispatch('close-modal', name: 'new-transaction');
        }
    }

    public function render()
    {
        return view('livewire.create-transaction', $this->form->options());
    }
}
