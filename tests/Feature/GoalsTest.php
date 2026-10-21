<?php

namespace Tests\Feature;

use App\Livewire\Goals;
use App\Support\GoalProgress;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsTest extends TestCase
{
    public function test_page_renders_goals_and_totals(): void
    {
        $this->get(route('goals'))
            ->assertOk()
            ->assertSee('Reserva de emergência')
            ->assertSee('Prazo: jun/2027')
            ->assertSee('R$ 27.000')        // total guardado
            ->assertSee('R$ 58.500')        // falta para concluir tudo
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

    public function test_creates_a_goal(): void
    {
        Livewire::test(Goals::class)
            ->call('create')
            ->assertDispatched('open-modal', name: 'goal-form')
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
    }

    public function test_edits_a_goal_without_touching_saved_amount(): void
    {
        Livewire::test(Goals::class)
            ->call('edit', 2)
            ->assertSet('form.name', 'Viagem de férias')
            ->assertSet('form.target', '6.000,00')
            ->set('form.name', 'Viagem para o Chile')
            ->set('form.target', '8.000')
            ->call('saveGoal')
            ->assertHasNoErrors()
            ->assertSee('Viagem para o Chile')
            ->assertSee('de R$ 8.000')
            ->assertSee('R$ 2.400');
    }

    public function test_deletes_a_goal_and_its_deposits(): void
    {
        Livewire::test(Goals::class)
            ->call('edit', 4)
            ->call('deleteGoal')
            ->assertDontSee('Entrada do apartamento')
            ->assertDontSee('+ R$ 900');
    }

    public function test_deposit_increases_saved_and_is_listed(): void
    {
        Livewire::test(Goals::class)
            ->call('startDeposit', 3)
            ->assertDispatched('open-modal', name: 'deposit')
            ->set('deposit.amount', '900,00')
            ->set('deposit.account', 'Poupança')
            ->call('saveDeposit')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'deposit')
            ->assertSee('Concluída')
            ->assertSee('Meta atingida')
            ->assertSee('+ R$ 900');
    }

    public function test_deposit_requires_positive_amount(): void
    {
        Livewire::test(Goals::class)
            ->call('startDeposit', 1)
            ->set('deposit.amount', '')
            ->call('saveDeposit')
            ->assertHasErrors(['deposit.amount']);
    }
}
