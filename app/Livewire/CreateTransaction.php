<?php

namespace App\Livewire;

use App\Livewire\Forms\TransactionForm;
use App\Models\Transaction;
use App\Support\Installments;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $attributes = $this->form->attributes();
        $count = $this->form->installmentCount();

        $count > 1 ? $this->createInstallments($attributes, $count) : Transaction::create($attributes);

        $this->form->clearInputs();
        $this->dispatch('transactions-changed');

        if ($this->inline) {
            $this->dispatch('transaction-saved');
        } else {
            $this->dispatch('close-modal', name: 'new-transaction');
        }
    }

    /** O valor digitado é o total da compra: vira uma transação por parcela, uma por fatura. */
    private function createInstallments(array $attributes, int $count): void
    {
        $group = (string) Str::uuid();

        DB::transaction(function () use ($attributes, $count, $group) {
            foreach (Installments::schedule(-$attributes['amount'], $count, Carbon::parse($attributes['date'])) as $parcel) {
                Transaction::create([
                    ...$attributes,
                    'amount' => -$parcel['amount'],
                    'date' => $parcel['date'],
                    'installment_group' => $group,
                    'installment_number' => $parcel['number'],
                    'installment_total' => $count,
                ]);
            }
        });
    }

    public function render()
    {
        return view('livewire.create-transaction', $this->form->options());
    }
}
