<?php

namespace Tests\Feature;

use App\Livewire\Goals;
use App\Models\Account;
use App\Models\Goal;
use App\Models\GoalDeposit;
use App\Support\GoalProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    private function goal(string $name, float $target, string $due, float $saved = 0): Goal
    {
        $goal = Goal::factory()->create(['name' => $name, 'target' => $target, 'due_month' => $due]);

        if ($saved > 0) {
            GoalDeposit::factory()->for($goal)->create(['amount' => $saved, 'date' => '2026-06-01']);
        }

        return $goal;
    }

    public function test_renders_empty_state(): void
    {
        $this->get(route('goals'))
            ->assertOk()
            ->assertSee('Você ainda não tem metas.')
            ->assertSee('Nenhum depósito ainda.');
    }

    public function test_renders_goals_and_totals(): void
    {
        $this->goal('Reserva de emergência', 15000, '2027-06', 9000);
        $this->goal('Notebook novo', 4500, '2027-01', 3600);

        $this->get(route('goals'))
            ->assertOk()
            ->assertSeeInOrder(['Notebook novo', 'Reserva de emergência']) // prazo mais próximo primeiro
            ->assertSee('Prazo: jun/2027')
            ->assertSee('R$ 12.600')        // total guardado
            ->assertSee('R$ 6.900')         // falta para concluir tudo
            ->assertSee('Guardar R$ 750 por mês')
            ->assertSee('Quase lá');        // Notebook: 80%
    }

    public function test_monthly_amount_follows_the_deadline(): void
    {
        $progress = GoalProgress::for(['saved' => 9000, 'target' => 15000, 'due' => '2027-06'], '2026-10');
        $this->assertEqualsWithDelta(750, $progress['monthly'], 0.01);

        $overdue = GoalProgress::for(['saved' => 100, 'target' => 400, 'due' => '2026-08'], '2026-10');
        $this->assertSame('Prazo vencido', $overdue['status']);
        $this->assertEqualsWithDelta(300, $overdue['monthly'], 0.01);

        $done = GoalProgress::for(['saved' => 500, 'target' => 400, 'due' => '2027-01'], '2026-10');
        $this->assertSame('Concluída', $done['status']);
        $this->assertEquals(100, $done['percent']);
    }

    public function test_creates_a_goal_with_an_initial_deposit(): void
    {
        Livewire::test(Goals::class)
            ->call('create')
            ->assertDispatched('open-modal', name: 'goal-form')
            ->assertSet('form.due', '2027-10')
            ->set('form.name', 'Bicicleta')
            ->set('form.target', '2.000,00')
            ->set('form.saved', '500')
            ->set('form.due', '2027-03')
            ->call('saveGoal')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'goal-form')
            ->assertSee('Bicicleta')
            ->assertSee('Prazo: mar/2027')
            ->assertSee('Guardar R$ 300 por mês');

        $goal = Goal::where('name', 'Bicicleta')->firstOrFail();
        $this->assertSame(2000.0, $goal->target);
        $this->assertSame(500.0, $goal->saved);
    }

    public function test_creating_without_initial_amount_adds_no_deposit(): void
    {
        Livewire::test(Goals::class)
            ->call('create')
            ->set('form.name', 'Curso')
            ->set('form.target', '1.200')
            ->call('saveGoal')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('goal_deposits', 0);
    }

    public function test_validates_new_goal(): void
    {
        Livewire::test(Goals::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.target', '0')
            ->set('form.due', '2020-01')
            ->call('saveGoal')
            ->assertHasErrors(['form.name', 'form.target', 'form.due']);

        $this->assertDatabaseCount('goals', 0);
    }

    public function test_edits_a_goal_without_touching_saved_amount(): void
    {
        $goal = $this->goal('Viagem de férias', 6000, '2027-12', 2400);

        Livewire::test(Goals::class)
            ->call('edit', $goal->id)
            ->assertSet('form.name', 'Viagem de férias')
            ->assertSet('form.target', '6.000,00')
            ->set('form.name', 'Viagem para o Chile')
            ->set('form.target', '8.000')
            ->call('saveGoal')
            ->assertHasNoErrors()
            ->assertSee('Viagem para o Chile')
            ->assertSee('de R$ 8.000')
            ->assertSee('R$ 2.400');

        $this->assertSame(2400.0, $goal->refresh()->saved);
    }

    public function test_overdue_goal_can_be_edited_keeping_its_deadline(): void
    {
        $goal = $this->goal('Antiga', 1000, '2026-08');

        Livewire::test(Goals::class)
            ->call('edit', $goal->id)
            ->set('form.name', 'Antiga renomeada')
            ->call('saveGoal')
            ->assertHasNoErrors();

        $this->assertSame('Antiga renomeada', $goal->refresh()->name);
    }

    public function test_deletes_a_goal_and_its_deposits(): void
    {
        $goal = $this->goal('Entrada do apartamento', 60000, '2030-12', 900);

        Livewire::test(Goals::class)
            ->call('edit', $goal->id)
            ->call('deleteGoal')
            ->assertDontSee('Entrada do apartamento');

        $this->assertModelMissing($goal);
        $this->assertDatabaseCount('goal_deposits', 0);
    }

    public function test_deposit_increases_saved_and_is_listed(): void
    {
        $savings = Account::factory()->create(['name' => 'Poupança', 'opening_balance' => 1000]);
        $goal = $this->goal('Notebook novo', 4500, '2027-01', 3600);

        Livewire::test(Goals::class)
            ->call('startDeposit', $goal->id)
            ->assertDispatched('open-modal', name: 'deposit')
            ->assertSet('deposit.account', (string) $savings->id)
            ->assertSet('deposit.date', '2026-10-21')
            ->set('deposit.amount', '900,00')
            ->call('saveDeposit')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'deposit')
            ->assertSee('Concluída')
            ->assertSee('Meta atingida')
            ->assertSee('+ R$ 900,00');

        $this->assertSame(4500.0, $goal->refresh()->saved);
        $this->assertDatabaseHas('goal_deposits', ['goal_id' => $goal->id, 'account_id' => $savings->id, 'amount' => 900]);
        // O depósito reserva o dinheiro; o saldo da conta não muda.
        $this->assertSame(1000.0, $savings->refresh()->balance);
    }

    public function test_deposit_requires_positive_amount(): void
    {
        $goal = $this->goal('Reserva', 1000, '2027-06');

        Livewire::test(Goals::class)
            ->call('startDeposit', $goal->id)
            ->set('deposit.amount', '')
            ->call('saveDeposit')
            ->assertHasErrors(['deposit.amount']);

        $this->assertDatabaseCount('goal_deposits', 0);
    }

    public function test_undoes_a_deposit(): void
    {
        $goal = $this->goal('Reserva', 1000, '2027-06', 300);
        $deposit = $goal->deposits()->first();

        Livewire::test(Goals::class)
            ->call('deleteDeposit', $deposit->id)
            ->assertSee('Nenhum depósito ainda.');

        $this->assertSame(0.0, $goal->refresh()->saved);
    }
}
