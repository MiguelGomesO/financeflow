<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = ['name', 'target', 'due_month'];

    protected function casts(): array
    {
        return ['target' => 'float'];
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(GoalDeposit::class);
    }

    /** Total guardado: soma dos depósitos (use withSum('deposits', 'amount') para evitar N+1). */
    public function getSavedAttribute(): float
    {
        return (float) (array_key_exists('deposits_sum_amount', $this->attributes)
            ? $this->deposits_sum_amount
            : $this->deposits()->sum('amount'));
    }
}
