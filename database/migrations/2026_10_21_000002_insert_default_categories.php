<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Categorias iniciais, para o app já funcionar num banco novo. */
return new class extends Migration
{
    private const CATEGORIES = [
        ['name' => 'Mercado', 'type' => 'expense'],
        ['name' => 'Moradia', 'type' => 'expense'],
        ['name' => 'Transporte', 'type' => 'expense'],
        ['name' => 'Lazer', 'type' => 'expense'],
        ['name' => 'Saúde', 'type' => 'expense'],
        ['name' => 'Assinaturas', 'type' => 'expense'],
        ['name' => 'Renda', 'type' => 'income'],
    ];

    public function up(): void
    {
        $now = now();

        DB::table('categories')->insertOrIgnore(array_map(
            fn (array $category) => $category + ['created_at' => $now, 'updated_at' => $now],
            self::CATEGORIES,
        ));
    }

    public function down(): void
    {
        DB::table('categories')
            ->whereIn('name', array_column(self::CATEGORIES, 'name'))
            ->whereNotExists(fn ($q) => $q->from('transactions')->whereColumn('transactions.category_id', 'categories.id'))
            ->delete();
    }
};
