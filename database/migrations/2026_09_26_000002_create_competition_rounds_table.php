<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Babak penilaian di dalam satu tingkat lomba (penyisihan, final, ...).
     *
     * Babak memisahkan baris rubrik: kriteria babak final berbeda dari
     * penyisihan, jadi satu registrasi bisa punya nilai di kedua babak
     * sekaligus tanpa mengubah unique index assessment_scores.
     */
    public function up(): void
    {
        Schema::create('competition_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('preliminary');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['competition_category_id', 'name'], 'competition_rounds_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_rounds');
    }
};
