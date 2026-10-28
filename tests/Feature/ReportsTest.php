<?php

namespace Tests\Feature;

use App\Livewire\Reports;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\ReportsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    private function add(string $category, float $amount, string $date): void
    {
        Transaction::factory()->create([
            'category_id' => Category::where('name', $category)->firstOrFail()->id,
            'account_id' => Account::first()?->id ?? Account::factory()->create()->id,
            'amount' => $amount,
            'date' => $date,
        ]);
    }

    private function seedTwoMonths(): void
    {
        $this->add('Renda', 5000, '2026-10-05');
        $this->add('Mercado', -300, '2026-10-10');
        $this->add('Transporte', -100, '2026-10-15');

        $this->add('Renda', 4000, '2026-09-05');
        $this->add('Mercado', -200, '2026-09-10');
        $this->add('Mercado', -999, '2026-09-25'); // depois do ponto de corte (dia 21)
    }

    public function test_renders_with_an_empty_database(): void
    {
        $this->get(route('reports'))
            ->assertOk()
            ->assertSee('Relatórios')
            ->assertSee('Evolução mensal')
            ->assertSee('Nenhum gasto neste período.')
            ->assertSee('Sem movimento nos dois períodos');
    }

    public function test_compares_with_the_same_stretch_of_the_previous_month(): void
    {
        $this->seedTwoMonths();

        $data = (new ReportsQuery(now(), 1))->compare();

        $this->assertSame(['income' => 5000.0, 'expense' => 400.0, 'net' => 4600.0], $data['current']);
        $this->assertSame(['income' => 4000.0, 'expense' => 200.0, 'net' => 3800.0], $data['previous']);
    }

    public function test_groups_expenses_by_category_with_share_and_delta(): void
    {
        $this->seedTwoMonths();

        [$mercado, $transporte] = (new ReportsQuery(now(), 1))->compare()['categories'];

        $this->assertSame('Mercado', $mercado['name']);
        $this->assertSame(300.0, $mercado['current']);
        $this->assertSame(75.0, $mercado['share']);
        $this->assertSame(50.0, $mercado['delta']);

        $this->assertSame('Transporte', $transporte['name']);
        $this->assertNull($transporte['delta']);
    }

    public function test_page_shows_comparison_and_categories(): void
    {
        $this->seedTwoMonths();

        $this->get(route('reports'))
            ->assertOk()
            ->assertSee('100% a mais (R$ 200,00) que no período anterior')
            ->assertSee('R$ 800,00 a mais que no período anterior')
            ->assertSee('Mercado')
            ->assertSee('Transporte')
            ->assertSee('novo')
            ->assertSee('01/09/26 a 21/09/26')
            ->assertDontSee('999,00');
    }

    public function test_longer_periods_span_more_months(): void
    {
        $ranges = (new ReportsQuery(now(), 12))->ranges();

        $this->assertSame('2025-11-01', $ranges['start']->toDateString());
        $this->assertSame('2024-11-01', $ranges['previousStart']->toDateString());
        $this->assertSame('2025-10-21', $ranges['previousEnd']->toDateString());
    }

    public function test_previous_period_end_does_not_overflow_short_months(): void
    {
        $ranges = (new ReportsQuery(Carbon::parse('2026-10-31'), 1))->ranges();

        $this->assertSame('2026-09-30', $ranges['previousEnd']->toDateString());
    }

    public function test_extra_categories_are_folded_into_others(): void
    {
        foreach (range(1, 10) as $i) {
            $this->add(Category::factory()->create(['name' => "Categoria {$i}"])->name, -(10 + $i), '2026-10-05');
        }

        $categories = (new ReportsQuery(now(), 1))->compare()['categories'];

        $this->assertCount(8, $categories);
        $this->assertSame('Outras', end($categories)['name']);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($categories, 'share')), 0.01);
    }

    public function test_unknown_period_falls_back_to_month(): void
    {
        Livewire::test(Reports::class)
            ->set('period', 'decade')
            ->assertSet('period', 'month')
            ->set('period', 'year')
            ->assertSet('period', 'year')
            ->assertSee('Últimos 12 meses');
    }

    public function test_only_sees_own_transactions(): void
    {
        $this->add('Mercado', -300, '2026-10-10');

        $this->actingAs(User::factory()->create());

        $this->get(route('reports'))->assertSee('Nenhum gasto neste período.');
    }
}
