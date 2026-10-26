<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            // Limite usado informado pelo banco/loja; vazio = usa a fatura em aberto.
            $table->decimal('used_limit', 12, 2)->nullable()->after('limit');
        });
    }

    public function down(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropColumn('used_limit');
        });
    }
};
