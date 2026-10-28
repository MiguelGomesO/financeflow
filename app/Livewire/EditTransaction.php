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

    /** "Parcela 2 de 10" quando a transação é uma parcela; null nas demais. */
    public ?string $installmentInfo = null;

    #[On('edit-transaction')]
    public function load(int $id): void
    {
        $transaction = Transaction::find($id);

        if ($transaction === null) {
            return;
        }

        $this->transactionId = $transaction->id;
        $this->installmentInfo = $transaction->installment_group
            ? "Parcela {$transaction->installment_number} de {$transaction->installment_total}"
            : null;
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

    /**
     * Exclui parcelas da mesma compra: "following" apaga esta e as próximas, "all" apaga todas.
     * O grupo vem do banco, não do navegador.
     */
    public function deleteInstallments(string $scope): void
    {
        $transaction = Transaction::find($this->transactionId);

        if ($transaction?->installment_group === null || ! in_array($scope, ['following', 'all'], true)) {
            return;
        }

        Transaction::query()
            ->where('installment_group', $transaction->installment_group)
            ->when($scope === 'following', fn ($q) => $q->where('installment_number', '>=', $transaction->installment_number))
            ->delete();

        $this->finish();
    }

    public function render()
    {
        return view('livewire.edit-transaction', $this->form->options());
    }

    private function finish(): void
    {
        $this->transactionId = null;
        $this->installmentInfo = null;

        $this->dispatch('transactions-changed');
        $this->dispatch('close-modal', name: 'edit-transaction');
    }
}
