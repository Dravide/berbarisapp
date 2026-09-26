<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot kriteria untuk Tie Break — kembaran `champion_criteria`, sama
 * seperti `champion_tiebreak` terhadap `champion_assessment`. Aturan
 * kosong/terisi identik: kosong = seluruh kriteria sub yang dicentang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('champion_tiebreak_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('champion_category_id')->constrained('champion_categories')->cascadeOnDelete();
            $table->foreignId('assessment_criteria_id')->constrained('assessment_criterias')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['champion_category_id', 'assessment_criteria_id'], 'champion_tiebreak_criteria_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('champion_tiebreak_criteria');
    }
};
