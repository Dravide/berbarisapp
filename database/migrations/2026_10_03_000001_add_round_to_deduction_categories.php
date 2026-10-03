<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dimensi babak pada kelompok pengurangan.
     *
     * Pengurangan per kategori sebenarnya sudah ikut babak lewat rubriknya
     * (assessment_category_id), tetapi pengurangan TINGKAT tidak menempel pada
     * rubrik mana pun — ia hanya milik satu tingkat lomba. Tanpa kolom ini,
     * sanksi fase grup ikut memotong NILAI AKHIR di fase final, dan tak ada
     * satu pun nilai di baris itu yang bisa membedakan keduanya.
     *
     * Semantiknya SENGAJA identik dengan assessment_categories.competition_round_id
     * (NULL = berlaku semua babak), supaya seluruh baris lama tetap berarti
     * persis seperti sebelumnya dan saringan babak bisa memakai klausa yang sama.
     *
     * Nullable, nullOnDelete, bukan cascade — menghapus babak hanya melepas
     * batasannya, tidak boleh menghapus kelompok pengurangan beserta kriterianya
     * (preseden competition_series_id dan competition_group_id).
     */
    public function up(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->foreignId('competition_round_id')->nullable()->after('competition_category_id')
                ->constrained('competition_rounds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deduction_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_round_id');
        });
    }
};
