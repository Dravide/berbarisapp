<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `wilayah_level` untuk field bertipe `wilayah`.
 *
 * Field asal daerah dulu teks bebas, jadi satu kabupaten bisa tertulis dengan
 * banyak ejaan dan rekapnya tidak bisa diandalkan. Sekarang nilainya dipilih
 * dari api.datawilayah.com, dan kolom ini menentukan sedalam apa dropdownnya:
 *
 *  - `auto`      → ikut `tingkat_perlombaan` event (nasional → provinsi saja,
 *                  tingkat provinsi → provinsi + kabupaten)
 *  - `provinsi` / `kabupaten` / `kecamatan` → dipatok panitia, yang sekaligus
 *                  mewujudkan field "wilayah dinamis" (tiga tingkat sekaligus)
 *
 * Kolom `type` tidak perlu diubah tipenya: sudah varchar(20) dan tipe baru
 * hanya divalidasi lewat aturan Livewire. Yang perlu adalah backfill baris
 * `asal_kabupaten` yang sudah terlanjur tersimpan sebagai `text` — ensureDefaults()
 * tidak menimpa baris lama, jadi tanpa backfill ini tidak ada satu pun field
 * wilayah yang muncul untuk event yang sudah ada.
 *
 * Nilai `registration_field_values` lama sengaja tidak disentuh: teks bebasnya
 * masih sah (rule validasinya meloloskan nilai non-kode), dan menghapusnya
 * berarti membuang isian peserta yang sudah masuk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_fields', function (Blueprint $table) {
            $table->string('wilayah_level', 20)->nullable()->after('type');
        });

        DB::table('registration_fields')
            ->where('field_key', 'asal_kabupaten')
            ->whereNull('builtin_source')
            ->where('type', 'text')
            ->update(['type' => 'wilayah', 'wilayah_level' => 'auto']);
    }

    public function down(): void
    {
        DB::table('registration_fields')
            ->where('field_key', 'asal_kabupaten')
            ->whereNull('builtin_source')
            ->where('type', 'wilayah')
            ->update(['type' => 'text']);

        Schema::table('registration_fields', function (Blueprint $table) {
            $table->dropColumn('wilayah_level');
        });
    }
};
