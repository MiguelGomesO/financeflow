<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Etiqueta livre do usuário ("viagem", "trabalho"), ligada a quantas transações quiser. */
class Tag extends Model
{
    use BelongsToUser;

    public const MAX_PER_TRANSACTION = 8;

    public const MAX_LENGTH = 30;

    protected $fillable = ['name'];

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class);
    }

    /**
     * Ids das etiquetas com esses nomes, criando as que não existem.
     * "Viagem" e "viagem" são a mesma etiqueta; vale a grafia da primeira vez.
     *
     * @param  list<string>  $names
     * @return list<int>
     */
    public static function idsFor(array $names): array
    {
        $existing = static::query()->get(['id', 'name'])->keyBy(fn (Tag $tag) => mb_strtolower($tag->name));

        return array_map(
            fn (string $name) => ($existing[mb_strtolower($name)] ?? static::create(['name' => $name]))->id,
            $names,
        );
    }
}
