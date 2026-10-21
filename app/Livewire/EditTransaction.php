<?php

namespace App\Livewire;

use App\Livewire\Forms\TransactionForm;
use App\Models\Transaction;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de edição. Abrir com: $dispatch('edit-transaction', { id: 5 })
 * Ao salvar ou excluir, avisa as páginas com o evento "transactions-changed".
 */
class EditTransaction extends Component
{
    public TransactionForm $form;

    public ?int $transactionId = null;

    #[On('edit-transaction')]
    public function load(int $id): void
    {
        $transaction = Transaction::find($id);

        if ($transaction === null) {
            return;
        }

        $this->transactionId = $transaction->id;
        $this->form->setTransaction($transaction);

        $this->dispatch('open-modal', name: 'edit-transaction');
    }

    public function updated(string $property): void
    {
        if ($property === 'form.type') {
            $this->form->syncWithType();
        }
    }

    public function save(): void
    {
        $transaction = Transaction::find($this->transactionId);

        if ($transaction !== null) {
            $transaction->update($this->form->attributes());
        }

        $this->finish();
    }

    public function delete(): void
    {
        Transaction::whereKey($this->transactionId)->delete();

        $this->finish();
    }

    public function render()
    {
        return view('livewire.edit-transaction', $this->form->options());
    }

    private function finish(): void
    {
        $this->transactionId = null;

        $this->dispatch('transactions-changed');
        $this->dispatch('close-modal', name: 'edit-transaction');
    }
}
