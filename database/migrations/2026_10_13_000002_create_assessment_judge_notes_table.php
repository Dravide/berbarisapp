<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan evaluasi dan voice note dewan juri per peserta (dan babak jika ada).
     *
     * Juri di tablet dapat merekam audio catatan evaluasi saat mengamati peleton
     * atau mengetik catatan teks. Rekaman ini disajikan ke peserta/pelatih di lembar
     * rincian hasil penilaian dan portal magic link sekolah.
     */
    public function up(): void
    {
        Schema::create('assessment_judge_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('judges')->cascadeOnDelete();
            $table->foreignId('competition_round_id')->nullable()->constrained('competition_rounds')->cascadeOnDelete();
            $table->string('audio_path')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->string('mime_type')->nullable()->default('audio/webm');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['registration_id', 'judge_id', 'competition_round_id'],
                'judge_notes_unique_per_round'
            );
            $table->index(['eventner_id', 'registration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_judge_notes');
    }
};
