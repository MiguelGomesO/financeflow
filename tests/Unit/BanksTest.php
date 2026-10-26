<?php

namespace Tests\Unit;

use App\Support\Banks;
use Tests\TestCase;

class BanksTest extends TestCase
{
    public function test_finds_banks_ignoring_case_and_accents(): void
    {
        $this->assertSame('nubank', Banks::find('NUBANK')['slug']);
        $this->assertSame('itau', Banks::find('Itaú Unibanco')['slug']);
        $this->assertSame('banco-do-brasil', Banks::find('Conta no BB')['slug']);
        $this->assertSame('c6', Banks::find('C6 Bank')['slug']);
    }

    public function test_uses_the_first_text_that_matches(): void
    {
        $this->assertSame('inter', Banks::find('Banco Inter', 'Cartão Nubank')['slug']);
        $this->assertSame('nubank', Banks::find(null, 'Cartão Principal Nubank')['slug']);
    }

    public function test_does_not_match_inside_other_words(): void
    {
        $this->assertNull(Banks::find('Internet banking'));
        $this->assertNull(Banks::find('Conta salário'));
        $this->assertNull(Banks::find('AlieExpress'));
        $this->assertNull(Banks::find(null, ''));
    }
}
