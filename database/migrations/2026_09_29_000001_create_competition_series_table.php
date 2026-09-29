<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seri urutan perlombaan di dalam satu tingkat ("Seri A" / "Seri B").
     *
     * Sumbu baru yang SENGAJA lepas dari grup: dua pasukan di grup yang sama
     * boleh ikut seri berbeda, dan satu seri boleh tersebar di beberapa grup.
     * Yang membedakan keduanya adalah perannya —
     *
     *   grup  -> tabel peringkat (ChampionCalculator) + nomor undian
     *   seri  -> lembar nilai (rubrik) + cakupan juri
     *
     * Bentuknya meniru competition_groups persis, termasuk alasan tabel
     * terpisah (bukan level baru di parent_id): hierarki competition_categories
     * dipakai di semua dropdown "pilih tingkat" dan scopeSelectable(), jadi
     * menambah level di sana merusak pendaftaran.
     */
    public function up(): void
    {
        Schema::create('competition_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['competition_category_id', 'name'], 'competition_series_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_series');
    }
};
