<?php

namespace App\Livewire;

use App\Livewire\Forms\TransactionForm;
use App\Models\Tag;
use App\Models\Transaction;
use App\Support\Receipts;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Modal de edição. Abrir com: $dispatch('edit-transaction', { id: 5 })
 * Ao salvar ou excluir, avisa as páginas com o evento "transactions-changed".
 */
class EditTransaction extends Component
{
    use WithFileUploads;

    public TransactionForm $form;

    public ?int $transactionId = null;

    /** "Parcela 2 de 10" quando a transação é uma parcela; null nas demais. */
    public ?string $installmentInfo = null;

    /** A transação já tem comprovante guardado (o arquivo novo, se houver, fica em form.attachment). */
    public bool $hasAttachment = false;

    /** O usuário pediu para tirar o comprovante atual. */
    public bool $removeAttachment = false;

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
        $this->hasAttachment = $transaction->attachment_path !== null;
        $this->removeAttachment = false;
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
            $attributes = $this->form->attributes();
            $previous = $transaction->attachment_path;

            if ($this->form->attachment) {
                $attributes['attachment_path'] = Receipts::store($this->form->attachment);
            } elseif ($this->removeAttachment) {
                $attributes['attachment_path'] = null;
            }

            DB::transaction(function () use ($transaction, $attributes) {
                $transaction->update($attributes);
                $transaction->tags()->sync(Tag::idsFor($this->form->tagNames()));
            });

            if (array_key_exists('attachment_path', $attributes)) {
                Receipts::releaseIfUnused($previous);
            }
        }

        $this->finish();
    }

    public function removeReceipt(): void
    {
        $this->removeAttachment = true;
        $this->form->attachment = null;
    }

    public function delete(): void
    {
        // Pelo model, para o comprovante sem uso ser apagado junto.
        Transaction::find($this->transactionId)?->delete();

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
            ->get()
            ->each->delete();

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
        $this->hasAttachment = false;
        $this->removeAttachment = false;

        $this->dispatch('transactions-changed');
        $this->dispatch('close-modal', name: 'edit-transaction');
    }
}
