<?php

namespace App\Livewire;

use App\Livewire\Forms\AccountForm;
use App\Livewire\Forms\TransferForm;
use App\Support\SampleData;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contas e cartões · FinanceFlow')]
class Accounts extends Component
{
    public AccountForm $form;

    public TransferForm $transfer;

    /** Por enquanto contas e cartões vivem no estado do componente (sem banco). */
    public array $accounts = [];

    public array $cards = [];

    public function mount(): void
    {
        $this->accounts = SampleData::accounts();
        $this->cards = SampleData::cards();
    }

    public function openCreate(): void
    {
        $this->form->clear();

        $this->dispatch('open-modal', name: 'new-account');
    }

    public function openTransfer(): void
    {
        $this->transfer->clear();

        $ids = array_column($this->accounts, 'id');
        $this->transfer->from = (string) ($ids[0] ?? '');
        $this->transfer->to = (string) ($ids[1] ?? '');

        $this->dispatch('open-modal', name: 'transfer');
    }

    public function saveAccount(): void
    {
        $data = $this->form->validated();

        if ($this->form->isCard()) {
            $this->cards[] = ['id' => $this->nextId($this->cards)] + $data;
        } else {
            $this->accounts[] = ['id' => $this->nextId($this->accounts)] + $data;
        }

        $this->form->clear();
        $this->dispatch('close-modal', name: 'new-account');
    }

    public function saveTransfer(): void
    {
        $data = $this->transfer->validated(array_column($this->accounts, 'balance', 'id'));

        foreach ($this->accounts as &$account) {
            if ($account['id'] === $data['from']) {
                $account['balance'] = round($account['balance'] - $data['amount'], 2);
            } elseif ($account['id'] === $data['to']) {
                $account['balance'] = round($account['balance'] + $data['amount'], 2);
            }
        }

        $this->transfer->clear();
        $this->dispatch('close-modal', name: 'transfer');
    }

    public function render()
    {
        $balance = array_sum(array_column($this->accounts, 'balance'));
        $bills = array_sum(array_column($this->cards, 'bill'));

        return view('livewire.accounts', [
            'accountTypes' => AccountForm::TYPES,
            'totals' => [
                'balance' => $balance,
                'bills' => $bills,
                'net' => $balance - $bills,
            ],
        ]);
    }

    private function nextId(array $rows): int
    {
        return max(array_column($rows, 'id') ?: [0]) + 1;
    }
}
