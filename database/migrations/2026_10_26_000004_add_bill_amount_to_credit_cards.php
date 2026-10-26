<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            // Fatura atual informada à mão; vale até o fechamento do ciclo em que foi informada.
            $table->decimal('bill_amount', 12, 2)->nullable()->after('used_limit');
            $table->date('bill_until')->nullable()->after('bill_amount');
        });
    }

    public function down(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropColumn(['bill_amount', 'bill_until']);
        });
    }
};
