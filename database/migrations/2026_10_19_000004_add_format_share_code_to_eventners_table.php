<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode berbagi FORMAT PENILAIAN — sejajar dengan drawing_code & scoring_code.
 *
 * Satu kode per event, bukan satu baris per pasangan event: yang dibagikan
 * adalah "silakan baca rubrik saya", bukan izin khusus untuk satu penerima.
 * format_share_revoked_at menyimpan jejak pencabutan supaya modal import bisa
 * membedakan "belum pernah dibagikan" dari "sudah dicabut" — dua kalimat yang
 * menuntut tindakan berbeda dari panitia (minta kode vs minta dibagikan lagi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->string('format_share_code')->nullable()->after('scoring_code');
            $table->timestamp('format_share_revoked_at')->nullable()->after('format_share_code');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn(['format_share_code', 'format_share_revoked_at']);
        });
    }
};
