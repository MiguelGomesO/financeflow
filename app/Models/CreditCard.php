<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['name', 'last4', 'limit', 'used_limit', 'bill_amount', 'bill_until', 'close_day', 'due_day'];

    protected static function booted(): void
    {
        // A fatura informada vale só até o fechamento do ciclo em que foi informada.
        static::saving(function (CreditCard $card) {
            if ($card->bill_amount === null) {
                $card->bill_until = null;
            } elseif ($card->isDirty(['bill_amount', 'close_day']) || $card->bill_until === null) {
                $card->bill_until = $card->currentCycle()[1]->toDateString();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'limit' => 'float',
            'used_limit' => 'float',
            'bill_amount' => 'float',
            'bill_until' => 'date',
            'close_day' => 'integer',
            'due_day' => 'integer',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Ciclo da fatura em aberto: do dia seguinte ao último fechamento até o
     * próximo fechamento (inclusive). Fechamento no dia 31 vira o último dia
     * em meses mais curtos.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function currentCycle(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $closeIn = fn (CarbonImmutable $month) => $month->startOfMonth()->day(min($this->close_day, $month->daysInMonth));

        $closeThisMonth = $closeIn($today);

        if ($today->lessThanOrEqualTo($closeThisMonth)) {
            return [$closeIn($today->startOfMonth()->subMonth())->addDay(), $closeThisMonth];
        }

        return [$closeThisMonth->addDay(), $closeIn($today->startOfMonth()->addMonth())];
    }

    /**
     * Faturas dos próximos ciclos (depois da aberta) que já têm compras, como as parcelas.
     * Cada uma traz o vencimento e o valor.
     *
     * @return list<array{due: CarbonImmutable, amount: float}>
     */
    public function upcomingBills(int $cycles = 3, ?CarbonInterface $today = null): array
    {
        $onDay = fn (CarbonImmutable $month, int $day) => $month->startOfMonth()->day(min($day, $month->daysInMonth));

        $ranges = [];
        $end = $this->currentCycle($today)[1];

        for ($i = 0; $i < $cycles; $i++) {
            $start = $end->addDay();
            $end = $onDay($end->addMonth(), $this->close_day);
            $ranges[] = [$start, $end];
        }

        $totals = array_fill(0, $cycles, 0.0);

        $this->transactions()
            ->betweenDates($ranges[0][0], end($ranges)[1])
            ->get(['date', 'amount'])
            ->each(function (Transaction $transaction) use ($ranges, &$totals) {
                foreach ($ranges as $i => [$start, $end]) {
                    if ($transaction->date->between($start, $end)) {
                        $totals[$i] -= $transaction->amount;
                    }
                }
            });

        $bills = [];

        foreach ($ranges as $i => [, $closing]) {
            // Vence no mês do fechamento ou no seguinte, conforme o dia do vencimento.
            $due = $onDay($closing->addMonths($this->due_day <= $this->close_day ? 1 : 0), $this->due_day);

            if (round($totals[$i], 2) != 0.0) {
                $bills[] = ['due' => $due, 'amount' => round($totals[$i], 2)];
            }
        }

        return $bills;
    }

    /**
     * Limite usado: o valor informado à mão (como o banco ou a loja mostra) ou,
     * sem ele, a fatura em aberto. Parcelas futuras não entram na conta.
     */
    public function usedLimit(?CarbonInterface $today = null): float
    {
        return $this->used_limit ?? max(0, $this->openBill($today));
    }

    /** Fatura informada à mão, se ainda dentro do ciclo em que foi informada. */
    public function billOverride(?CarbonInterface $today = null): ?float
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return $this->bill_amount !== null && $this->bill_until !== null && $today->lessThanOrEqualTo($this->bill_until)
            ? $this->bill_amount
            : null;
    }

    /** Fatura em aberto: o valor informado à mão ou a soma das compras do ciclo. */
    public function openBill(?CarbonInterface $today = null): float
    {
        return $this->billOverride($today) ?? $this->currentBill($today);
    }

    /** Valor da fatura em aberto (compras menos estornos do ciclo atual). */
    public function currentBill(?CarbonInterface $today = null): float
    {
        [$start, $end] = $this->currentCycle($today);

        return round(-(float) $this->transactions()->betweenDates($start, $end)->sum('amount'), 2);
    }
}
