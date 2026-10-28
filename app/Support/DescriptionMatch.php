<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Compara descrições de lançamentos que o banco e o usuário escrevem cada um do seu jeito. */
class DescriptionMatch
{
    /** Porcentagem mínima de semelhança (similar_text) para duas descrições valerem como parecidas. */
    private const THRESHOLD = 70;

    /**
     * "PADARIA SAO JOSE 0123" e "Padaria São José" são parecidas; "Uber" e "Mercado" não.
     * Ignora acentos, maiúsculas, números e pontuação.
     */
    public static function similar(string $a, string $b): bool
    {
        $a = self::normalize($a);
        $b = self::normalize($b);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        if (strlen($a) >= 4 && strlen($b) >= 4 && (str_contains($a, $b) || str_contains($b, $a))) {
            return true;
        }

        similar_text($a, $b, $percent);

        return $percent >= self::THRESHOLD;
    }

    private static function normalize(string $text): string
    {
        $text = preg_replace('/[^a-z]+/', ' ', strtolower(Str::ascii($text)));

        return trim($text);
    }
}
