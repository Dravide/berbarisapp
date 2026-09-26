<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peserta yang lolos ke sebuah babak (daftar finalis).
     *
     * UNIQUE (competition_round_id, registration_id) membuat tombol "Loloskan
     * Top-N" idempoten: menekan dua kali tidak menggandakan finalis.
     * preliminary_total menyimpan total penyisihan saat diloloskan, supaya
     * pratinjau tidak berubah bila nilai penyisihan dikoreksi setelahnya.
     * seed menyimpan peringkat asal (peringkat dalam grupnya).
     */
    public function up(): void
    {
        Schema::create('competition_round_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('competition_round_id')->constrained('competition_rounds')->cascadeOnDelete();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('competition_group_id')->nullable()
                ->constrained('competition_groups')->nullOnDelete();
            $table->unsignedInteger('seed')->nullable();
            $table->decimal('preliminary_total', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['competition_round_id', 'registration_id'], 'competition_round_registrations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_round_registrations');
    }
};
