<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tabelas cujos registros pertencem a um usuário (categorias continuam compartilhadas). */
    private const TABLES = ['accounts', 'credit_cards', 'transactions', 'budgets', 'goals', 'goal_deposits', 'transfers'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            // Anulável para não quebrar dados já existentes: `php artisan finance:claim` entrega-os a um usuário.
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        // Um orçamento por categoria e mês, agora por usuário. O índice simples
        // em category_id mantém a chave estrangeira válida ao trocar o único.
        Schema::table('budgets', function (Blueprint $table) {
            $table->index('category_id');
            $table->unique(['user_id', 'category_id', 'month']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->unique(['category_id', 'month']);
        });

        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'category_id', 'month']);
            $table->dropIndex(['category_id']);
        });

        foreach (self::TABLES as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
