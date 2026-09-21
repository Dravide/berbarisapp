<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPSN jadi opsional.
 *
 * Panitia yang memindahkan pendaftaran lama (kertas/WhatsApp) sering tidak
 * memegang NPSN-nya, dan sebelum ini kolomnya NOT NULL tanpa default sehingga
 * baris seperti itu tidak bisa masuk sama sekali lewat jalur import.
 *
 * Yang ikut berubah karena kolomnya nullable:
 *
 *  - Pembanding duplikat: `npsn` kosong membuat pembandingnya jatuh ke
 *    nama_sekolah, supaya dua sekolah berbeda yang sama-sama tanpa NPSN tidak
 *    saling dianggap duplikat.
 *  - `ParticipantController::renderInvoice()` menggabung pasukan dengan
 *    `where('npsn', ...)`. Di MySQL, `= NULL` tidak pernah cocok, jadi tanpa
 *    perbaikan terpisah di sana semua pendaftar tanpa NPSN akan tercetak dalam
 *    satu invoice.
 *
 * `nama_sekolah` dan `no_hp` sengaja TIDAK ikut di-nullable: keduanya selalu
 * diisi dan tidak pernah dikosongkan panitia, jadi melonggarkannya hanya
 * membuka jalan data kosong tanpa kebutuhan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('npsn', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Baris yang terlanjur kosong diisi '' dulu — mengembalikan NOT NULL di
        // atas data NULL akan gagal di MySQL, dan lebih buruk lagi menghapus
        // barisnya. String kosong menjaga pendaftarnya tetap ada.
        DB::table('registrations')->whereNull('npsn')->update(['npsn' => '']);

        Schema::table('registrations', function (Blueprint $table) {
            $table->string('npsn', 255)->nullable(false)->change();
        });
    }
};
