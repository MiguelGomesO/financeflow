<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('description', 120);
            // Com sinal, como em "transactions": entradas positivas, saídas negativas.
            $table->decimal('amount', 12, 2);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            // Sai de uma conta OU de um cartão.
            $table->foreignId('account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('credit_card_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_month');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->boolean('active')->default(true);
            // Última data já processada: ocorrências até ela não são geradas de novo
            // (assim, excluir um lançamento gerado não o traz de volta).
            $table->date('generated_until')->nullable();
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('recurring_transaction_id')->nullable()->after('credit_card_id')
                ->constrained()->nullOnDelete();
            $table->unique(['recurring_transaction_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['recurring_transaction_id', 'date']);
            $table->dropConstrainedForeignId('recurring_transaction_id');
        });

        Schema::dropIfExists('recurring_transactions');
    }
};
