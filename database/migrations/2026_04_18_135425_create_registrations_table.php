<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained()->cascadeOnDelete();
            $table->string('nama_sekolah');
            $table->string('npsn');
            $table->string('nama_pelatih');
            $table->string('no_hp');
            $table->string('magic_token', 32)->unique();
            $table->string('logo_sekolah')->nullable();
            $table->string('surat_tugas')->nullable();
            $table->string('danton_nama')->nullable();
            $table->string('danton_foto')->nullable();
            $table->enum('status_berkas', ['Menunggu', 'Terverifikasi', 'Ditolak'])->default('Menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
