<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batas jumlah vote per transaksi.
 *
 * Halaman vote publik dulu hanya memvalidasi `min:1` — tanpa batas atas. Satu
 * permintaan bisa meminta jumlah vote berapa pun, dan `voteCount * harga` jadi
 * nominal QRIS yang tidak pernah dipikirkan penyelenggara. Tombol cepat di
 * formulir bahkan menyediakan 1000, jadi batasnya memang harus ada dan harus
 * bisa diatur per event, bukan angka sulap di kode.
 *
 * Bentuknya sengaja meniru `ticket_max_per_order` (lihat migrasi
 * 2026_05_01_143352): kolom yang sama-sama dibaca formulir publik, divalidasi
 * halaman pengaturan, dan dikirim ke klien API. Default 1000 mengikuti pilihan
 * terbesar yang sudah ditawarkan formulir vote, jadi perilaku hari ini tidak
 * berubah untuk event yang tidak pernah menyentuh pengaturannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->integer('vote_max_per_order')->default(1000)->after('vote_price');
        });
    }

    public function down(): void
    {
        Schema::table('eventners', function (Blueprint $table) {
            $table->dropColumn('vote_max_per_order');
        });
    }
};
