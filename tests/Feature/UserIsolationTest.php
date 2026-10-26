<?php

namespace Tests\Feature;

use App\Livewire\Accounts;
use App\Livewire\Goals;
use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class UserIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $signIn = false;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');

        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
    }

    public function test_new_records_belong_to_the_logged_in_user(): void
    {
        $this->actingAs($this->alice);

        $this->assertSame($this->alice->id, Account::factory()->create()->user_id);
        $this->assertSame($this->alice->id, CreditCard::factory()->create()->user_id);
        $this->assertSame($this->alice->id, Goal::factory()->create()->user_id);
    }

    public function test_each_user_only_sees_their_own_records(): void
    {
        $this->actingAs($this->alice);
        $account = Account::factory()->create(['name' => 'Conta da Alice']);
        CreditCard::factory()->create(['name' => 'Cartão da Alice']);
        Goal::factory()->create(['name' => 'Meta da Alice']);
        Transaction::factory()->create(['description' => 'Compra da Alice', 'account_id' => $account->id]);

        $this->actingAs($this->bob);
        Account::factory()->create(['name' => 'Conta do Bob']);

        $this->assertSame(1, Account::count());
        $this->assertSame(0, CreditCard::count());
        $this->assertSame(0, Goal::count());
        $this->assertSame(0, Transaction::count());
        $this->assertSame(2, Account::withoutGlobalScopes()->count());

        Livewire::test(Accounts::class)
            ->assertSee('Conta do Bob')
            ->assertDontSee('Conta da Alice')
            ->assertDontSee('Cartão da Alice');
        Livewire::test(Goals::class)->assertDontSee('Meta da Alice');
        Livewire::test(Transactions::class)->assertDontSee('Compra da Alice');
    }

    public function test_a_user_cannot_open_or_change_someone_elses_record(): void
    {
        $this->actingAs($this->alice);
        $account = Account::factory()->create(['name' => 'Conta da Alice']);
        $goal = Goal::factory()->create(['name' => 'Meta da Alice']);

        $this->actingAs($this->bob);

        Livewire::test(Accounts::class)
            ->call('editAccount', $account->id)
            ->assertSet('editingId', null);

        Livewire::test(Goals::class)
            ->call('startDeposit', $goal->id)
            ->assertSet('depositGoalId', null);

        Livewire::test(Goals::class)
            ->set('editingId', $goal->id)
            ->call('deleteGoal');

        $this->assertSame(1, Goal::withoutGlobalScopes()->count());
    }

    public function test_a_user_cannot_deposit_from_someone_elses_account(): void
    {
        $this->actingAs($this->alice);
        $account = Account::factory()->create();
        $goal = Goal::factory()->create();

        $this->actingAs($this->bob);
        $own = Goal::factory()->create();

        Livewire::test(Goals::class)
            ->call('startDeposit', $own->id)
            ->set('deposit.amount', '10,00')
            ->set('deposit.account', (string) $account->id)
            ->call('saveDeposit')
            ->assertHasErrors('deposit.account');

        $this->assertSame(0, $goal->deposits()->withoutGlobalScopes()->count());
        $this->assertSame(0, $own->deposits()->count());
    }

    public function test_each_user_can_have_a_budget_for_the_same_category_and_month(): void
    {
        $category = Category::where('name', 'Mercado')->firstOrFail();

        foreach ([$this->alice, $this->bob] as $user) {
            $this->actingAs($user);
            Budget::factory()->create(['category_id' => $category->id, 'month' => '2026-10', 'limit' => 500]);
        }

        $this->assertSame(2, Budget::withoutGlobalScopes()->count());
        $this->assertSame(1, Budget::count());
    }

    public function test_transfers_are_private_too(): void
    {
        $this->actingAs($this->alice);
        $from = Account::factory()->create(['opening_balance' => 100]);
        $to = Account::factory()->create();
        Transfer::create(['from_account_id' => $from->id, 'to_account_id' => $to->id, 'amount' => 10, 'date' => '2026-10-20']);

        $this->actingAs($this->bob);

        $this->assertSame(0, Transfer::count());
    }
}
