<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor undian per babak.
     *
     * registrations.urutan_tampil menyimpan SATU nomor per registrasi, dan
     * nomor itu dipakai ulang di semua babak. Akibatnya peserta yang lolos
     * final tetap membawa nomor undian fase grupnya di babak final — padahal
     * final adalah babak baru, dan panitia tidak punya cara mengundi ulang
     * final tanpa merusak undian fase grup.
     *
     * Kolom ini menampung undian babaknya sendiri: baris di sini sudah
     * per (babak, peserta), jadi nomor final tidak menimpa nomor grup.
     * registrations.urutan_tampil TETAP jadi nomor penyisihan / tingkat tanpa
     * babak — babak penyisihan tidak punya baris di tabel ini sama sekali
     * (yang diisi hanya finalis), jadi membacanya dari sini akan menghapus
     * nomor undian fase grup.
     *
     * Nullable, nullOnDelete, bukan cascade — menghapus babak hanya melepas
     * undiannya, tidak boleh menghapus catatan kelolosan peserta.
     *
     * Sengaja TANPA unique index: MySQL menganggap NULL berbeda satu sama lain,
     * jadi unique tidak menjamin apa pun untuk baris yang belum diundi.
     * Keunikan ditegakkan di aplikasi, sama seperti Drawing\Index::assignManual().
     */
    public function up(): void
    {
        Schema::table('competition_round_registrations', function (Blueprint $table) {
            $table->unsignedInteger('urutan_tampil')->nullable()->after('preliminary_total');
        });
    }

    public function down(): void
    {
        Schema::table('competition_round_registrations', function (Blueprint $table) {
            $table->dropColumn('urutan_tampil');
        });
    }
};
