<?php

namespace Tests\Feature;

use App\Livewire\Import;
use App\Models\Account;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Support\DescriptionMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
    }

    /** @param  list<array{0: string, 1: string, 2: string, 3: string}>  $entries  [FITID, AAAAMMDD, valor, descrição] */
    private function ofx(array $entries): UploadedFile
    {
        $body = '';
        foreach ($entries as [$id, $date, $amount, $memo]) {
            $body .= "<STMTTRN>\n<TRNTYPE>DEBIT\n<DTPOSTED>{$date}\n<TRNAMT>{$amount}\n<FITID>{$id}\n<MEMO>{$memo}\n</STMTTRN>\n";
        }

        return UploadedFile::fake()->createWithContent(
            'extrato.ofx',
            "OFXHEADER:100\n\n<OFX><BANKMSGSRSV1><STMTTRNRS><STMTRS><BANKTRANLIST>\n{$body}</BANKTRANLIST></STMTRS></STMTTRNRS></BANKMSGSRSV1></OFX>",
        );
    }

    private function existing(string $description, float $amount, string $date, ?string $externalId = null, ?Account $account = null): Transaction
    {
        return Transaction::factory()->create([
            'account_id' => ($account ?? $this->account)->id,
            'description' => $description,
            'amount' => $amount,
            'date' => $date,
            'external_id' => $externalId,
        ]);
    }

    public function test_description_similarity(): void
    {
        $this->assertTrue(DescriptionMatch::similar('PADARIA SAO JOSE 0123', 'Padaria São José'));
        $this->assertTrue(DescriptionMatch::similar('UBER *TRIP', 'Uber'));
        $this->assertTrue(DescriptionMatch::similar('Netflix.com', 'NETFLIX'));
        $this->assertTrue(DescriptionMatch::similar('Notebook PARC 02/10', 'Notebook'));
        $this->assertFalse(DescriptionMatch::similar('Mercado', 'Uber'));
        $this->assertFalse(DescriptionMatch::similar('Aluguel', 'Academia'));
        $this->assertFalse(DescriptionMatch::similar('123', '456'));
        $this->assertFalse(DescriptionMatch::similar('', 'Uber'));
    }

    public function test_reimporting_an_ofx_file_does_not_duplicate_anything(): void
    {
        $entries = [['A1', '20261005', '-45.90', 'NETFLIX'], ['A2', '20261006', '-30.00', 'FARMACIA']];

        Livewire::test(Import::class)->set('file', $this->ofx($entries))->call('read')->call('import')->assertSet('done.imported', 2);

        $this->assertSame(['A1', 'A2'], Transaction::orderBy('external_id')->pluck('external_id')->all());

        Livewire::test(Import::class)
            ->set('file', $this->ofx($entries))
            ->call('read')
            ->assertSet('rows.0.duplicate', true)
            ->assertSet('rows.1.duplicate', true)
            ->assertSet('rows.0.selected', false)
            ->assertSee('desmarcadas')
            ->call('import')
            ->assertSet('done.imported', 0);

        $this->assertSame(2, Transaction::count());
    }

    public function test_ofx_match_survives_a_renamed_transaction(): void
    {
        $this->existing('Assinatura de filmes', -45.90, '2026-10-05', 'A1');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261005', '-45.90', 'NETFLIX']]))
            ->call('read')
            ->assertSet('rows.0.duplicate', true);
    }

    public function test_two_identical_purchases_with_different_codes_are_both_kept(): void
    {
        $this->existing('CAFE', -8, '2026-10-06', 'A1');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261006', '-8.00', 'CAFE'], ['A2', '20261006', '-8.00', 'CAFE']]))
            ->call('read')
            ->assertSet('rows.0.duplicate', true)
            ->assertSet('rows.1.duplicate', false)
            ->assertSet('rows.1.similarTo', null)
            ->assertSet('rows.1.selected', true);
    }

    public function test_the_bank_code_wins_even_when_the_rows_come_in_another_order(): void
    {
        $this->existing('CAFE', -8, '2026-10-06', 'A1');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A2', '20261006', '-8.00', 'CAFE'], ['A1', '20261006', '-8.00', 'CAFE']]))
            ->call('read')
            ->assertSet('rows.0.duplicate', false)
            ->assertSet('rows.1.duplicate', true);
    }

    public function test_import_skips_a_code_already_saved_since_the_preview(): void
    {
        $component = Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261005', '-45.90', 'NETFLIX'], ['A2', '20261006', '-30.00', 'FARMACIA']]))
            ->call('read');

        // Outra aba importou o mesmo arquivo depois que esta prévia foi aberta.
        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261005', '-45.90', 'NETFLIX']]))
            ->call('read')
            ->call('import');

        $component->call('import')->assertSet('done.imported', 1)->assertSet('done.skipped', 1);

        $this->assertSame(2, Transaction::count());
    }

    public function test_codes_are_per_account(): void
    {
        $other = Account::factory()->create();
        $this->existing('NETFLIX', -45.90, '2026-10-05', 'A1', $other);

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261005', '-45.90', 'NETFLIX']]))
            ->call('read')
            ->assertSet('rows.0.duplicate', false)
            ->assertSet('rows.0.selected', true);
    }

    public function test_flags_a_similar_manual_entry_a_couple_of_days_apart(): void
    {
        $this->existing('Padaria', -23.50, '2026-10-04');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261006', '-23.50', 'PADARIA SAO JOSE 0123']]))
            ->call('read')
            ->assertSet('rows.0.duplicate', false)
            ->assertSet('rows.0.similarTo', 'Padaria · 04/10')
            ->assertSet('rows.0.selected', false)
            ->assertSee('Parecido com')
            ->call('toggleAll', true)
            ->call('import')
            ->assertSet('done.imported', 1);
    }

    public function test_does_not_flag_different_amount_distant_date_or_unrelated_description(): void
    {
        $this->existing('Padaria', -23.50, '2026-10-01');
        $this->existing('Padaria', -20.00, '2026-10-12');
        $this->existing('Farmácia', -15.00, '2026-10-12');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([
                ['A1', '20261012', '-23.50', 'PADARIA'],   // valor igual, mas 11 dias depois
                ['A2', '20261012', '-23.50', 'PADARIA'],   // idem
                ['A3', '20261012', '-15.00', 'ACADEMIA'],  // mesmo valor e dia, descrição diferente
            ]))
            ->call('read')
            ->assertSet('rows.0.similarTo', null)
            ->assertSet('rows.1.similarTo', null)
            ->assertSet('rows.2.similarTo', null)
            ->assertSet('rows.0.selected', true);
    }

    public function test_one_existing_transaction_matches_only_one_row(): void
    {
        $this->existing('Uber', -18, '2026-10-05');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261005', '-18.00', 'UBER *TRIP'], ['A2', '20261006', '-18.00', 'UBER *TRIP']]))
            ->call('read')
            ->assertSet('rows.0.selected', false)
            ->assertSet('rows.1.selected', true);
    }

    public function test_an_exact_match_is_not_stolen_by_a_similar_one(): void
    {
        $this->existing('Mercado', -50, '2026-10-05');

        Livewire::test(Import::class)
            ->set('file', $this->sheet("Data;Descrição;Valor\n03/10/2026;MERCADO CENTRAL;-50,00\n05/10/2026;Mercado;-50,00\n"))
            ->call('read')
            ->assertSet('rows.0.duplicate', false)
            ->assertSet('rows.0.similarTo', null)   // o único "Mercado" já pertence à linha exata
            ->assertSet('rows.0.selected', true)
            ->assertSet('rows.1.duplicate', true);
    }

    public function test_spreadsheets_without_codes_still_use_date_amount_and_description(): void
    {
        $this->existing('Mercado', -250, '2026-10-06');

        Livewire::test(Import::class)
            ->set('file', $this->sheet("Data;Descrição;Valor\n06/10/2026;mercado;-250,00\n07/10/2026;Salário;5.000,00\n"))
            ->call('read')
            ->assertSet('rows.0.duplicate', true)
            ->assertSet('rows.1.duplicate', false)
            ->assertSet('rows.1.selected', true);
    }

    public function test_card_statements_check_the_card_not_the_accounts(): void
    {
        $card = CreditCard::factory()->create();
        $this->existing('Uber', -18, '2026-10-05');

        Livewire::test(Import::class)
            ->set('source', "card:{$card->id}")
            ->set('file', $this->ofx([['A1', '20261005', '-18.00', 'Uber']]))
            ->call('read')
            ->assertSet('rows.0.similarTo', null)
            ->assertSet('rows.0.selected', true);
    }

    public function test_changing_the_destination_recomputes_the_flags(): void
    {
        $other = Account::factory()->create();
        $this->existing('NETFLIX', -45.90, '2026-10-05', 'A1');

        Livewire::test(Import::class)
            ->set('file', $this->ofx([['A1', '20261005', '-45.90', 'NETFLIX']]))
            ->call('read')
            ->assertSet('rows.0.duplicate', true)
            ->set('source', "account:{$other->id}")
            ->assertSet('rows.0.duplicate', false)
            ->assertSet('rows.0.selected', true);
    }

    /** Planilha .xlsx a partir de texto separado por ";". */
    private function sheet(string $body): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(array_map(
            fn ($line) => explode(';', $line),
            array_filter(explode("\n", $body)),
        ), null, 'A1', true);

        $path = tempnam(sys_get_temp_dir(), 'ff');
        (new Xlsx($spreadsheet))->save($path);
        $bytes = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('extrato.xlsx', $bytes);
    }
}
