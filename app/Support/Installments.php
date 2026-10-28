<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** Divide o valor de uma compra parcelada no cartão. */
class Installments
{
    public const MAX = 24;

    /**
     * Uma parcela por mês, no dia da compra (dia 31 vira o último dia em meses mais curtos).
     * Os centavos que sobram da divisão ficam na primeira parcela, como nas faturas.
     *
     * @return list<array{number: int, amount: float, date: CarbonImmutable}>
     */
    public static function schedule(float $total, int $count, CarbonInterface $first): array
    {
        $first = CarbonImmutable::instance($first)->startOfDay();
        $cents = (int) round($total * 100);
        $each = intdiv($cents, $count);
        $rest = $cents - $each * $count;

        $parcels = [];
        for ($i = 0; $i < $count; $i++) {
            $parcels[] = [
                'number' => $i + 1,
                'amount' => ($each + ($i === 0 ? $rest : 0)) / 100,
                'date' => $first->addMonthsNoOverflow($i),
            ];
        }

        return $parcels;
    }
}
