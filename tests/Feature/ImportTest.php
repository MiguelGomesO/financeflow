<?php

namespace Tests\Feature;

use App\Livewire\Import;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create(['name' => 'Conta corrente']);
    }

    /** Monta uma planilha .xlsx a partir de linhas de texto separadas por ";" (ou ","). */
    private function sheet(string $body, string $name = 'extrato.xlsx'): UploadedFile
    {
        $delimiter = str_contains($body, ';') ? ';' : ',';
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(array_map(
            fn ($line) => explode($delimiter, $line),
            array_filter(explode("\n", $body)),
        ), null, 'A1', true);

        $path = tempnam(sys_get_temp_dir(), 'ff');
        (new Xlsx($spreadsheet))->save($path);
        $bytes = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private const STATEMENT = "Data;Descrição;Valor\n05/10/2026;Salário;5.000,00\n06/10/2026;Mercado;-250,00\n";

    public function test_shows_a_preview_without_saving_anything(): void
    {
        Livewire::test(Import::class)
            ->set('file', $this->sheet(self::STATEMENT))
            ->call('read')
            ->assertHasNoErrors()
            ->assertSet('rows.0.description', 'Salário')
            ->assertSet('rows.1.amount', -250)
            ->assertSee('Importar 2');

        $this->assertSame(0, Transaction::count());
    }

    public function test_imports_the_selected_rows_into_the_account(): void
    {
        $income = Category::where('name', 'Renda')->value('id');
        $expense = Category::where('name', 'Mercado')->value('id');

        Livewire::test(Import::class)
            ->set('file', $this->sheet(self::STATEMENT))
            ->call('read')
            ->set('rows.0.categoryId', (string) $income)
            ->set('rows.1.categoryId', (string) $expense)
            ->call('import')
            ->assertSet('done.imported', 2)
            ->assertSet('rows', []);

        $this->assertDatabaseHas('transactions', [
            'description' => 'Mercado',
            'amount' => -250,
            'date' => '2026-10-06',
            'account_id' => $this->account->id,
            'category_id' => $expense,
            'user_id' => $this->user->id,
        ]);
        $this->assertSame(2, Transaction::count());
    }

    public function test_unselected_rows_are_not_imported(): void
    {
        Livewire::test(Import::class)
            ->set('file', $this->sheet(self::STATEMENT))
            ->call('read')
            ->set('rows.0.selected', false)
            ->call('import')
            ->assertSet('done.imported', 1);

        $this->assertDatabaseMissing('transactions', ['description' => 'Salário']);
    }

    public function test_flags_rows_that_were_already_imported_and_leaves_them_unchecked(): void
    {
        Transaction::factory()->create([
            'account_id' => $this->account->id,
            'description' => 'mercado',
            'amount' => -250,
            'date' => '2026-10-06',
        ]);

        Livewire::test(Import::class)
            ->set('file', $this->sheet(self::STATEMENT))
            ->call('read')
            ->assertSet('rows.0.duplicate', false)
            ->assertSet('rows.1.duplicate', true)
            ->assertSet('rows.1.selected', false)
            ->call('import')
            ->assertSet('done.imported', 1);
    }

    public function test_two_identical_rows_are_kept_when_only_one_exists_already(): void
    {
        Transaction::factory()->create(['account_id' => $this->account->id, 'description' => 'Café', 'amount' => -8, 'date' => '2026-10-06']);

        Livewire::test(Import::class)
            ->set('file', $this->sheet("Data;Descrição;Valor\n06/10/2026;Café;-8,00\n06/10/2026;Café;-8,00\n"))
            ->call('read')
            ->assertSet('rows.0.duplicate', true)
            ->assertSet('rows.1.duplicate', false);
    }

    public function test_suggests_the_category_used_before_for_the_same_description(): void
    {
        $lazer = Category::where('name', 'Lazer')->value('id');
        Transaction::factory()->create(['account_id' => $this->account->id, 'description' => 'Mercado', 'amount' => -10, 'date' => '2026-01-01', 'category_id' => $lazer]);

        Livewire::test(Import::class)
            ->set('file', $this->sheet(self::STATEMENT))
            ->call('read')
            ->assertSet('rows.1.categoryId', (string) $lazer);
    }

    public function test_card_statements_treat_positive_values_as_purchases(): void
    {
        $card = CreditCard::factory()->create();

        Livewire::test(Import::class)
            ->set('file', $this->sheet("date,title,amount\n2026-10-03,Padaria,24.50\n2026-10-04,Posto,180.00\n"))
            ->set('source', "card:{$card->id}")
            ->call('read')
            ->assertSet('invert', true)
            ->assertSet('rows.0.amount', -24.5)
            ->call('import')
            ->assertSet('done.imported', 2);

        $this->assertDatabaseHas('transactions', ['description' => 'Posto', 'amount' => -180, 'credit_card_id' => $card->id, 'account_id' => null]);
    }

    public function test_income_rows_cannot_go_into_a_card(): void
    {
        $card = CreditCard::factory()->create();

        Livewire::test(Import::class)
            ->set('file', $this->sheet("Data;Descrição;Valor\n03/10/2026;Compra;-50,00\n04/10/2026;Estorno;20,00\n"))
            ->set('source', "card:{$card->id}")
            ->call('read')
            ->assertSet('rows.1.error', 'Entrada em cartão (pagamento ou estorno)')
            ->call('import')
            ->assertSet('done.imported', 1);
    }

    public function test_rejects_unreadable_and_wrong_files(): void
    {
        Livewire::test(Import::class)
            ->set('file', $this->sheet("Nome;Idade\nAna;30\n"))
            ->call('read')
            ->assertHasErrors('file');

        Livewire::test(Import::class)
            ->set('file', UploadedFile::fake()->create('foto.png', 10))
            ->call('read')
            ->assertHasErrors('file');

        Livewire::test(Import::class)->call('read')->assertHasErrors('file');
    }

    public function test_cannot_import_into_another_users_account(): void
    {
        $this->actingAs($other = User::factory()->create());
        $foreign = Account::factory()->create();
        $this->actingAs($this->user);

        Livewire::test(Import::class)
            ->set('file', $this->sheet(self::STATEMENT))
            ->set('source', "account:{$foreign->id}")
            ->call('read')
            ->assertHasErrors('source');
    }

    public function test_the_transactions_page_offers_the_import(): void
    {
        $this->get(route('transactions'))->assertSeeLivewire(Import::class)->assertSee('Importar extrato');
    }
}
