<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\BudgetAlert;
use App\Services\BudgetAlerter;
use App\Support\BudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BudgetAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    private function budget(float $limit = 1000): Budget
    {
        return Budget::factory()->create([
            'category_id' => Category::where('name', 'Mercado')->firstOrFail()->id,
            'month' => '2026-10',
            'limit' => $limit,
        ]);
    }

    private function spend(float $amount): Transaction
    {
        return Transaction::factory()->create([
            'category_id' => Category::where('name', 'Mercado')->firstOrFail()->id,
            'account_id' => Account::factory(),
            'amount' => -$amount,
            'date' => '2026-10-10',
        ]);
    }

    public function test_alert_levels(): void
    {
        $this->assertSame(0, BudgetStatus::alertLevel(799.99, 1000));
        $this->assertSame(80, BudgetStatus::alertLevel(800, 1000));
        $this->assertSame(80, BudgetStatus::alertLevel(999.99, 1000));
        $this->assertSame(100, BudgetStatus::alertLevel(1000, 1000));
        $this->assertSame(100, BudgetStatus::alertLevel(1500, 1000));
        $this->assertSame(0, BudgetStatus::alertLevel(50, 0));
        $this->assertSame(80, BudgetStatus::alertLevel(0.8, 1.0));
    }

    public function test_dashboard_shows_alerts_from_80_percent(): void
    {
        $this->budget();
        $this->spend(790);

        $this->get(route('dashboard'))->assertOk()->assertDontSee('Perto do limite');

        $this->spend(10);

        $this->get(route('dashboard'))->assertSee('Perto do limite em Mercado');

        $this->spend(200);

        $this->get(route('dashboard'))
            ->assertSee('Limite atingido em Mercado')
            ->assertDontSee('Perto do limite');
    }

    public function test_emails_each_level_only_once(): void
    {
        Notification::fake();
        $this->budget();
        $alerter = new BudgetAlerter;

        $this->assertSame(0, $alerter->run());

        $this->spend(850);
        $this->assertSame(1, $alerter->run());
        $this->assertSame(0, $alerter->run());

        $this->spend(200);
        $this->assertSame(1, $alerter->run());
        $this->assertSame(0, $alerter->run());

        Notification::assertSentToTimes($this->user, BudgetAlert::class, 2);
        $this->assertSame(100, Budget::first()->alert_level);
    }

    public function test_jumping_straight_to_100_sends_a_single_email(): void
    {
        Notification::fake();
        $this->budget();
        $this->spend(1200);

        $this->assertSame(1, (new BudgetAlerter)->run());

        Notification::assertSentToTimes($this->user, BudgetAlert::class, 1);
    }

    public function test_alert_comes_back_after_spending_drops(): void
    {
        Notification::fake();
        $this->budget();
        $expense = $this->spend(900);
        $alerter = new BudgetAlerter;

        $alerter->run();
        $expense->delete();
        $alerter->run();
        $this->assertSame(0, Budget::first()->alert_level);

        $this->spend(900);
        $this->assertSame(1, $alerter->run());
    }

    public function test_respects_the_opt_out_but_keeps_levels_updated(): void
    {
        Notification::fake();
        $this->user->update(['budget_alerts' => false]);
        $this->budget();
        $this->spend(900);

        $this->assertSame(0, (new BudgetAlerter)->run());

        Notification::assertNothingSent();
        $this->assertSame(80, Budget::first()->alert_level);
    }

    public function test_each_user_is_judged_only_by_their_own_spending(): void
    {
        Notification::fake();
        $this->budget();

        $other = User::factory()->create();
        $this->actingAs($other);
        $this->budget();
        $this->spend(950);

        $this->actingAs($this->user);
        $this->spend(100);

        $this->assertSame(1, (new BudgetAlerter)->run());

        Notification::assertSentTo($other, BudgetAlert::class);
        Notification::assertNotSentTo($this->user, BudgetAlert::class);
        $this->assertSame($this->user->id, auth()->id(), 'devolve o usuário que estava logado');
    }

    public function test_email_content(): void
    {
        $this->budget();
        $this->spend(1000);

        $mail = (new BudgetAlert([
            ['name' => 'Mercado', 'spent' => 1000.0, 'limit' => 1000.0, 'level' => 100],
        ]))->toMail($this->user);

        $this->assertSame('Você atingiu o limite de um orçamento', $mail->subject);
        $this->assertStringContainsString('Mercado atingiu o limite: R$ 1.000,00 de R$ 1.000,00.', implode(' ', $mail->introLines));
    }

    public function test_command_runs(): void
    {
        $this->artisan('finance:budget-alerts')->expectsOutput('0 e-mail(s) enviado(s).')->assertSuccessful();
    }
}
