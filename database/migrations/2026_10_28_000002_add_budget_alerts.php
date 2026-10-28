<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            // Último nível de alerta já enviado por e-mail: 0, 80 ou 100 (% do limite).
            $table->unsignedTinyInteger('alert_level')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('budget_alerts')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropColumn('alert_level');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('budget_alerts');
        });
    }
};
