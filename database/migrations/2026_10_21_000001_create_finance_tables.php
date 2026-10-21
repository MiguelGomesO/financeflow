<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->unique();
            $table->enum('type', ['income', 'expense']);
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('type', 30);
            $table->string('institution', 40)->nullable();
            $table->string('note', 120)->nullable();
            // Saldo no momento do cadastro; o saldo atual soma as transações da conta.
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->char('last4', 4);
            $table->decimal('limit', 12, 2);
            $table->unsignedTinyInteger('close_day');
            $table->unsignedTinyInteger('due_day');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('description', 120);
            // Com sinal: entradas positivas, saídas negativas.
            $table->decimal('amount', 12, 2);
            $table->date('date')->index();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            // Uma transação sai de uma conta OU de um cartão.
            $table->foreignId('account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('credit_card_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7); // "Y-m"
            $table->decimal('limit', 12, 2);
            $table->timestamps();

            $table->unique(['category_id', 'month']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->decimal('target', 12, 2);
            $table->char('due_month', 7); // "Y-m"
            $table->timestamps();
        });

        Schema::create('goal_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_deposits');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('credit_cards');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('categories');
    }
};
