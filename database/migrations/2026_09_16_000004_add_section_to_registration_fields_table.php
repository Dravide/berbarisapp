<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah `section` + `sub_fields` ke registration_fields.
 *
 * `section` menentukan kartu tempat field dirender di portal/PDF (umum,
 * pelatih, danton). `sub_fields` hanya dipakai baris type=group — daftar input
 * berulang milik satu field, dengan mesin simpan yang sudah ada (tabel
 * participants untuk grup "peserta").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_fields', function (Blueprint $table) {
            // umum | pelatih | danton
            $table->string('section', 20)->default('umum')->after('type');
            $table->json('sub_fields')->nullable()->after('options');
        });

        // Baris lama belum punya section: nama pelatih & no HP satu kartu.
        DB::table('registration_fields')
            ->whereIn('field_key', ['nama_pelatih', 'no_hp'])
            ->update(['section' => 'pelatih']);
    }

    public function down(): void
    {
        Schema::table('registration_fields', function (Blueprint $table) {
            $table->dropColumn(['section', 'sub_fields']);
        });
    }
};
