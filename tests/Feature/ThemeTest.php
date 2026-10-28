<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_layout_applies_the_saved_theme_before_first_paint(): void
    {
        $html = $this->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString("localStorage.getItem('theme')", $html);
        $this->assertStringContainsString("classList.add('dark')", $html);
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);

        // O script precisa vir antes do CSS (do build ou do servidor do Vite) para a página não piscar clara.
        $script = strpos($html, "localStorage.getItem('theme')");
        $stylesheet = preg_match('~<link[^>]+rel="stylesheet"[^>]*(app[^"]*\.css|resources/css/app\.css)~', $html, $m, PREG_OFFSET_CAPTURE) ? $m[0][1] : false;

        $this->assertNotFalse($stylesheet, 'a página deveria carregar o CSS do app');
        $this->assertLessThan($stylesheet, $script);
    }

    public function test_guest_pages_follow_the_theme_too(): void
    {
        auth()->logout();

        $this->get(route('login'))->assertOk()->assertSee("localStorage.getItem('theme')", false);
    }

    public function test_sidebar_has_the_theme_toggle(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('x-data="themeSwitcher"', false)
            ->assertSee('Modo escuro');
    }

    public function test_profile_offers_automatic_light_and_dark(): void
    {
        $this->get(route('profile'))
            ->assertOk()
            ->assertSee('Aparência')
            ->assertSeeInOrder(['Automático', 'Claro', 'Escuro'])
            ->assertSee("set('dark')", false);
    }

    /**
     * Cores fixas quebram o modo escuro: o claro/escuro vem de tokens (surface, strong, *-fg).
     * Vale para as views, o JS e as classes montadas em PHP.
     */
    public function test_views_use_theme_tokens_instead_of_fixed_colors(): void
    {
        $offenders = [];

        foreach ($this->sources() as $path) {
            foreach (file($path) as $number => $line) {
                // Fundo que não muda de tema (mint, ink): o branco e o texto escuro dali são de propósito.
                $fixedBackground = str_contains($line, 'bg-mint') || str_contains($line, 'border-white/20') || preg_match('/(?<![\w-])bg-ink(?![\w-])/', $line);

                $rules = [
                    'text-(brand|brand-dark|expense|expense-dark)' => 'use a variante -fg',
                ];

                if (! $fixedBackground) {
                    $rules['bg-white'] = 'use bg-surface';
                    $rules['text-ink'] = 'use text-strong';
                }

                foreach ($rules as $class => $hint) {
                    if (preg_match('~(?<![\w/-])'.$class.'(?![\w/-])~', $line)) {
                        $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).':'.($number + 1)." {$class} ({$hint})";
                    }
                }
            }
        }

        $this->assertSame([], $offenders, "Cores fixas fora dos tokens do tema:\n".implode("\n", $offenders));
    }

    /** @return list<string> */
    private function sources(): array
    {
        $skip = ['welcome.blade.php', 'bank-logo.blade.php'];
        $files = [];

        foreach ([resource_path('views'), resource_path('js'), app_path()] as $dir) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                $isSource = in_array($file->getExtension(), ['php', 'js'], true);
                $isExport = str_contains(str_replace('\\', '/', $file->getPathname()), 'views/exports/');

                if ($isSource && ! $isExport && ! in_array($file->getFilename(), $skip, true)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
