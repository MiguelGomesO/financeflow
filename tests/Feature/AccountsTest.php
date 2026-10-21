<?php

namespace Tests\Feature;

use App\Livewire\Accounts;
use Livewire\Livewire;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    public function test_page_renders_accounts_cards_and_totals(): void
    {
        $this->get(route('accounts'))
            ->assertOk()
            ->assertSee('Contas e cartões')
            ->assertSee('R$ 12.840,55')     // saldo em contas
            ->assertSee('R$ 1.899,30')      // faturas em aberto
            ->assertSee('R$ 10.941,25')     // patrimônio líquido
            ->assertSee('Cartão principal')
            ->assertSee('28% do limite de R$ 5.000,00')
            ->assertSee('Vence dia 10');
    }

    public function test_adds_an_account(): void
    {
        Livewire::test(Accounts::class)
            ->call('openCreate')
            ->assertDispatched('open-modal', name: 'new-account')
            ->set('form.name', 'Corretora')
            ->set('form.type', 'Investimentos')
            ->set('form.balance', '2.000,00')
            ->call('saveAccount')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'new-account')
            ->assertSee('Corretora')
            ->assertSee('R$ 14.840,55');
    }

    public function test_adds_a_credit_card(): void
    {
        Livewire::test(Accounts::class)
            ->set('form.kind', 'card')
            ->set('form.name', 'Cartão viagem')
            ->set('form.last4', '1234')
            ->set('form.limit', '3.000')
            ->set('form.closeDay', '5')
            ->set('form.dueDay', '12')
            ->call('saveAccount')
            ->assertHasNoErrors()
            ->assertSee('Cartão viagem')
            ->assertSee('Vence dia 12');
    }

    public function test_validates_credit_card_fields(): void
    {
        Livewire::test(Accounts::class)
            ->set('form.kind', 'card')
            ->set('form.name', '')
            ->set('form.last4', '12')
            ->set('form.limit', '0')
            ->set('form.closeDay', '40')
            ->call('saveAccount')
            ->assertHasErrors(['form.name', 'form.last4', 'form.limit', 'form.closeDay', 'form.dueDay']);
    }

    public function test_transfers_between_accounts(): void
    {
        Livewire::test(Accounts::class)
            ->call('openTransfer')
            ->assertSet('transfer.from', '1')
            ->assertSet('transfer.to', '2')
            ->set('transfer.amount', '1.000,55')
            ->call('saveTransfer')
            ->assertHasNoErrors()
            ->assertSee('R$ 3.180,00')      // conta corrente
            ->assertSee('R$ 9.420,55')      // poupança
            ->assertSee('R$ 12.840,55');    // total não muda
    }

    public function test_transfer_rejects_same_account_and_insufficient_balance(): void
    {
        Livewire::test(Accounts::class)
            ->set('transfer.from', '3')
            ->set('transfer.to', '3')
            ->set('transfer.amount', '500')
            ->call('saveTransfer')
            ->assertHasErrors(['transfer.to', 'transfer.amount']);
    }
}
