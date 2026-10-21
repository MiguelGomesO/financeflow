<?php

namespace Tests\Feature;

use App\Livewire\CreateTransaction;
use App\Livewire\Dashboard;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Goal;
use App\Models\GoalDeposit;
use App\Models\Transaction;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    public function test_renders_with_an_empty_database(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('R$ 0,00')
            ->assertSee('Nenhum orçamento para este mês.')
            ->assertSee('Nenhuma meta ainda.')
            ->assertSee('Nenhuma transação ainda.');
    }

    public function test_balance_sums_opening_balance_and_account_transactions(): void
    {
        $checking = Account::factory()->create(['opening_balance' => 1000]);
        Account::factory()->create(['opening_balance' => 50]);
        $card = CreditCard::factory()->create();

        Transaction::factory()->for($checking)->create(['amount' => 500]);
        Transaction::factory()->for($checking)->create(['amount' => -200]);
        // Gasto no cartão não mexe no saldo das contas.
        Transaction::factory()->create(['account_id' => null, 'credit_card_id' => $card->id, 'amount' => -999]);

        Livewire::test(Dashboard::class)
            ->assertSee('R$ 1.350,00')
            ->assertSee('2 contas');
    }

    public function test_income_and_expense_follow_the_selected_period(): void
    {
        $account = Account::factory()->create();

        Transaction::factory()->for($account)->create(['amount' => 1000, 'date' => '2026-10-05']);
        Transaction::factory()->for($account)->create(['amount' => -300, 'date' => '2026-10-10']);
        Transaction::factory()->for($account)->create(['amount' => 2000, 'date' => '2026-08-05']);
        Transaction::factory()->for($account)->create(['amount' => -700, 'date' => '2026-06-10']);

        Livewire::test(Dashboard::class)
            ->assertSee('outubro de 2026')
            ->assertSee('R$ 1.000,00')
            ->assertSee('R$ 300,00')
            ->assertSee('70%')
            ->set('period', 'quarter')
            ->assertSee('agosto a outubro de 2026')
            ->assertSee('R$ 3.000,00')
            ->set('period', 'semester')
            ->assertSee('R$ 1.000,00'); // saídas: 300 + 700
    }

    public function test_budget_spent_counts_only_the_current_month_expenses(): void
    {
        $groceries = Category::factory()->create(['name' => 'Feira']);
        $card = CreditCard::factory()->create();

        Budget::factory()->for($groceries)->create(['month' => '2026-10', 'limit' => 1000]);
        Budget::factory()->for($groceries)->create(['month' => '2026-09', 'limit' => 9999]);

        Transaction::factory()->for($groceries)->create(['amount' => -300, 'date' => '2026-10-03']);
        Transaction::factory()->for($groceries)->create(['amount' => -50, 'date' => '2026-10-15', 'account_id' => null, 'credit_card_id' => $card->id]);
        Transaction::factory()->for($groceries)->create(['amount' => -100, 'date' => '2026-09-28']);

        Livewire::test(Dashboard::class)
            ->assertSee('R$ 350 de R$ 1.000')
            ->assertDontSee('R$ 9.999');
    }

    public function test_last_day_of_month_is_included(): void
    {
        $account = Account::factory()->create();
        Transaction::factory()->for($account)->create(['amount' => -123.45, 'date' => '2026-09-30 15:00:00']);

        $this->assertDatabaseHas('transactions', ['date' => '2026-09-30']);

        Livewire::test(Dashboard::class)
            ->set('period', 'quarter')
            ->assertSee('R$ 123,45');
    }

    public function test_lists_the_six_latest_transactions(): void
    {
        $account = Account::factory()->create();

        foreach (range(1, 7) as $day) {
            Transaction::factory()->for($account)->create(['description' => "Compra {$day}", 'date' => "2026-10-0{$day}"]);
        }

        Livewire::test(Dashboard::class)
            ->assertSeeInOrder(['Compra 7', 'Compra 6', 'Compra 2'])
            ->assertDontSee('Compra 1');
    }

    public function test_goals_show_saved_amount_from_deposits(): void
    {
        $goal = Goal::factory()->create(['name' => 'Bicicleta', 'target' => 2000]);
        GoalDeposit::factory()->for($goal)->create(['amount' => 300]);
        GoalDeposit::factory()->for($goal)->create(['amount' => 200]);

        Livewire::test(Dashboard::class)
            ->assertSee('Bicicleta')
            ->assertSee('R$ 500 / R$ 2.000')
            ->assertSee('25%');
    }

    public function test_demo_seeder_fills_the_dashboard(): void
    {
        $this->seed(DemoSeeder::class);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('3 contas')
            ->assertSee('Reserva de emergência')
            ->assertDontSee('Nenhum orçamento para este mês.');
    }

    public function test_creates_an_expense_and_refreshes_the_dashboard(): void
    {
        $account = Account::factory()->create(['opening_balance' => 1000]);
        $category = Category::where('name', 'Mercado')->firstOrFail();

        Livewire::test(CreateTransaction::class)
            ->assertSet('form.source', "account:{$account->id}")
            ->set('form.description', 'Feira da semana')
            ->set('form.amount', '1.234,50')
            ->set('form.categoryId', (string) $category->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transactions-changed')
            ->assertDispatched('close-modal', name: 'new-transaction')
            ->assertSet('form.description', '');

        $this->assertDatabaseHas('transactions', [
            'description' => 'Feira da semana',
            'amount' => -1234.50,
            'category_id' => $category->id,
            'account_id' => $account->id,
            'date' => '2026-10-21',
        ]);

        Livewire::test(Dashboard::class)->assertSee('Feira da semana')->assertSee('− R$ 234,50');
    }

    public function test_creates_an_expense_on_a_credit_card(): void
    {
        Account::factory()->create();
        $card = CreditCard::factory()->create();

        Livewire::test(CreateTransaction::class)
            ->set('form.description', 'Livro')
            ->set('form.amount', '80')
            ->set('form.source', "card:{$card->id}")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', ['description' => 'Livro', 'credit_card_id' => $card->id, 'account_id' => null]);
    }

    public function test_income_switches_category_and_rejects_credit_card(): void
    {
        Account::factory()->create();
        $card = CreditCard::factory()->create();
        $income = Category::where('name', 'Renda')->firstOrFail();

        Livewire::test(CreateTransaction::class)
            ->set('form.type', 'income')
            ->assertSet('form.categoryId', (string) $income->id)
            ->set('form.description', 'Salário')
            ->set('form.amount', '5000')
            ->set('form.source', "card:{$card->id}")
            ->call('save')
            ->assertHasErrors(['form.source']);
    }

    public function test_validates_the_new_transaction(): void
    {
        Account::factory()->create();
        $expenseCategory = Category::where('name', 'Mercado')->firstOrFail();

        Livewire::test(CreateTransaction::class)
            ->set('form.type', 'income')
            ->set('form.categoryId', (string) $expenseCategory->id)
            ->set('form.description', '')
            ->set('form.amount', '0')
            ->call('save')
            ->assertHasErrors(['form.description', 'form.amount', 'form.categoryId']);

        $this->assertDatabaseCount('transactions', 0);
    }
}
