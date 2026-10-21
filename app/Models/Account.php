<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'institution', 'note', 'opening_balance'];

    protected function casts(): array
    {
        return ['opening_balance' => 'float'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Adiciona a coluna "balance": saldo inicial + transações da conta. */
    public function scopeWithBalance(Builder $query): void
    {
        $query->withSum('transactions', 'amount');
    }

    public function getBalanceAttribute(): float
    {
        $movements = array_key_exists('transactions_sum_amount', $this->attributes)
            ? $this->transactions_sum_amount
            : $this->transactions()->sum('amount');

        return round($this->opening_balance + (float) $movements, 2);
    }
}
