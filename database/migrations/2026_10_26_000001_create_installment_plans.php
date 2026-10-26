<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('description', 110);
            // Valor total da compra; cada parcela vira uma transação no cartão.
            $table->decimal('total', 12, 2);
            $table->unsignedTinyInteger('installments');
            $table->date('first_date');
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('installment_plan_id')->nullable()->after('credit_card_id')
                ->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('installment_plan_id');
        });

        Schema::dropIfExists('installment_plans');
    }
};
