<?php

namespace App\Livewire;

use App\Livewire\Forms\AccountForm;
use App\Livewire\Forms\TransferForm;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\GoalDeposit;
use App\Models\Transfer;
use App\Support\Money;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Contas e cartões · FinanceFlow')]
class Accounts extends Component
{
    public AccountForm $form;

    public TransferForm $transfer;

    /** O que está aberto no modal de edição: "account" ou "card" (null = criando). */
    public ?string $editingKind = null;

    public ?int $editingId = null;

    /** Cartão que está recebendo a fatura atual informada, e o texto digitado. */
    public ?int $billCardId = null;

    public string $billAmount = '';

    /** Motivo de uma exclusão recusada, mostrado no modal. */
    public ?string $deleteError = null;

    public function openCreate(): void
    {
        $this->resetEditing();
        $this->form->clear();

        $this->dispatch('open-modal', name: 'account-form');
    }

    public function editAccount(int $id): void
    {
        $account = Account::find($id);

        if ($account === null) {
            return;
        }

        $this->resetEditing();
        $this->editingKind = 'account';
        $this->editingId = $account->id;
        $this->form->setAccount($account);

        $this->dispatch('open-modal', name: 'account-form');
    }

    public function editCard(int $id): void
    {
        $card = CreditCard::find($id);

        if ($card === null) {
            return;
        }

        $this->resetEditing();
        $this->editingKind = 'card';
        $this->editingId = $card->id;
        $this->form->setCard($card);

        $this->dispatch('open-modal', name: 'account-form');
    }

    /** Abre o modal para informar a fatura atual do cartão. */
    public function addBill(int $id): void
    {
        $card = CreditCard::find($id);

        if ($card === null) {
            return;
        }

        $bill = $card->billOverride();

        $this->billCardId = $card->id;
        $this->billAmount = $bill === null ? '' : number_format($bill, 2, ',', '.');
        $this->resetErrorBag();

        $this->dispatch('open-modal', name: 'card-bill');
    }

    /** Grava a fatura informada; em branco, volta a somar as compras do ciclo. */
    public function saveBill(): void
    {
        $this->validate(
            ['billAmount' => ['nullable', 'regex:/^[\d.,\s]*$/']],
            ['billAmount.regex' => 'Valor inválido.'],
        );

        $card = CreditCard::find($this->billCardId);

        if ($card !== null) {
            $card->update(['bill_amount' => trim($this->billAmount) === '' ? null : Money::parse($this->billAmount)]);
        }

        $this->reset('billCardId', 'billAmount');

        $this->dispatch('close-modal', name: 'card-bill');
    }

    public function saveAccount(): void
    {
        $data = $this->form->validated();

        match (true) {
            $this->editingKind === 'account' => Account::whereKey($this->editingId)->first()?->update($data),
            $this->editingKind === 'card' => CreditCard::whereKey($this->editingId)->first()?->update($data),
            $this->form->isCard() => CreditCard::create($data),
            default => Account::create($data),
        };

        $this->closeAccountForm();
    }

    public function deleteEditing(): void
    {
        if ($this->editingKind === 'account') {
            $account = Account::find($this->editingId);

            if ($account?->hasActivity()) {
                $this->deleteError = 'Esta conta tem transações ou transferências. Exclua ou mova esses lançamentos antes.';

                return;
            }

            $account?->delete();
        }

        if ($this->editingKind === 'card') {
            $card = CreditCard::find($this->editingId);

            if ($card?->transactions()->exists()) {
                $this->deleteError = 'Este cartão tem transações. Exclua ou mova esses lançamentos antes.';

                return;
            }

            $card?->delete();
        }

        $this->closeAccountForm();
    }

    public function openTransfer(): void
    {
        $this->transfer->setDefaults(Account::query()->orderBy('id')->pluck('id')->all());

        $this->dispatch('open-modal', name: 'transfer');
    }

    public function saveTransfer(): void
    {
        $balances = Account::query()->withBalance()->get()->mapWithKeys(
            fn (Account $account) => [$account->id => $account->balance]
        )->all();

        Transfer::create($this->transfer->validated($balances));

        $this->transfer->reset();
        $this->dispatch('close-modal', name: 'transfer');
    }

    public function deleteTransfer(int $id): void
    {
        Transfer::whereKey($id)->delete();
    }

    public function render()
    {
        $accounts = Account::query()->withBalance()->orderBy('id')->get();
        $cards = CreditCard::query()->orderBy('id')->get();
        $bills = $cards->mapWithKeys(fn (CreditCard $card) => [$card->id => $card->openBill()]);
        $used = $cards->mapWithKeys(fn (CreditCard $card) => [$card->id => $card->usedLimit()]);
        $upcoming = $cards->mapWithKeys(fn (CreditCard $card) => [$card->id => $card->upcomingBills()]);
        $balance = round($accounts->sum('balance') - GoalDeposit::totalSaved(), 2);
        $billsTotal = round($bills->sum(), 2);

        return view('livewire.accounts', [
            'accounts' => $accounts,
            'cards' => $cards,
            'bills' => $bills,
            'used' => $used,
            'upcoming' => $upcoming,
            'transfers' => Transfer::query()
                ->with(['fromAccount:id,name', 'toAccount:id,name'])
                ->orderByDesc('date')->orderByDesc('id')
                ->limit(5)
                ->get(),
            'accountTypes' => AccountForm::TYPES,
            'totals' => [
                'balance' => $balance,
                'bills' => $billsTotal,
                'net' => $balance - $billsTotal,
            ],
        ]);
    }

    private function closeAccountForm(): void
    {
        $this->form->clear();
        $this->resetEditing();

        $this->dispatch('close-modal', name: 'account-form');
    }

    private function resetEditing(): void
    {
        $this->editingKind = null;
        $this->editingId = null;
        $this->deleteError = null;
    }
}
