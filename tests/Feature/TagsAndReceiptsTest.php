<?php

namespace Tests\Feature;

use App\Livewire\CreateTransaction;
use App\Livewire\EditTransaction;
use App\Livewire\Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TagsAndReceiptsTest extends TestCase
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

    private function create(array $form = [], ?UploadedFile $file = null): \Livewire\Features\SupportTesting\Testable
    {
        $component = Livewire::test(CreateTransaction::class)
            ->set('form.description', 'Almoço')
            ->set('form.amount', '50,00')
            ->set('form.categoryId', (string) Category::where('name', 'Mercado')->value('id'))
            ->set('form.source', "account:{$this->account->id}")
            ->set('form.date', '2026-10-21');

        foreach ($form as $key => $value) {
            $component->set("form.{$key}", $value);
        }

        if ($file) {
            $component->set('form.attachment', $file);
        }

        return $component->call('save');
    }

    private function names(Transaction $transaction): array
    {
        return $transaction->tags()->pluck('name')->all();
    }

    public function test_creates_a_transaction_with_cleaned_up_tags(): void
    {
        $this->create(['tags' => '  Viagem ,trabalho;;  viagem,  mesa   redonda ']);

        $this->assertSame(['mesa redonda', 'trabalho', 'Viagem'], $this->names(Transaction::sole()));
        $this->assertSame(3, Tag::count());
    }

    public function test_reuses_existing_tags_ignoring_case(): void
    {
        $this->create(['tags' => 'Viagem']);
        $this->create(['tags' => 'viagem, extra']);

        $this->assertSame(['extra', 'Viagem'], Tag::orderByRaw('lower(name)')->pluck('name')->all());
        $this->assertSame(2, Tag::whereHas('transactions')->where('name', 'Viagem')->first()->transactions()->count());
    }

    public function test_validates_tags(): void
    {
        $this->create(['tags' => 'a, b, c, d, e, f, g, h, i'])->assertHasErrors('form.tags');
        $this->create(['tags' => str_repeat('x', 31)])->assertHasErrors('form.tags');

        $this->assertSame(0, Transaction::count());
    }

    public function test_a_transaction_without_tags_or_receipt_still_saves(): void
    {
        $this->create();

        $transaction = Transaction::sole();
        $this->assertSame([], $this->names($transaction));
        $this->assertNull($transaction->attachment_path);
    }

    public function test_stores_the_receipt_on_the_private_disk(): void
    {
        $this->create(file: UploadedFile::fake()->image('nota.jpg'));

        $path = Transaction::sole()->attachment_path;

        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_rejects_receipts_of_the_wrong_type_or_size(): void
    {
        $this->create(file: UploadedFile::fake()->create('nota.txt', 10, 'text/plain'))->assertHasErrors('form.attachment');
        $this->create(file: UploadedFile::fake()->create('nota.pdf', 6000, 'application/pdf'))->assertHasErrors('form.attachment');

        $this->assertSame(0, Transaction::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_installments_share_tags_and_a_single_receipt_file(): void
    {
        $card = CreditCard::factory()->create();

        $this->create(
            ['source' => "card:{$card->id}", 'installments' => '3', 'tags' => 'casa'],
            UploadedFile::fake()->image('nota.png'),
        );

        $rows = Transaction::orderBy('installment_number')->get();

        $this->assertCount(3, $rows);
        $this->assertCount(1, $rows->pluck('attachment_path')->unique());
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $rows->each(fn ($t) => $this->assertSame(['casa'], $this->names($t)));

        // O arquivo só some junto com a última parcela.
        $rows[0]->delete();
        $rows[1]->delete();
        $this->assertCount(1, Storage::disk('local')->allFiles());

        $rows[2]->delete();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_owner_can_open_the_receipt_and_others_cannot(): void
    {
        $this->create(file: UploadedFile::fake()->image('nota.jpg'));
        $transaction = Transaction::sole();

        $this->get(route('receipts.show', $transaction))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->actingAs(User::factory()->create());
        $this->get(route('receipts.show', $transaction))->assertNotFound();

        auth()->logout();
        $this->get(route('receipts.show', $transaction))->assertRedirect(route('login'));
    }

    public function test_receipt_route_is_404_without_a_receipt(): void
    {
        $this->create();

        $this->get(route('receipts.show', Transaction::sole()))->assertNotFound();
    }

    public function test_edit_modal_loads_tags_and_syncs_changes(): void
    {
        $this->create(['tags' => 'viagem, trabalho']);
        $transaction = Transaction::sole();

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->assertSet('form.tags', 'trabalho, viagem')
            ->set('form.tags', 'viagem, família')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['família', 'viagem'], $this->names($transaction));
    }

    public function test_replacing_the_receipt_removes_the_old_file(): void
    {
        $this->create(file: UploadedFile::fake()->image('a.jpg'));
        $transaction = Transaction::sole();
        $old = $transaction->attachment_path;

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->assertSet('hasAttachment', true)
            ->set('form.attachment', UploadedFile::fake()->image('b.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $new = $transaction->fresh()->attachment_path;

        $this->assertNotSame($old, $new);
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($new);
    }

    public function test_removing_the_receipt(): void
    {
        $this->create(file: UploadedFile::fake()->image('a.jpg'));
        $transaction = Transaction::sole();
        $path = $transaction->attachment_path;

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->call('removeReceipt')
            ->assertSee('será removido')
            ->call('save');

        $this->assertNull($transaction->fresh()->attachment_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_saving_without_touching_the_receipt_keeps_it(): void
    {
        $this->create(file: UploadedFile::fake()->image('a.jpg'));
        $transaction = Transaction::sole();
        $path = $transaction->attachment_path;

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->set('form.description', 'Almoço de domingo')
            ->call('save');

        $this->assertSame($path, $transaction->fresh()->attachment_path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_deleting_the_transaction_removes_its_receipt(): void
    {
        $this->create(file: UploadedFile::fake()->image('a.jpg'));
        $transaction = Transaction::sole();

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: $transaction->id)
            ->call('delete');

        $this->assertSame(0, Transaction::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_deleting_all_installments_removes_the_shared_receipt(): void
    {
        $card = CreditCard::factory()->create();
        $this->create(['source' => "card:{$card->id}", 'installments' => '3'], UploadedFile::fake()->image('a.jpg'));

        Livewire::test(EditTransaction::class)
            ->dispatch('edit-transaction', id: Transaction::where('installment_number', 2)->value('id'))
            ->call('deleteInstallments', 'all');

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_list_shows_tags_and_the_receipt_link_and_filters_by_tag(): void
    {
        $this->create(['description' => 'Hotel', 'tags' => 'viagem'], UploadedFile::fake()->image('a.jpg'));
        $this->create(['description' => 'Mensalidade academia', 'tags' => 'rotina']);

        $this->get(route('transactions'))
            ->assertOk()
            ->assertSee('#viagem')
            ->assertSee('#rotina')
            ->assertSee(route('receipts.show', Transaction::where('description', 'Hotel')->first()));

        $viagem = Tag::where('name', 'viagem')->first();

        Livewire::test(Transactions::class)
            ->set('tag', (string) $viagem->id)
            ->assertSee('Hotel')
            ->assertDontSee('Mensalidade academia')
            ->assertSee('Limpar filtros')
            ->call('clearFilters')
            ->assertSet('tag', '')
            ->assertSee('Mensalidade academia');
    }

    public function test_unknown_tag_filter_falls_back(): void
    {
        Livewire::test(Transactions::class)->set('tag', '999')->assertSet('tag', '');
    }

    public function test_tags_are_private_to_each_user(): void
    {
        $this->create(['tags' => 'segredo']);

        $this->actingAs(User::factory()->create());

        $this->assertSame(0, Tag::count());
        Livewire::test(Transactions::class)->assertDontSee('segredo');
        Livewire::test(CreateTransaction::class)->assertDontSee('+ segredo');
    }

    public function test_suggests_the_most_used_tags_in_the_form(): void
    {
        $this->create(['tags' => 'viagem']);
        $this->create(['tags' => 'viagem, casa']);

        Livewire::test(CreateTransaction::class)->assertSeeInOrder(['+ viagem', '+ casa']);
    }

    public function test_export_includes_tags(): void
    {
        $this->create(['tags' => 'viagem, casa']);

        $component = Livewire::test(Transactions::class)->call('export', 'csv');
        $csv = base64_decode($component->effects['download']['content']);

        $this->assertStringContainsString('Etiquetas', $csv);
        $this->assertStringContainsString('casa, viagem', $csv);
    }
}
