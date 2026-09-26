<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grup penilaian di dalam satu tingkat lomba ("Grup A" / "Grup B").
     *
     * Sengaja tabel terpisah, bukan level baru di parent_id: hierarki
     * competition_categories dipakai di semua dropdown "pilih tingkat"
     * (Drawing, ScoreRecap, Scoring, hasil, champions, scoreboard) dan
     * scopeSelectable(), jadi menambah level di sana merusak pendaftaran.
     */
    public function up(): void
    {
        Schema::create('competition_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['competition_category_id', 'name'], 'competition_groups_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_groups');
    }
};
