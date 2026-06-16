<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->boolean('surat_tugas_required')->default(true)->after('vote_price');
            $table->boolean('kwitansi_required')->default(true)->after('surat_tugas_required');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['surat_tugas_required', 'kwitansi_required']);
        });
    }
};
