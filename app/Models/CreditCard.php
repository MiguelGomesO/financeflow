<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'last4', 'limit', 'close_day', 'due_day'];

    protected function casts(): array
    {
        return [
            'limit' => 'float',
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

    /** Valor da fatura em aberto (compras menos estornos do ciclo atual). */
    public function currentBill(?CarbonInterface $today = null): float
    {
        [$start, $end] = $this->currentCycle($today);

        return round(-(float) $this->transactions()->betweenDates($start, $end)->sum('amount'), 2);
    }
}
