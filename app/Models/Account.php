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

    public function transfersOut(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    /** Carrega as somas usadas por "balance" numa consulta só (evita N+1). */
    public function scopeWithBalance(Builder $query): void
    {
        $query
            ->withSum('transactions', 'amount')
            ->withSum('transfersIn', 'amount')
            ->withSum('transfersOut', 'amount');
    }

    /** Saldo atual: saldo inicial + transações + transferências recebidas − enviadas. */
    public function getBalanceAttribute(): float
    {
        return round(
            $this->opening_balance
            + $this->loadedSum('transactions_sum_amount', fn () => $this->transactions()->sum('amount'))
            + $this->loadedSum('transfers_in_sum_amount', fn () => $this->transfersIn()->sum('amount'))
            - $this->loadedSum('transfers_out_sum_amount', fn () => $this->transfersOut()->sum('amount')),
            2,
        );
    }

    /** Conta tem movimentação? Contas com histórico não podem ser excluídas. */
    public function hasActivity(): bool
    {
        return $this->transactions()->exists()
            || $this->transfersIn()->exists()
            || $this->transfersOut()->exists();
    }

    private function loadedSum(string $attribute, callable $query): float
    {
        return (float) (array_key_exists($attribute, $this->attributes) ? $this->attributes[$attribute] : $query());
    }
}
