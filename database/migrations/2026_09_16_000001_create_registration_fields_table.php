<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->string('field_key', 50);
            $table->string('label');
            // text | textarea | number | date | select | file | image
            $table->string('type', 20)->default('text');
            $table->json('options')->nullable();
            $table->string('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            // Field bawaan (nama_sekolah, npsn, surat_tugas, ...) — tidak boleh dihapus,
            // hanya boleh dinonaktifkan atau diubah labelnya.
            $table->boolean('is_builtin')->default(false);
            // Nama kolom di tabel registrations tempat nilai field ini juga ditulis.
            // Null untuk field buatan panitia (nilainya hidup di registration_field_values).
            $table->string('builtin_source', 50)->nullable();
            $table->unsignedInteger('max_kb')->nullable();
            $table->string('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['eventner_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_fields');
    }
};
