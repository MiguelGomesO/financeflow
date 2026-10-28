<?php

namespace Tests\Feature;

use App\Livewire\Accounts;
use App\Livewire\CreateTransaction;
use App\Livewire\EditTransaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Support\Installments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class InstallmentsTest extends TestCase
{
    use RefreshDatabase;

    private CreditCard $card;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');

        $this->account = Account::factory()->create();
        $this->card = CreditCard::factory()->create(['close_day' => 25, 'due_day' => 5]);
    }

    private function buy(int $installments, string $amount = '1.000,00', ?string $source = null, string $date = '2026-10-21'): void
    {
        Livewire::test(CreateTransaction::class)
            ->set('form.description', 'Notebook')
            ->set('form.amount', $amount)
            ->set('form.categoryId', (string) Category::where('name', 'Lazer')->value('id'))
            ->set('form.source', $source ?? "card:{$this->card->id}")
            ->set('form.date', $date)
            ->set('form.installments', (string) $installments)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_splits_cents_into_the_first_installment(): void
    {
        $parcels = Installments::schedule(100, 3, Carbon::parse('2026-10-21'));

        $this->assertSame([33.34, 33.33, 33.33], array_column($parcels, 'amount'));
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($parcels, 'amount')), 0.001);
    }

    public function test_dates_keep_the_day_and_clamp_short_months(): void
    {
        $dates = array_map(fn ($p) => $p['date']->toDateString(), Installments::schedule(300, 4, Carbon::parse('2026-11-30')));

        $this->assertSame(['2026-11-30', '2026-12-30', '2027-01-30', '2027-02-28'], $dates);

        $dates = array_map(fn ($p) => $p['date']->toDateString(), Installments::schedule(300, 3, Carbon::parse('2027-01-31')));

        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], $dates);
    }

    public function test_creates_one_transaction_per_installment_on_the_card(): void
    {
        $this->buy(4, '1.000,00');

        $rows = Transaction::orderBy('installment_number')->get();

        $this->assertCount(4, $rows);
        $this->assertSame([-250.0, -250.0, -250.0, -250.0], $rows->pluck('amount')->all());
        $this->assertSame(['2026-10-21', '2026-11-21', '2026-12-21', '2027-01-21'], $rows->map(fn ($t) => $t->date->toDateString())->all());
        $this->assertSame([1, 2, 3, 4], $rows->pluck('installment_number')->all());
        $this->assertSame([4], $rows->pluck('installment_total')->unique()->values()->all());
        $this->assertCount(1, $rows->pluck('installment_group')->unique());
        $this->assertSame([$this->card->id], $rows->pluck('credit_card_id')->unique()->values()->all());
        $this->assertSame('2/4', $rows[1]->installment_label);
    }

    public function test_single_payment_is_a_plain_transaction(): void
    {
        $this->buy(1);

        $transaction = Transaction::sole();
        $this->assertNull($transaction->installment_group);
        $this->assertNull($transaction->installment_label);
        $this->assertSame(-1000.0, $transaction->amount);
    }

    public function test_installments_are_ignored_outside_the_card(): void
    {
        $this->buy(6, source: "account:{$this->account->id}");

        $this->assertSame(1, Transaction::count());
        $this->assertNull(Transaction::sole()->installment_group);
    }

    public function test_rejects_out_of_range_installments(): void
    {
        foreach (['0', '25', 'abc'] as $value) {
            Livewire::test(CreateTransaction::class)
                ->set('form.description', 'Notebook')
                ->set('form.amount', '100')
                ->set('form.categoryId', (string) Category::where('name', 'Lazer')->value('id'))
                ->set('form.source', "card:{$this->card->id}")
                ->set('form.installments', $value)
                ->call('save')
                ->assertHasErrors('form.installments');
        }

        $this->assertSame(0, Transaction::count());
    }

    public function test_installments_land_on_the_right_bills(): void
    {
        // Fecha dia 25: a parcela de 21/10 entra na fatura que fecha em 25/10; as outras, nas dos meses seguintes.
        $this->buy(3, '300,00');

        $this->assertSame(100.0, $this->card->currentBill());

        $upcoming = $this->card->upcomingBills();

        $this->assertSame([100.0, 100.0], array_column($upcoming, 'amount'));
        $this->assertSame(['2026-12-05', '2027-01-05'], array_map(fn ($b) => $b['due']->toDateString(), $upcoming));
    }

    public function test_purchase_after_closing_goes_to_the_next_bill(): void
    {
        $this->buy(2, '200,00', date: '2026-10-27');

        $this->assertSame(0.0, $this->card->currentBill());
        $this->assertSame([100.0, 100.0], array_column($this->card->upcomingBills(), 'amount'));
    }

    public function test_accounts_page_shows_upcoming_bills(): void
    {
        $this->buy(3, '300,00');

        Livewire::test(Accounts::class)->assertSee('Próximas faturas')->assertSee('05/12')->assertSee('R$ 100,00');
    }

    public function test_edit_modal_shows_the_installment_and_deletes_by_scope(): void
    {
        $this->buy(5, '500,00');
        $third = Transaction::where('installment_number', 3)->first();

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $third->id)
            ->assertSee('Parcela 3 de 5')
            ->call('deleteInstallments', 'following')
            ->assertDispatched('transactions-changed');

        $this->assertSame([1, 2], Transaction::orderBy('installment_number')->pluck('installment_number')->all());

        $second = Transaction::where('installment_number', 2)->first();

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $second->id)
            ->call('deleteInstallments', 'all');

        $this->assertSame(0, Transaction::count());
    }

    public function test_deleting_one_installment_keeps_the_others(): void
    {
        $this->buy(3, '300,00');
        $second = Transaction::where('installment_number', 2)->first();

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $second->id)
            ->call('delete');

        $this->assertSame([1, 3], Transaction::orderBy('installment_number')->pluck('installment_number')->all());
    }

    public function test_delete_installments_does_nothing_on_a_regular_transaction(): void
    {
        $this->buy(1);

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: Transaction::sole()->id)
            ->call('deleteInstallments', 'all');

        $this->assertSame(1, Transaction::count());
    }

    public function test_editing_one_installment_changes_only_that_one(): void
    {
        $this->buy(3, '300,00');
        $first = Transaction::where('installment_number', 1)->first();

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $first->id)
            ->set('form.description', 'Notebook novo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['Notebook novo', 'Notebook', 'Notebook'], Transaction::orderBy('installment_number')->pluck('description')->all());
        $this->assertSame(3, Transaction::whereNotNull('installment_group')->count());
    }

    public function test_dashboard_recent_list_hides_future_installments(): void
    {
        $this->buy(3, '300,00');

        $this->get(route('dashboard'))->assertOk()->assertSee('Notebook')->assertSee('1/3')->assertDontSee('2/3');
    }

    public function test_transactions_page_shows_the_installment_in_its_month(): void
    {
        $this->buy(3, '300,00');

        $this->get(route('transactions', ['month' => '2026-11']))->assertOk()->assertSee('Notebook')->assertSee('2/3');
    }
}
