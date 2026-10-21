<?php

namespace App\Models;

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
}
