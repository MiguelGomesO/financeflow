<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Código do lançamento no banco (FITID do OFX): reimportar o mesmo arquivo não duplica.
            $table->string('external_id', 64)->nullable()->after('attachment_path')->index();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
