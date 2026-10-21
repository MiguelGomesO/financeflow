<?php

namespace Tests\Feature;

use App\Livewire\CreateTransaction;
use App\Livewire\EditTransaction;
use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionsTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');

        $this->account = Account::factory()->create(['name' => 'Conta corrente']);
    }

    private function category(string $name): Category
    {
        return Category::where('name', $name)->firstOrFail();
    }

    private function transaction(string $description, float $amount, string $date, string $category = 'Mercado'): Transaction
    {
        return Transaction::factory()->for($this->account)->create([
            'description' => $description,
            'amount' => $amount,
            'date' => $date,
            'category_id' => $this->category($category)->id,
        ]);
    }

    public function test_lists_the_current_month_with_totals(): void
    {
        $this->transaction('Salário', 5000, '2026-10-05', 'Renda');
        $this->transaction('Supermercado', -300, '2026-10-18');
        $this->transaction('Aluguel de setembro', -1500, '2026-09-10', 'Moradia');

        $this->get(route('transactions'))
            ->assertOk()
            ->assertSeeInOrder(['Supermercado', 'Salário'])
            ->assertDontSee('Aluguel de setembro')
            ->assertSee('R$ 5.000,00')
            ->assertSee('R$ 300,00')
            ->assertSee('R$ 4.700,00')
            ->assertSee('Outubro de 2026')
            ->assertSee('Setembro de 2026');
    }

    public function test_switches_month(): void
    {
        $this->transaction('Supermercado', -300, '2026-10-18');
        $this->transaction('Aluguel de setembro', -1500, '2026-09-10', 'Moradia');

        Livewire::test(Transactions::class)
            ->set('month', '2026-09')
            ->assertSee('Aluguel de setembro')
            ->assertDontSee('Supermercado');
    }

    public function test_filters_by_search_type_and_category(): void
    {
        $this->transaction('Salário', 5000, '2026-10-05', 'Renda');
        $this->transaction('Supermercado', -300, '2026-10-18');
        $this->transaction('Conta de luz', -170, '2026-10-08', 'Moradia');

        Livewire::test(Transactions::class)
            ->set('search', 'super')
            ->assertSee('Supermercado')
            ->assertDontSee('Conta de luz')
            ->set('search', '')
            ->set('type', 'income')
            ->assertSee('Salário')
            ->assertDontSee('Supermercado')
            ->set('type', 'all')
            ->set('category', (string) $this->category('Moradia')->id)
            ->assertSee('Conta de luz')
            ->assertDontSee('Supermercado')
            ->assertSee('R$ 170,00')
            ->call('clearFilters')
            ->assertSee('Supermercado');
    }

    public function test_invalid_filters_fall_back_to_defaults(): void
    {
        $this->get(route('transactions', ['month' => 'abc', 'type' => 'x', 'category' => '999']))->assertOk();

        Livewire::test(Transactions::class)
            ->set('type', 'bogus')
            ->assertSet('type', 'all')
            ->set('month', '2026-13')
            ->assertSet('month', '2026-10');
    }

    public function test_paginates_long_months(): void
    {
        foreach (range(1, 21) as $i) {
            $this->transaction("Compra {$i}", -10, '2026-10-'.str_pad((string) min($i, 21), 2, '0', STR_PAD_LEFT));
        }

        Livewire::test(Transactions::class)
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 20 && $rows->total() === 21)
            ->assertSee('Compra 21')
            ->call('nextPage')
            ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->description === 'Compra 1');
    }

    public function test_inline_form_creates_and_refreshes_the_list(): void
    {
        Livewire::test(CreateTransaction::class, ['inline' => true])
            ->set('form.description', 'Pizzaria')
            ->set('form.amount', '1.234,50')
            ->set('form.categoryId', (string) $this->category('Lazer')->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transactions-changed')
            ->assertDispatched('transaction-saved')
            ->assertNotDispatched('close-modal')
            ->assertSet('form.description', '');

        Livewire::test(Transactions::class)->assertSee('Pizzaria')->assertSee('− R$ 1.234,50');
    }

    public function test_edits_a_transaction(): void
    {
        $card = CreditCard::factory()->create();
        $transaction = $this->transaction('Mercado', -100, '2026-10-10');

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->assertDispatched('open-modal', name: 'edit-transaction')
            ->assertSet('form.description', 'Mercado')
            ->assertSet('form.amount', '100,00')
            ->assertSet('form.type', 'expense')
            ->assertSet('form.source', "account:{$this->account->id}")
            ->set('form.description', 'Feira')
            ->set('form.amount', '80,50')
            ->set('form.source', "card:{$card->id}")
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transactions-changed')
            ->assertDispatched('close-modal', name: 'edit-transaction');

        $transaction->refresh();
        $this->assertSame('Feira', $transaction->description);
        $this->assertSame(-80.5, $transaction->amount);
        $this->assertNull($transaction->account_id);
        $this->assertSame($card->id, $transaction->credit_card_id);
    }

    public function test_switching_an_expense_to_income_moves_it_off_the_card(): void
    {
        $card = CreditCard::factory()->create();
        $transaction = Transaction::factory()->create([
            'account_id' => null,
            'credit_card_id' => $card->id,
            'category_id' => $this->category('Mercado')->id,
            'amount' => -50,
        ]);

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->set('form.type', 'income')
            ->assertSet('form.categoryId', (string) $this->category('Renda')->id)
            ->assertSet('form.source', "account:{$this->account->id}")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(50.0, $transaction->refresh()->amount);
    }

    public function test_deletes_a_transaction(): void
    {
        $transaction = $this->transaction('Cinema', -64, '2026-10-19', 'Lazer');

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->call('delete')
            ->assertDispatched('transactions-changed');

        $this->assertModelMissing($transaction);
    }

    public function test_parses_brazilian_amounts(): void
    {
        $this->assertSame(1234.56, Money::parse('1.234,56'));
        $this->assertSame(25.9, Money::parse('25,90'));
        $this->assertSame(12.5, Money::parse('12.50'));
        $this->assertSame(8000.0, Money::parse('8.000'));
        $this->assertSame(1250000.0, Money::parse('1.250.000'));
        $this->assertSame(0.0, Money::parse('abc'));
    }
}
