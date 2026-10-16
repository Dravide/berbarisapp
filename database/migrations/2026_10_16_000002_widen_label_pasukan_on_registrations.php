<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nama Pasukan tidak lagi sebatas label pendek.
 *
 * Kolomnya dibuat varchar(10) saat isinya hanya penanda pasukan A/B/C. Sejak
 * builder formulir membolehkan pendaftar mengisi "Nama Pasukan" sendiri
 * (mis. "Pleton Garuda"), 10 karakter terlalu sempit: MySQL strict mode
 * menolak simpanan yang lebih panjang dengan "Data too long for column
 * 'label_pasukan'", dan pendaftaran gagal dengan 500 di langkah terakhir —
 * padahal formulirnya sendiri sudah meloloskan isian itu.
 *
 * Lebar 255 dipilih agar sejajar dengan kolom teks bebas lain di tabel ini.
 * Batas isian yang lebih ketat (253 karakter — menyisakan dua karakter untuk
 * sufiks " A"/" B" milik pendaftar multi-pasukan) hidup di
 * PendaftarImport::MAX_LABEL_PASUKAN, dan itulah yang dilihat pendaftar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('label_pasukan', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Mengembalikan lebar 10 di atas data yang lebih panjang akan gagal di
        // MySQL strict mode. Dipangkas dulu supaya baris pendaftarannya tetap
        // ada — yang hilang hanya ekor nama pasukan, bukan pasukannya.
        DB::table('registrations')
            ->whereNotNull('label_pasukan')
            ->select('id', 'label_pasukan')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $pendek = mb_substr((string) $row->label_pasukan, 0, 10);

                    if ($pendek !== $row->label_pasukan) {
                        DB::table('registrations')->where('id', $row->id)->update(['label_pasukan' => $pendek]);
                    }
                }
            });

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('label_pasukan', 10)->nullable()->change();
        });
    }
};
