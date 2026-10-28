<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('tag_transaction', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();

            $table->primary(['tag_id', 'transaction_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            // Comprovante no disco privado "local"; vazio = sem comprovante.
            $table->string('attachment_path')->nullable()->after('installment_total');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });

        Schema::dropIfExists('tag_transaction');
        Schema::dropIfExists('tags');
    }
};
