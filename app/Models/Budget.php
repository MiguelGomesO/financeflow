<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Budget extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['category_id', 'month', 'limit'];

    protected function casts(): array
    {
        return ['limit' => 'float', 'alert_level' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Adiciona a coluna "spent": quanto foi gasto na categoria dentro do mês do orçamento.
     * Saídas são negativas, então o gasto é o negativo da soma.
     */
    public function scopeWithSpent(Builder $query, string $month): void
    {
        $start = Carbon::createFromFormat('!Y-m', $month);

        $query->addSelect([
            'spent' => Transaction::query()
                ->selectRaw('COALESCE(-SUM(amount), 0)')
                ->whereColumn('transactions.category_id', 'budgets.category_id')
                ->where('amount', '<', 0)
                ->betweenDates($start, $start->copy()->endOfMonth()),
        ]);
    }
}
