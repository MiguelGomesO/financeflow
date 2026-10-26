<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Dados de um usuário só. Com alguém logado, toda consulta enxerga apenas os
 * registros dele e todo registro novo nasce com o seu user_id. Fora do console,
 * sem login, nada é visível (falha fechada). No console (seeders, comandos,
 * testes) não há filtro.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('user', function (Builder $query) {
            $column = $query->getModel()->qualifyColumn('user_id');

            if (Auth::check()) {
                $query->where($column, Auth::id());
            } elseif (! app()->runningInConsole()) {
                $query->whereRaw('1 = 0');
            }
        });

        static::creating(function ($model) {
            $model->user_id ??= Auth::id();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
