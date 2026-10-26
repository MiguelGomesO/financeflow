<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Principais bancos e fintechs do Brasil: nome, cor da marca e sigla.
 *
 * O logo oficial é opcional: coloque o arquivo em public/images/banks/{slug}.svg
 * (ou .png/.webp) e ele substitui o selo de cor. Sem o arquivo, o selo usa a cor
 * e a sigla do banco.
 */
class Banks
{
    /** @var array<string, array{name: string, bg: string, fg: string, mark: string, aliases: list<string>}> */
    public const LIST = [
        'nubank' => ['name' => 'Nubank', 'bg' => '#820AD1', 'fg' => '#FFFFFF', 'mark' => 'Nu', 'aliases' => ['nubank', 'nu']],
        'itau' => ['name' => 'Itaú', 'bg' => '#EC7000', 'fg' => '#FFFFFF', 'mark' => 'Itaú', 'aliases' => ['itau', 'itau unibanco']],
        'bradesco' => ['name' => 'Bradesco', 'bg' => '#CC092F', 'fg' => '#FFFFFF', 'mark' => 'Bra', 'aliases' => ['bradesco']],
        'banco-do-brasil' => ['name' => 'Banco do Brasil', 'bg' => '#FFEF38', 'fg' => '#0038A8', 'mark' => 'BB', 'aliases' => ['banco do brasil', 'bb']],
        'santander' => ['name' => 'Santander', 'bg' => '#EC0000', 'fg' => '#FFFFFF', 'mark' => 'San', 'aliases' => ['santander']],
        'caixa' => ['name' => 'Caixa', 'bg' => '#005CA9', 'fg' => '#FFFFFF', 'mark' => 'CEF', 'aliases' => ['caixa', 'caixa economica', 'cef']],
        'inter' => ['name' => 'Inter', 'bg' => '#FF7A00', 'fg' => '#FFFFFF', 'mark' => 'In', 'aliases' => ['inter', 'banco inter']],
        'c6' => ['name' => 'C6 Bank', 'bg' => '#242424', 'fg' => '#FFFFFF', 'mark' => 'C6', 'aliases' => ['c6', 'c6 bank']],
        'btg' => ['name' => 'BTG Pactual', 'bg' => '#001E62', 'fg' => '#FFFFFF', 'mark' => 'BTG', 'aliases' => ['btg', 'btg pactual']],
        'xp' => ['name' => 'XP', 'bg' => '#101010', 'fg' => '#FFD100', 'mark' => 'XP', 'aliases' => ['xp', 'xp investimentos']],
        'picpay' => ['name' => 'PicPay', 'bg' => '#21C25E', 'fg' => '#FFFFFF', 'mark' => 'Pic', 'aliases' => ['picpay']],
        'mercado-pago' => ['name' => 'Mercado Pago', 'bg' => '#00B1EA', 'fg' => '#FFFFFF', 'mark' => 'MP', 'aliases' => ['mercado pago', 'mercadopago']],
        'neon' => ['name' => 'Neon', 'bg' => '#00E5E5', 'fg' => '#10203A', 'mark' => 'Ne', 'aliases' => ['neon']],
        'next' => ['name' => 'Next', 'bg' => '#00FF5F', 'fg' => '#10203A', 'mark' => 'Nx', 'aliases' => ['next']],
        'original' => ['name' => 'Banco Original', 'bg' => '#00A859', 'fg' => '#FFFFFF', 'mark' => 'Or', 'aliases' => ['original', 'banco original']],
        'safra' => ['name' => 'Safra', 'bg' => '#0A2A5E', 'fg' => '#FFFFFF', 'mark' => 'Sa', 'aliases' => ['safra']],
        'sicredi' => ['name' => 'Sicredi', 'bg' => '#3FA110', 'fg' => '#FFFFFF', 'mark' => 'Sic', 'aliases' => ['sicredi']],
        'sicoob' => ['name' => 'Sicoob', 'bg' => '#003641', 'fg' => '#7DB61C', 'mark' => 'Sco', 'aliases' => ['sicoob']],
        'banrisul' => ['name' => 'Banrisul', 'bg' => '#0A64B4', 'fg' => '#FFFFFF', 'mark' => 'Ban', 'aliases' => ['banrisul']],
        'will' => ['name' => 'Will Bank', 'bg' => '#FFD800', 'fg' => '#1A1A1A', 'mark' => 'W', 'aliases' => ['will', 'will bank', 'willbank']],
    ];

    /** Nomes para sugerir no cadastro. */
    public static function names(): array
    {
        return array_column(self::LIST, 'name');
    }

    /**
     * Acha o banco pelo primeiro texto que o cite (instituição, depois o nome).
     *
     * @return array{slug: string, name: string, bg: string, fg: string, mark: string, logo: ?string}|null
     */
    public static function find(?string ...$texts): ?array
    {
        foreach ($texts as $text) {
            $haystack = Str::of((string) $text)->ascii()->lower()->toString();

            if ($haystack === '') {
                continue;
            }

            foreach (self::LIST as $slug => $bank) {
                foreach ($bank['aliases'] as $alias) {
                    if (preg_match('/\b'.preg_quote($alias, '/').'\b/', $haystack)) {
                        return ['slug' => $slug, 'logo' => self::logoUrl($slug)] + $bank;
                    }
                }
            }
        }

        return null;
    }

    private static function logoUrl(string $slug): ?string
    {
        foreach (['svg', 'png', 'webp'] as $extension) {
            if (is_file(public_path("images/banks/{$slug}.{$extension}"))) {
                return asset("images/banks/{$slug}.{$extension}");
            }
        }

        return null;
    }
}
