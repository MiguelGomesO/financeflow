<?php

namespace Tests\Feature;

use App\Livewire\Recurring;
use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Services\RecurringGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class RecurringTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');

        $this->account = Account::factory()->create(['name' => 'Conta corrente']);
    }

    private function rule(array $attributes = []): RecurringTransaction
    {
        return RecurringTransaction::factory()->create([
            'account_id' => $this->account->id,
            'category_id' => Category::where('name', 'Moradia')->value('id'),
            'description' => 'Aluguel',
            'amount' => -1800,
            'day_of_month' => 10,
            'starts_on' => '2026-08-01',
            ...$attributes,
        ]);
    }

    private function dates(RecurringTransaction $rule): array
    {
        return Transaction::where('recurring_transaction_id', $rule->id)->orderBy('date')
            ->get()->map(fn ($t) => $t->date->toDateString())->all();
    }

    public function test_short_months_use_the_last_day(): void
    {
        $rule = $this->rule(['day_of_month' => 31, 'starts_on' => '2026-01-01']);

        $dates = array_map(fn ($d) => $d->toDateString(), $rule->occurrences(Carbon::parse('2026-01-01'), Carbon::parse('2026-04-30')));

        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'], $dates);
    }

    public function test_generates_every_due_occurrence_up_to_today(): void
    {
        $rule = $this->rule();

        $created = app(RecurringGenerator::class)->run();

        $this->assertSame(3, $created);
        $this->assertSame(['2026-08-10', '2026-09-10', '2026-10-10'], $this->dates($rule));

        $first = Transaction::where('recurring_transaction_id', $rule->id)->orderBy('date')->first();
        $this->assertSame('Aluguel', $first->description);
        $this->assertEqualsWithDelta(-1800.0, $first->amount, 0.001);
        $this->assertSame($this->user->id, $first->user_id);
    }

    public function test_running_twice_does_not_duplicate(): void
    {
        $rule = $this->rule();
        $generator = app(RecurringGenerator::class);

        $generator->run();

        $this->assertSame(0, $generator->run());
        $this->assertCount(3, $this->dates($rule));
    }

    public function test_picks_up_the_next_occurrence_when_its_day_arrives(): void
    {
        $rule = $this->rule();
        app(RecurringGenerator::class)->run();

        $this->assertSame(1, app(RecurringGenerator::class)->run(Carbon::parse('2026-11-10')));
        $this->assertCount(4, $this->dates($rule));
    }

    public function test_a_deleted_occurrence_does_not_come_back(): void
    {
        $rule = $this->rule();
        app(RecurringGenerator::class)->run();

        Transaction::where('recurring_transaction_id', $rule->id)->whereDate('date', '2026-09-10')->delete();
        app(RecurringGenerator::class)->run();

        $this->assertSame(['2026-08-10', '2026-10-10'], $this->dates($rule));
    }

    public function test_respects_the_end_date(): void
    {
        $rule = $this->rule(['ends_on' => '2026-09-30']);

        app(RecurringGenerator::class)->run();

        $this->assertSame(['2026-08-10', '2026-09-10'], $this->dates($rule));
        $this->assertNull($rule->fresh()->nextDate());
    }

    public function test_paused_rules_do_not_generate_and_resume_skips_what_was_missed(): void
    {
        $rule = $this->rule(['starts_on' => '2026-10-01']);
        app(RecurringGenerator::class)->run();
        $rule->update(['active' => false]);

        app(RecurringGenerator::class)->run(Carbon::parse('2026-11-15'));
        $this->assertSame(['2026-10-10'], $this->dates($rule));

        Carbon::setTestNow('2026-12-15');
        Livewire::test(Recurring::class)->call('toggle', $rule->id);

        $this->assertSame(['2026-10-10'], $this->dates($rule));
        $this->assertSame('2027-01-10', $rule->fresh()->nextDate()->toDateString());
    }

    public function test_future_start_waits_for_its_date(): void
    {
        $rule = $this->rule(['starts_on' => '2026-12-01']);

        $this->assertSame(0, app(RecurringGenerator::class)->run());
        $this->assertSame('2026-12-10', $rule->fresh()->nextDate()->toDateString());
    }

    public function test_command_generates_for_every_user(): void
    {
        $this->rule();

        $other = \App\Models\User::factory()->create();
        $this->actingAs($other);
        $otherRule = RecurringTransaction::factory()->create([
            'account_id' => Account::factory()->create()->id,
            'category_id' => Category::where('name', 'Moradia')->value('id'),
            'day_of_month' => 5,
            'starts_on' => '2026-10-01',
        ]);

        auth()->logout();
        $this->artisan('finance:recurring')->assertSuccessful();

        $this->assertSame($other->id, Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $otherRule->id)->value('user_id'));
        $this->assertSame(4, Transaction::withoutGlobalScopes()->whereNotNull('recurring_transaction_id')->count());
    }

    public function test_creates_a_rule_from_the_page_and_backfills_to_today(): void
    {
        Livewire::test(Recurring::class)
            ->call('create')
            ->set('form.description', 'Streaming')
            ->set('form.amount', '55,90')
            ->set('form.day', '5')
            ->set('form.startsOn', '2026-09-01')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'recurring-form');

        $rule = RecurringTransaction::firstOrFail();
        $this->assertEqualsWithDelta(-55.90, $rule->amount, 0.001);
        $this->assertSame(['2026-09-05', '2026-10-05'], $this->dates($rule));
    }

    public function test_income_rules_are_positive_and_cannot_use_cards(): void
    {
        $card = \App\Models\CreditCard::factory()->create();

        Livewire::test(Recurring::class)
            ->call('create')
            ->set('form.type', 'income')
            ->set('form.description', 'Salário')
            ->set('form.amount', '7.500,00')
            ->set('form.source', "card:{$card->id}")
            ->call('save')
            ->assertHasErrors('form.source')
            ->set('form.source', "account:{$this->account->id}")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEqualsWithDelta(7500.0, RecurringTransaction::firstOrFail()->amount, 0.001);
    }

    public function test_validates_the_form(): void
    {
        Livewire::test(Recurring::class)
            ->call('create')
            ->set('form.description', '')
            ->set('form.amount', '0')
            ->set('form.day', '32')
            ->set('form.startsOn', '2026-10-10')
            ->set('form.endsOn', '2026-09-01')
            ->call('save')
            ->assertHasErrors(['form.description', 'form.amount', 'form.day', 'form.endsOn']);

        $this->assertSame(0, RecurringTransaction::count());
    }

    public function test_deleting_a_rule_keeps_the_transactions_it_created(): void
    {
        $rule = $this->rule();
        app(RecurringGenerator::class)->run();

        Livewire::test(Recurring::class)->call('edit', $rule->id)->call('delete');

        $this->assertSame(0, RecurringTransaction::count());
        $this->assertSame(3, Transaction::count());
        $this->assertSame(0, Transaction::whereNotNull('recurring_transaction_id')->count());
    }

    public function test_page_lists_rules_and_totals(): void
    {
        $this->rule(['description' => 'Aluguel', 'amount' => -1800]);
        $this->rule(['description' => 'Salário', 'amount' => 7500, 'category_id' => Category::where('name', 'Renda')->value('id')]);

        $this->get(route('recurring'))
            ->assertOk()
            ->assertSee('Aluguel')
            ->assertSee('Salário')
            ->assertSee('R$ 1.800,00')
            ->assertSee('R$ 7.500,00')
            ->assertSee('Todo dia 10');
    }

    public function test_other_users_rules_are_invisible(): void
    {
        $this->rule(['description' => 'Aluguel da Alice']);

        $this->actingAs(\App\Models\User::factory()->create());

        Livewire::test(Recurring::class)->assertDontSee('Aluguel da Alice');
    }
}
