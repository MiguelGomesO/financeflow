<?php

namespace Tests\Feature;

use App\Livewire\Budgets;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    private function category(string $name): Category
    {
        return Category::where('name', $name)->firstOrFail();
    }

    private function budget(string $category, float $limit, string $month = '2026-10'): Budget
    {
        return Budget::factory()->create(['category_id' => $this->category($category)->id, 'month' => $month, 'limit' => $limit]);
    }

    private function spend(string $category, float $amount, string $date, array $extra = []): void
    {
        Transaction::factory()->create([
            'category_id' => $this->category($category)->id,
            'account_id' => Account::factory(),
            'amount' => -$amount,
            'date' => $date,
            ...$extra,
        ]);
    }

    public function test_renders_the_current_month_with_spending(): void
    {
        $this->budget('Mercado', 1400);
        $this->budget('Lazer', 450);
        $this->spend('Mercado', 1120, '2026-10-05');
        $this->spend('Lazer', 520, '2026-10-12', ['account_id' => null, 'credit_card_id' => CreditCard::factory()]);
        $this->spend('Mercado', 999, '2026-09-30'); // outro mês

        $this->get(route('budgets'))
            ->assertOk()
            ->assertSee('Outubro de 2026')
            ->assertSee('R$ 1.850')     // orçado
            ->assertSee('R$ 1.640')     // gasto
            ->assertSee('R$ 210')       // disponível
            ->assertSee('dia 21 de 31')
            ->assertSee('R$ 280 disponíveis')
            ->assertSee('R$ 28/dia')
            ->assertSee('Estourado')
            ->assertSee('R$ 70 acima do limite');
    }

    public function test_past_and_future_months(): void
    {
        $this->budget('Mercado', 1000, '2026-09');
        $this->spend('Mercado', 400, '2026-09-10');

        Livewire::test(Budgets::class)
            ->set('month', '2026-09')
            ->assertSee('Mês encerrado')
            ->assertSee('R$ 600 disponíveis')
            ->set('month', '2026-11')
            ->assertSee('O mês ainda não começou')
            ->assertSee('Novembro de 2026');
    }

    public function test_invalid_month_falls_back_to_current(): void
    {
        $this->get(route('budgets', ['month' => 'xyz']))->assertOk()->assertSee('dia 21 de 31');

        Livewire::test(Budgets::class)
            ->set('month', '2026-13')
            ->assertSet('month', '2026-10');
    }

    public function test_creates_a_budget(): void
    {
        Livewire::test(Budgets::class)
            ->call('openCreate')
            ->assertDispatched('open-modal', name: 'budget-form')
            ->set('form.categoryId', (string) $this->category('Saúde')->id)
            ->set('form.limit', '350,00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'budget-form')
            ->assertSee('R$ 350 disponíveis');

        $this->assertDatabaseHas('budgets', ['category_id' => $this->category('Saúde')->id, 'month' => '2026-10', 'limit' => 350]);
    }

    public function test_rejects_duplicate_income_category_and_zero_limit(): void
    {
        $this->budget('Mercado', 1000);

        Livewire::test(Budgets::class)
            ->set('form.categoryId', (string) $this->category('Mercado')->id)
            ->set('form.limit', '0')
            ->call('save')
            ->assertHasErrors(['form.categoryId' => 'unique', 'form.limit']);

        Livewire::test(Budgets::class)
            ->set('form.categoryId', (string) $this->category('Renda')->id)
            ->set('form.limit', '100')
            ->call('save')
            ->assertHasErrors(['form.categoryId' => 'exists']);

        $this->assertDatabaseCount('budgets', 1);
    }

    public function test_same_category_is_allowed_in_another_month(): void
    {
        $this->budget('Mercado', 1000, '2026-09');

        Livewire::test(Budgets::class)
            ->set('form.categoryId', (string) $this->category('Mercado')->id)
            ->set('form.limit', '1200')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('budgets', 2);
    }

    public function test_edits_the_limit_and_deletes(): void
    {
        $budget = $this->budget('Mercado', 1000);

        Livewire::test(Budgets::class)
            ->call('edit', $budget->id)
            ->assertSet('form.limit', '1.000,00')
            ->assertSee('Orçamento de Mercado')
            ->set('form.limit', '1.500')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1500.0, $budget->refresh()->limit);

        Livewire::test(Budgets::class)
            ->call('edit', $budget->id)
            ->call('delete')
            ->assertSee('Nenhum orçamento definido para este mês.');

        $this->assertModelMissing($budget);
    }

    public function test_copies_limits_from_previous_month(): void
    {
        $this->budget('Mercado', 1400, '2026-09');
        $this->budget('Lazer', 450, '2026-09');

        Livewire::test(Budgets::class)
            ->assertSee('Copiar limites de Setembro de 2026')
            ->call('copyPrevious')
            ->assertSee('Mercado')
            ->assertSee('Lazer')
            ->assertDontSee('Copiar limites');

        $this->assertDatabaseHas('budgets', ['category_id' => $this->category('Mercado')->id, 'month' => '2026-10', 'limit' => 1400]);
        $this->assertDatabaseCount('budgets', 4);
    }
}
