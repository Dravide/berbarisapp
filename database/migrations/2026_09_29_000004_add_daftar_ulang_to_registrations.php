<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan kehadiran di meja daftar ulang.
     *
     * Sebelum ini tidak ada satu pun penanda "pasukan ini sudah datang" — PDF
     * daftar ulang hanya bisa dicetak dan dicentang dengan pena. Kolom ini yang
     * membuat layar Daftar Ulang bisa menampilkan siapa yang masih mengantre,
     * dan sekaligus mengunci seri: seri ditetapkan panitia saat menandai hadir,
     * jadi tanpa penanda ini "sudah daftar ulang" tak bisa dibedakan dari
     * "serinya belum diatur".
     *
     * Bukan kolom boolean: jam kedatangan dipakai panitia untuk menelusuri
     * siapa yang datang terlambat, dan itu informasi yang hilang kalau hanya
     * disimpan sebagai true/false.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->timestamp('daftar_ulang_at')->nullable()->after('urutan_tampil');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('daftar_ulang_at');
        });
    }
};
