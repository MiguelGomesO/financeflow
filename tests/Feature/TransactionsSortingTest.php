<?php

namespace Tests\Feature;

use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionsSortingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');

        $account = Account::factory()->create();

        // data, descrição, valor, categoria
        foreach ([
            ['2026-10-05', 'banana', -30, 'Mercado'],
            ['2026-10-10', 'Zebra', 1000, 'Renda'],
            ['2026-10-15', 'Almoco', -500, 'Lazer'],
            ['2026-10-20', 'cinema', -80, 'Lazer'],
        ] as [$date, $description, $amount, $category]) {
            Transaction::factory()->create([
                'date' => $date,
                'description' => $description,
                'amount' => $amount,
                'category_id' => Category::where('name', $category)->value('id'),
                'account_id' => $account->id,
            ]);
        }
    }

    public function test_default_order_is_newest_first(): void
    {
        Livewire::test(Transactions::class)
            ->assertSet('sort', 'date')
            ->assertSet('direction', 'desc')
            ->assertSeeInOrder(['cinema', 'Almoco', 'Zebra', 'banana']);
    }

    public function test_sorts_by_date_both_ways(): void
    {
        Livewire::test(Transactions::class)
            ->call('sortBy', 'date')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['banana', 'Zebra', 'Almoco', 'cinema'])
            ->call('sortBy', 'date')
            ->assertSet('direction', 'desc')
            ->assertSeeInOrder(['cinema', 'Almoco', 'Zebra', 'banana']);
    }

    public function test_sorts_by_size_of_the_amount_regardless_of_sign(): void
    {
        Livewire::test(Transactions::class)
            ->call('sortBy', 'amount')
            ->assertSet('sort', 'amount')
            ->assertSet('direction', 'desc')
            ->assertSeeInOrder(['Zebra', 'Almoco', 'cinema', 'banana'])
            ->call('sortBy', 'amount')
            ->assertSeeInOrder(['banana', 'cinema', 'Almoco', 'Zebra']);
    }

    public function test_sorts_by_description_ignoring_case(): void
    {
        Livewire::test(Transactions::class)
            ->call('sortBy', 'description')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['Almoco', 'banana', 'cinema', 'Zebra'])
            ->call('sortBy', 'description')
            ->assertSeeInOrder(['Zebra', 'cinema', 'banana', 'Almoco']);
    }

    public function test_sorts_by_category_name_and_breaks_ties_by_newest(): void
    {
        // Lazer (cinema, Almoco), Mercado (banana), Renda (Zebra)
        Livewire::test(Transactions::class)
            ->call('sortBy', 'category')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['cinema', 'Almoco', 'banana', 'Zebra'])
            ->call('sortBy', 'category')
            ->assertSeeInOrder(['Zebra', 'banana', 'cinema', 'Almoco']);
    }

    public function test_switching_column_uses_that_columns_natural_direction(): void
    {
        Livewire::test(Transactions::class)
            ->call('sortBy', 'description')
            ->assertSet('direction', 'asc')
            ->call('sortBy', 'amount')
            ->assertSet('sort', 'amount')
            ->assertSet('direction', 'desc');
    }

    public function test_sorting_applies_to_every_page_and_goes_back_to_the_first(): void
    {
        $account = Account::first();
        foreach (range(1, 25) as $i) {
            Transaction::factory()->create([
                'date' => '2026-10-02',
                'description' => 'Extra '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'amount' => -$i,
                'category_id' => Category::where('name', 'Mercado')->value('id'),
                'account_id' => $account->id,
            ]);
        }

        // Os 29 lançamentos pelo valor: o maior (1000) abre a página 1 e o menor (1) fecha a página 2.
        $component = Livewire::test(Transactions::class)
            ->call('sortBy', 'amount')
            ->assertSeeInOrder(['Zebra', 'Almoco'])
            ->call('nextPage')
            ->assertSee('Extra 01')
            ->assertDontSee('Zebra');

        $component->call('sortBy', 'amount')->assertSet('paginators.page', 1);
    }

    public function test_sorting_keeps_the_filters(): void
    {
        Livewire::test(Transactions::class)
            ->set('type', 'expense')
            ->call('sortBy', 'amount')
            ->assertSet('type', 'expense')
            ->assertDontSee('Zebra')
            ->assertSeeInOrder(['Almoco', 'cinema', 'banana']);
    }

    public function test_unknown_column_is_ignored_and_bad_url_values_fall_back(): void
    {
        Livewire::test(Transactions::class)
            ->call('sortBy', 'user_id; drop table')
            ->assertSet('sort', 'date')
            ->assertSet('direction', 'desc');

        Livewire::withQueryParams(['sort' => 'nope', 'direction' => 'sideways'])
            ->test(Transactions::class)
            ->assertSet('sort', 'date')
            ->assertSet('direction', 'desc');

        Livewire::withQueryParams(['sort' => 'amount', 'direction' => 'asc'])
            ->test(Transactions::class)
            ->assertSet('sort', 'amount')
            ->assertSet('direction', 'asc')
            ->assertSeeInOrder(['banana', 'cinema', 'Almoco', 'Zebra']);
    }

    public function test_headers_are_buttons_that_announce_the_sort(): void
    {
        $html = Livewire::test(Transactions::class)->call('sortBy', 'amount')->html();

        $this->assertStringContainsString("wire:click=\"sortBy('category')\"", $html);
        $this->assertMatchesRegularExpression('/aria-sort="descending"[^>]*>\s*<button[^>]*sortBy\(\'amount\'\)/', $html);
        $this->assertMatchesRegularExpression('/aria-sort="none"[^>]*>\s*<button[^>]*sortBy\(\'date\'\)/', $html);
        $this->assertStringContainsString('em ordem decrescente', $html);
    }

    public function test_sorting_is_kept_in_the_page_url(): void
    {
        $this->get(route('transactions', ['sort' => 'amount', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['banana', 'cinema', 'Almoco', 'Zebra']);
    }
}
