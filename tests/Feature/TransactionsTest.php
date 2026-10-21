<?php

namespace Tests\Feature;

use App\Livewire\Transactions;
use App\Support\Money;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionsTest extends TestCase
{
    public function test_page_renders(): void
    {
        $this->get(route('transactions'))
            ->assertOk()
            ->assertSee('Transações')
            ->assertSee('Supermercado');
    }

    public function test_filters_by_search_type_and_category(): void
    {
        Livewire::test(Transactions::class)
            ->set('search', 'super')
            ->assertSee('Supermercado')
            ->assertDontSee('Aluguel')
            ->set('search', '')
            ->set('type', 'income')
            ->assertSee('Salário')
            ->assertDontSee('Supermercado')
            ->set('type', 'all')
            ->set('category', 'Moradia')
            ->assertSee('Conta de luz')
            ->assertDontSee('Cinema');
    }

    public function test_invalid_filters_fall_back_to_defaults(): void
    {
        $this->get(route('transactions', ['type' => 'x', 'category' => 'y']))->assertOk();

        Livewire::test(Transactions::class)
            ->set('type', 'bogus')
            ->assertSet('type', 'all');
    }

    public function test_saves_a_new_transaction(): void
    {
        Livewire::test(Transactions::class)
            ->set('form.type', 'expense')
            ->set('form.description', 'Pizzaria')
            ->set('form.amount', '1.234,50')
            ->set('form.category', 'Lazer')
            ->set('form.date', '2026-10-22')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('transaction-saved')
            ->assertSet('form.description', '')
            ->assertSee('Pizzaria')
            ->assertSee('− R$ 1.234,50');
    }

    public function test_validates_the_form(): void
    {
        Livewire::test(Transactions::class)
            ->set('form.description', '')
            ->set('form.amount', '0')
            ->call('save')
            ->assertHasErrors(['form.description', 'form.amount']);
    }

    public function test_parses_brazilian_amounts(): void
    {
        $this->assertSame(1234.56, Money::parse('1.234,56'));
        $this->assertSame(25.9, Money::parse('25,90'));
        $this->assertSame(12.5, Money::parse('12.50'));
        $this->assertSame(8000.0, Money::parse('8.000'));
        $this->assertSame(1250000.0, Money::parse('1.250.000'));
        $this->assertSame(0.0, Money::parse('abc'));
    }
}
