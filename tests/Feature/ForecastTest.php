<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\ForecastQuery;
use App\Services\RecurringGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ForecastTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');

        $this->account = Account::factory()->create(['opening_balance' => 1000]);
    }

    private function rule(string $description, float $amount, int $day, array $extra = []): RecurringTransaction
    {
        return RecurringTransaction::factory()->create([
            'account_id' => $this->account->id,
            'credit_card_id' => null,
            'category_id' => Category::where('name', $amount > 0 ? 'Renda' : 'Moradia')->value('id'),
            'description' => $description,
            'amount' => $amount,
            'day_of_month' => $day,
            'starts_on' => '2026-10-01',
            ...$extra,
        ]);
    }

    private function entry(string $description, float $amount, string $date): Transaction
    {
        return Transaction::factory()->create([
            'account_id' => $this->account->id,
            'description' => $description,
            'amount' => $amount,
            'date' => $date,
            'category_id' => Category::where('name', 'Mercado')->value('id'),
        ]);
    }

    private function forecast(): array
    {
        (new RecurringGenerator)->run();

        return (new ForecastQuery(now()))->month();
    }

    public function test_projects_the_end_of_month_balance_from_recurring_and_scheduled_entries(): void
    {
        $this->rule('Internet', -100, 10);          // já venceu: foi gerada e está no saldo de hoje
        $this->rule('Salário', 5000, 25);
        $this->rule('Aluguel', -1800, 28);
        $this->entry('Almoço', -20, '2026-10-21');  // hoje: já está no saldo
        $this->entry('Dentista', -30, '2026-10-23'); // cadastrada para depois
        $this->entry('Viagem de novembro', -999, '2026-11-02'); // fora do mês: não conta

        $forecast = $this->forecast();

        $this->assertSame(880.0, $forecast['balance']);          // 1000 − 100 − 20
        $this->assertSame(5000.0, $forecast['income']);
        $this->assertSame(1830.0, $forecast['expense']);         // 30 + 1800
        $this->assertSame(4050.0, $forecast['projected']);       // 880 + 5000 − 1830
        $this->assertSame('2026-10-31', $forecast['end']->toDateString());
        $this->assertSame(
            [['2026-10-23', 'Dentista', false], ['2026-10-25', 'Salário', true], ['2026-10-28', 'Aluguel', true]],
            array_map(fn ($e) => [$e['date']->toDateString(), $e['description'], $e['recurring']], $forecast['events']),
        );
    }

    public function test_finds_the_lowest_point_and_when_it_happens(): void
    {
        $this->account->update(['opening_balance' => 100]);
        $this->rule('Aluguel', -1800, 24);   // antes do salário
        $this->rule('Salário', 5000, 25);

        $forecast = $this->forecast();

        $this->assertSame(3300.0, $forecast['projected']);
        $this->assertSame(-1700.0, $forecast['lowest']['amount']);
        $this->assertSame('2026-10-24', $forecast['lowest']['date']->toDateString());
    }

    public function test_when_nothing_dips_the_lowest_point_is_today(): void
    {
        $this->rule('Salário', 5000, 25);

        $forecast = $this->forecast();

        $this->assertSame(1000.0, $forecast['lowest']['amount']);
        $this->assertSame('2026-10-21', $forecast['lowest']['date']->toDateString());
    }

    public function test_ignores_inactive_ended_and_future_rules(): void
    {
        $this->rule('Pausada', -500, 26, ['active' => false]);
        $this->rule('Terminou', -500, 27, ['ends_on' => '2026-09-30']);
        $this->rule('Começa em novembro', -500, 28, ['starts_on' => '2026-11-01']);

        $forecast = $this->forecast();

        $this->assertSame([], $forecast['events']);
        $this->assertSame(1000.0, $forecast['projected']);
    }

    public function test_card_recurring_goes_to_the_card_total_not_the_balance(): void
    {
        $card = CreditCard::factory()->create();
        $this->rule('Streaming', -50, 30, ['account_id' => null, 'credit_card_id' => $card->id]);

        $forecast = $this->forecast();

        $this->assertSame(50.0, $forecast['card']);
        $this->assertSame(1000.0, $forecast['projected']);
        $this->assertSame([], $forecast['events']);
    }

    public function test_on_the_last_day_of_the_month_nothing_is_left_to_project(): void
    {
        Carbon::setTestNow('2026-10-31 09:00:00');
        $this->rule('Salário', 5000, 31);

        $forecast = $this->forecast();

        $this->assertSame([], $forecast['events']);
        $this->assertSame(6000.0, $forecast['projected']);  // o salário de hoje já foi gerado
        $this->assertSame($forecast['balance'], $forecast['projected']);
    }

    public function test_short_months_use_their_last_day(): void
    {
        Carbon::setTestNow('2027-02-10 09:00:00');
        $this->rule('Aluguel', -1000, 31, ['starts_on' => '2027-02-01']);

        $forecast = $this->forecast();

        $this->assertSame(['2027-02-28'], array_map(fn ($e) => $e['date']->toDateString(), $forecast['events']));
    }

    public function test_only_the_logged_in_users_data_counts(): void
    {
        $this->rule('Salário', 5000, 25);

        $this->actingAs(User::factory()->create());

        $forecast = (new ForecastQuery(now()))->month();

        $this->assertSame(0.0, $forecast['balance']);
        $this->assertSame([], $forecast['events']);
    }

    public function test_dashboard_shows_the_forecast(): void
    {
        $this->rule('Salário', 5000, 25);
        $this->rule('Aluguel', -1800, 28);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Previsão do mês')
            ->assertSee('Saldo previsto em 31/10')
            ->assertSee('R$ 4.200,00')
            ->assertSee('Salário')
            ->assertSee('recorrente')
            ->assertDontSee('O saldo deve ficar negativo');
    }

    public function test_dashboard_warns_when_the_balance_goes_negative(): void
    {
        $this->account->update(['opening_balance' => 100]);
        $this->rule('Aluguel', -1800, 24);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('O saldo deve ficar negativo a partir de 24/10');
    }

    public function test_dashboard_says_when_nothing_is_scheduled(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertSee('Nada previsto nas contas até o fim do mês.');
    }
}
