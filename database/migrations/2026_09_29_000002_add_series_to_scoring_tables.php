<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dimensi seri pada penugasan.
     *
     * registrations.competition_series_id       — peserta ikut seri mana.
     * assessment_categories.competition_series_id — rubrik ini milik seri mana;
     *   dari sini pula cakupan juri ditentukan, karena juri terikat ke rubrik,
     *   bukan ke tingkat lomba. NULL = berlaku semua seri.
     *
     * Semantiknya SENGAJA identik dengan competition_group_id yang sudah ada
     * (NULL = berlaku di mana saja), supaya backfill bisa mengisi kolom ini
     * dari grup tanpa mengubah arti satu pun nilai.
     *
     * Kolom grup TIDAK dihapus: peringkat dan nomor undian masih memakainya.
     *
     * Semua nullable, nullOnDelete, bukan cascade — menghapus seri hanya
     * melepas penugasan, tidak boleh menghapus peserta maupun rubriknya
     * (preseden venue_id dan competition_group_id).
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('competition_series_id')->nullable()->after('competition_group_id')
                ->constrained('competition_series')->nullOnDelete();
        });

        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->foreignId('competition_series_id')->nullable()->after('competition_round_id')
                ->constrained('competition_series')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_series_id');
        });

        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_series_id');
        });
    }
};
