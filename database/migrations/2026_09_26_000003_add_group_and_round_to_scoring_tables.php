<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dimensi grup & babak pada penugasan.
     *
     * registrations.competition_group_id  — peserta ada di grup mana (pool panitia).
     * assessment_categories.competition_group_id — rubrik ini milik grup mana; dari
     *   sini pula juri per grup ditentukan, karena juri terikat ke rubrik, bukan
     *   ke tingkat lomba. NULL = berlaku semua grup (perilaku lama).
     * assessment_categories.competition_round_id — rubrik ini milik babak mana.
     *   NULL = berlaku semua babak (perilaku lama).
     *
     * Semua nullable tanpa backfill: data lama tetap sah apa adanya.
     * nullOnDelete, bukan cascade — menghapus grup/babak hanya melepas penugasan,
     * tidak boleh menghapus peserta maupun rubriknya (preseden venue_id).
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('competition_group_id')->nullable()->after('competition_category_id')
                ->constrained('competition_groups')->nullOnDelete();
        });

        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->foreignId('competition_group_id')->nullable()->after('competition_category_id')
                ->constrained('competition_groups')->nullOnDelete();
            $table->foreignId('competition_round_id')->nullable()->after('competition_group_id')
                ->constrained('competition_rounds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_group_id');
        });

        Schema::table('assessment_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_group_id');
            $table->dropConstrainedForeignId('competition_round_id');
        });
    }
};
