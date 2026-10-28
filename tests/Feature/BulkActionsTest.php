<?php

namespace Tests\Feature;

use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Receipts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 10:00:00');
        Storage::fake('local');

        $this->account = Account::factory()->create();
    }

    private function category(string $name): int
    {
        return Category::where('name', $name)->value('id');
    }

    private function add(string $description, float $amount, string $category = 'Mercado', string $date = '2026-10-10'): Transaction
    {
        return Transaction::factory()->create([
            'description' => $description,
            'amount' => $amount,
            'category_id' => $this->category($category),
            'account_id' => $this->account->id,
            'date' => $date,
        ]);
    }

    private function ids(Transaction ...$transactions): array
    {
        return array_map(fn (Transaction $t) => (string) $t->id, $transactions);
    }

    public function test_deletes_the_selected_transactions_only(): void
    {
        $a = $this->add('Compra A', -10);
        $b = $this->add('Compra B', -20);
        $keep = $this->add('Compra C', -30);

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a, $b))
            ->call('deleteSelected')
            ->assertSet('selected', [])
            ->assertSet('notice', '2 transações excluídas.')
            ->assertDispatched('transactions-changed')
            ->assertSee('2 transações excluídas.')
            ->assertSee('Compra C')
            ->assertDontSee('Compra A');

        $this->assertSame([$keep->id], Transaction::pluck('id')->all());
    }

    public function test_singular_notice(): void
    {
        $a = $this->add('Compra A', -10);

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a))
            ->call('deleteSelected')
            ->assertSet('notice', '1 transação excluída.');
    }

    public function test_deleting_cleans_up_receipts_and_tags(): void
    {
        $a = $this->add('Compra A', -10);
        $a->update(['attachment_path' => Receipts::store(UploadedFile::fake()->image('n.jpg'))]);
        $a->tags()->attach(Tag::create(['name' => 'x'])->id);

        Livewire::test(Transactions::class)->set('selected', $this->ids($a))->call('deleteSelected');

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, DB::table('tag_transaction')->count());
    }

    public function test_changes_the_category_of_the_selected_expenses(): void
    {
        $a = $this->add('Compra A', -10);
        $b = $this->add('Compra B', -20);
        $other = $this->add('Compra C', -30);

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a, $b))
            ->set('bulkCategory', (string) $this->category('Lazer'))
            ->call('categorizeSelected')
            ->assertHasNoErrors()
            ->assertSet('selected', [])
            ->assertSet('bulkCategory', '')
            ->assertSet('notice', '2 transações movidas para Lazer.');

        $this->assertSame($this->category('Lazer'), $a->fresh()->category_id);
        $this->assertSame($this->category('Lazer'), $b->fresh()->category_id);
        $this->assertSame($this->category('Mercado'), $other->fresh()->category_id);
    }

    public function test_income_selection_only_accepts_income_categories(): void
    {
        $a = $this->add('Salário', 1000, 'Renda');

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a))
            ->set('bulkCategory', (string) $this->category('Lazer'))
            ->call('categorizeSelected')
            ->assertHasErrors('bulkCategory');

        $this->assertSame($this->category('Renda'), $a->fresh()->category_id);
    }

    public function test_mixed_selection_cannot_change_category_but_can_be_deleted(): void
    {
        $expense = $this->add('Compra A', -10);
        $income = $this->add('Salário', 1000, 'Renda');

        $component = Livewire::test(Transactions::class)
            ->set('selected', $this->ids($expense, $income))
            ->assertSee('marque só despesas ou só receitas')
            ->set('bulkCategory', (string) $this->category('Lazer'))
            ->call('categorizeSelected')
            ->assertHasErrors('bulkCategory');

        $this->assertSame($this->category('Mercado'), $expense->fresh()->category_id);

        $component->call('deleteSelected');
        $this->assertSame(0, Transaction::count());
    }

    public function test_requires_a_category_to_be_chosen(): void
    {
        $a = $this->add('Compra A', -10);

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a))
            ->call('categorizeSelected')
            ->assertHasErrors('bulkCategory');
    }

    public function test_ids_of_other_users_are_ignored(): void
    {
        $mine = $this->add('Minha compra', -10);

        $this->actingAs(User::factory()->create());
        $account = Account::factory()->create();
        $theirs = Transaction::factory()->create([
            'description' => 'Compra do outro',
            'amount' => -50,
            'category_id' => $this->category('Mercado'),
            'account_id' => $account->id,
            'date' => '2026-10-10',
        ]);

        $this->actingAs($this->user);

        Livewire::test(Transactions::class)
            ->set('selected', [(string) $mine->id, (string) $theirs->id, 'abc', '999999'])
            ->assertSee('1 marcada')
            ->set('bulkCategory', (string) $this->category('Lazer'))
            ->call('categorizeSelected');

        $this->assertSame($this->category('Lazer'), $mine->fresh()->category_id);
        $this->assertSame($this->category('Mercado'), Transaction::withoutGlobalScopes()->find($theirs->id)->category_id);

        Livewire::test(Transactions::class)->set('selected', [(string) $theirs->id])->call('deleteSelected');

        $this->assertNotNull(Transaction::withoutGlobalScopes()->find($theirs->id));
    }

    public function test_selects_everything_in_the_filter_across_pages(): void
    {
        foreach (range(1, 25) as $i) {
            $this->add("Mercado {$i}", -10);
        }
        $this->add('Salário', 1000, 'Renda');

        Livewire::test(Transactions::class)
            ->set('type', 'expense')
            ->call('selectFiltered')
            ->assertSee('25 marcadas')
            ->call('deleteSelected');

        $this->assertSame(['Salário'], Transaction::pluck('description')->all());
    }

    public function test_changing_a_filter_clears_the_selection(): void
    {
        $a = $this->add('Compra A', -10);

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a))
            ->set('search', 'Compra')
            ->assertSet('selected', [])
            ->set('selected', $this->ids($a))
            ->call('clearFilters')
            ->assertSet('selected', []);
    }

    public function test_selection_survives_page_changes(): void
    {
        foreach (range(1, 25) as $i) {
            $this->add("Mercado {$i}", -10, date: '2026-10-'.str_pad((string) min($i, 20), 2, '0', STR_PAD_LEFT));
        }
        $first = Transaction::orderByDesc('date')->orderByDesc('id')->first();

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($first))
            ->call('nextPage')
            ->assertSet('selected', $this->ids($first))
            ->assertSee('1 marcada');
    }

    public function test_bar_is_hidden_without_a_selection_and_rows_have_checkboxes(): void
    {
        $a = $this->add('Compra A', -10);

        Livewire::test(Transactions::class)
            ->assertDontSee('Ações em massa')
            ->assertSee('Marcar Compra A')
            ->assertSee('Marcar todas desta página')
            ->set('selected', $this->ids($a))
            ->assertSee('Ações em massa')
            ->assertSee('1 marcada')
            ->assertSee('Trocar categoria');
    }

    public function test_editing_or_deleting_elsewhere_prunes_the_selection(): void
    {
        $a = $this->add('Compra A', -10);
        $b = $this->add('Compra B', -20);

        Livewire::test(Transactions::class)
            ->set('selected', $this->ids($a, $b))
            ->tap(fn () => $a->delete())
            ->dispatch('transactions-changed')
            ->assertSet('selected', $this->ids($b));
    }
}
