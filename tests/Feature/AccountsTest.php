<?php

namespace Tests\Feature;

use App\Livewire\Accounts;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    private function expense(array $attributes): Transaction
    {
        return Transaction::factory()->create([
            'category_id' => Category::where('name', 'Mercado')->value('id'),
            ...$attributes,
        ]);
    }

    public function test_renders_empty_state(): void
    {
        $this->get(route('accounts'))
            ->assertOk()
            ->assertSee('Nenhuma conta cadastrada')
            ->assertSee('Nenhum cartão cadastrado')
            ->assertDontSee('Transferências recentes');
    }

    public function test_balance_includes_transactions_and_transfers(): void
    {
        $checking = Account::factory()->create(['name' => 'Corrente', 'opening_balance' => 1000]);
        $savings = Account::factory()->create(['name' => 'Reserva', 'opening_balance' => 0]);

        $this->expense(['account_id' => $checking->id, 'amount' => -200]);
        Transfer::factory()->create(['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 300]);

        $this->assertSame(500.0, $checking->balance);
        $this->assertSame(300.0, $savings->balance);

        Livewire::test(Accounts::class)
            ->assertSee('R$ 500,00')
            ->assertSee('R$ 300,00')
            ->assertSee('R$ 800,00')
            ->assertSee('Transferências recentes');
    }

    public function test_card_bill_counts_only_the_open_cycle(): void
    {
        // Fecha dia 3: em 21/10 o ciclo aberto vai de 04/10 a 03/11.
        $card = CreditCard::factory()->create(['close_day' => 3, 'limit' => 1000]);

        $this->expense(['account_id' => null, 'credit_card_id' => $card->id, 'amount' => -250, 'date' => '2026-10-05']);
        $this->expense(['account_id' => null, 'credit_card_id' => $card->id, 'amount' => 50, 'date' => '2026-10-10']); // estorno
        $this->expense(['account_id' => null, 'credit_card_id' => $card->id, 'amount' => -999, 'date' => '2026-10-02']); // fatura anterior

        $this->assertSame(200.0, $card->currentBill());

        Livewire::test(Accounts::class)
            ->assertSee('R$ 200,00')
            ->assertSee('20% do limite de R$ 1.000,00')
            ->assertSee('R$ 800,00 livres');
    }

    public function test_card_cycle_handles_short_months(): void
    {
        $card = CreditCard::factory()->make(['close_day' => 31]);

        [$start, $end] = $card->currentCycle(Carbon::parse('2026-02-10'));
        $this->assertSame('2026-02-01', $start->toDateString());
        $this->assertSame('2026-02-28', $end->toDateString());

        $card->close_day = 10;
        [$start, $end] = $card->currentCycle(Carbon::parse('2026-01-15'));
        $this->assertSame('2026-01-11', $start->toDateString());
        $this->assertSame('2026-02-10', $end->toDateString());
    }

    public function test_adds_an_account(): void
    {
        Livewire::test(Accounts::class)
            ->call('openCreate')
            ->assertDispatched('open-modal', name: 'account-form')
            ->set('form.name', 'Corretora')
            ->set('form.type', 'Investimentos')
            ->set('form.balance', '2.000,00')
            ->set('form.note', 'Tesouro Selic')
            ->call('saveAccount')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'account-form')
            ->assertSee('Corretora')
            ->assertSee('Tesouro Selic');

        $this->assertDatabaseHas('accounts', ['name' => 'Corretora', 'type' => 'Investimentos', 'opening_balance' => 2000, 'institution' => null]);
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

        $this->assertDatabaseHas('credit_cards', ['name' => 'Cartão viagem', 'last4' => '1234', 'limit' => 3000, 'close_day' => 5]);
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

        $this->assertDatabaseCount('credit_cards', 0);
    }

    public function test_edits_an_account_and_a_card(): void
    {
        $account = Account::factory()->create(['name' => 'Conta', 'opening_balance' => 100]);
        $card = CreditCard::factory()->create(['name' => 'Cartão', 'limit' => 1000]);

        Livewire::test(Accounts::class)
            ->call('editAccount', $account->id)
            ->assertSet('form.name', 'Conta')
            ->assertSet('form.balance', '100,00')
            ->set('form.name', 'Conta salário')
            ->set('form.balance', '150,00')
            ->call('saveAccount')
            ->assertHasNoErrors()
            ->call('editCard', $card->id)
            ->assertSet('form.kind', 'card')
            ->set('form.limit', '2.500')
            ->call('saveAccount')
            ->assertHasNoErrors();

        $this->assertSame('Conta salário', $account->refresh()->name);
        $this->assertSame(150.0, $account->opening_balance);
        $this->assertSame(2500.0, $card->refresh()->limit);
        $this->assertDatabaseCount('credit_cards', 1);
    }

    public function test_refuses_to_delete_an_account_with_activity(): void
    {
        $account = Account::factory()->create();
        $this->expense(['account_id' => $account->id]);

        Livewire::test(Accounts::class)
            ->call('editAccount', $account->id)
            ->call('deleteEditing')
            ->assertSet('deleteError', fn ($error) => str_contains($error, 'tem transações'))
            ->assertNotDispatched('close-modal');

        $this->assertModelExists($account);
    }

    public function test_deletes_an_account_and_a_card_without_activity(): void
    {
        $account = Account::factory()->create();
        $card = CreditCard::factory()->create();

        Livewire::test(Accounts::class)
            ->call('editAccount', $account->id)
            ->call('deleteEditing')
            ->assertDispatched('close-modal', name: 'account-form')
            ->call('editCard', $card->id)
            ->call('deleteEditing');

        $this->assertModelMissing($account);
        $this->assertModelMissing($card);
    }

    public function test_transfers_between_accounts(): void
    {
        $checking = Account::factory()->create(['opening_balance' => 4180.55]);
        $savings = Account::factory()->create(['opening_balance' => 8420]);

        Livewire::test(Accounts::class)
            ->call('openTransfer')
            ->assertSet('transfer.from', (string) $checking->id)
            ->assertSet('transfer.to', (string) $savings->id)
            ->set('transfer.amount', '1.000,55')
            ->call('saveTransfer')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'transfer')
            ->assertSee('R$ 3.180,00')
            ->assertSee('R$ 9.420,55')
            ->assertSee('R$ 12.600,55');

        $this->assertDatabaseHas('transfers', ['from_account_id' => $checking->id, 'to_account_id' => $savings->id, 'amount' => 1000.55, 'date' => '2026-10-21']);
    }

    public function test_transfer_rejects_same_account_and_insufficient_balance(): void
    {
        $wallet = Account::factory()->create(['opening_balance' => 240]);
        Account::factory()->create();

        Livewire::test(Accounts::class)
            ->call('openTransfer')
            ->set('transfer.from', (string) $wallet->id)
            ->set('transfer.to', (string) $wallet->id)
            ->set('transfer.amount', '500')
            ->call('saveTransfer')
            ->assertHasErrors(['transfer.to', 'transfer.amount']);

        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_undoes_a_transfer(): void
    {
        $transfer = Transfer::factory()->create();

        Livewire::test(Accounts::class)->call('deleteTransfer', $transfer->id);

        $this->assertModelMissing($transfer);
    }
}
