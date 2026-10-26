<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Lançamento que se repete todo mês no mesmo dia (salário, aluguel, assinaturas). */
class RecurringTransaction extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'description', 'amount', 'category_id', 'account_id', 'credit_card_id',
        'day_of_month', 'starts_on', 'ends_on', 'active', 'generated_until',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'day_of_month' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'active' => 'boolean',
            'generated_until' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getSourceNameAttribute(): ?string
    {
        return $this->account?->name ?? $this->creditCard?->name;
    }

    /** Dia do mês efetivo: dia 31 vira o último dia em meses mais curtos. */
    public function dateIn(CarbonInterface $month): CarbonImmutable
    {
        $month = CarbonImmutable::instance($month)->startOfMonth();

        return $month->day(min($this->day_of_month, $month->daysInMonth));
    }

    /**
     * Datas de ocorrência no intervalo (ambas inclusive), respeitando início e fim.
     *
     * @return list<CarbonImmutable>
     */
    public function occurrences(CarbonInterface $from, CarbonInterface $to): array
    {
        $from = CarbonImmutable::instance($from)->startOfDay()->max($this->starts_on->startOfDay());
        $to = CarbonImmutable::instance($to)->startOfDay();

        if ($this->ends_on !== null) {
            $to = $to->min($this->ends_on->startOfDay());
        }

        $dates = [];

        for ($month = $from->startOfMonth(); $month->lessThanOrEqualTo($to); $month = $month->addMonth()) {
            $date = $this->dateIn($month);

            if ($date->greaterThanOrEqualTo($from) && $date->lessThanOrEqualTo($to)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /** Próximo lançamento a partir de hoje, ou null se a recorrência já terminou. */
    public function nextDate(?CarbonInterface $today = null): ?CarbonImmutable
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $horizon = $this->ends_on?->startOfDay() ?? $today->addYears(2);

        return $this->occurrences($today, $horizon)[0] ?? null;
    }
}
