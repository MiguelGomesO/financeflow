<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Parcelas da mesma compra dividem o grupo; cada uma é uma transação no cartão.
            $table->uuid('installment_group')->nullable()->index()->after('recurring_transaction_id');
            $table->unsignedTinyInteger('installment_number')->nullable()->after('installment_group');
            $table->unsignedTinyInteger('installment_total')->nullable()->after('installment_number');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['installment_group']);
            $table->dropColumn(['installment_group', 'installment_number', 'installment_total']);
        });
    }
};
