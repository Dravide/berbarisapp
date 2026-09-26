<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot kriteria (level ketiga) untuk rubrik penilaian juara.
 *
 * Pivot sub-kategori (`champion_assessment`) tetap dipakai — ia yang
 * menentukan kategori juara relevan di tingkat lomba mana (isVisibleFor).
 * Pivot ini hanya mempersempit lagi: bila terisi, hanya kriteria inilah
 * yang dihitung. Bila kosong, seluruh kriteria sub yang dicentang dipakai
 * (perilaku sebelum fitur ini ada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('champion_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('champion_category_id')->constrained('champion_categories')->cascadeOnDelete();
            $table->foreignId('assessment_criteria_id')->constrained('assessment_criterias')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['champion_category_id', 'assessment_criteria_id'], 'champion_criteria_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('champion_criteria');
    }
};
