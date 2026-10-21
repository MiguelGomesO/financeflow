<?php

namespace Tests\Feature;

use App\Livewire\Budgets;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetsTest extends TestCase
{
    public function test_page_renders_current_month(): void
    {
        $this->get(route('budgets'))
            ->assertOk()
            ->assertSee('Orçamentos')
            ->assertSee('Outubro de 2026')
            ->assertSee('R$ 4.970')         // orçado
            ->assertSee('R$ 4.297')         // gasto
            ->assertSee('dia 21 de 31')
            ->assertSee('Estourado');       // Lazer: 520 de 450
    }

    public function test_switching_to_a_past_month(): void
    {
        Livewire::test(Budgets::class)
            ->set('month', '2026-09')
            ->assertSee('R$ 4.670')
            ->assertSee('Mês encerrado')
            ->assertDontSee('dia 21 de 31');
    }

    public function test_invalid_month_falls_back_to_current(): void
    {
        $this->get(route('budgets', ['month' => '1999-01']))->assertOk()->assertSee('dia 21 de 31');

        Livewire::test(Budgets::class)
            ->set('month', 'xyz')
            ->assertSet('month', '2026-10');
    }

    public function test_creates_a_budget(): void
    {
        $component = Livewire::test(Budgets::class);

        // Remove "Saúde" do mês para liberar a categoria.
        $component->set('budgets.2026-10', array_values(array_filter(
            $component->get('budgets')['2026-10'],
            fn (array $b) => $b['name'] !== 'Saúde',
        )));

        $component
            ->set('form.category', 'Saúde')
            ->set('form.limit', '350,00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'new-budget')
            ->assertSee('R$ 350 disponíveis');
    }

    public function test_rejects_duplicate_category_and_zero_limit(): void
    {
        Livewire::test(Budgets::class)
            ->set('form.category', 'Mercado')
            ->set('form.limit', '0')
            ->call('save')
            ->assertHasErrors(['form.category', 'form.limit']);
    }
}
