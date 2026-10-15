<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gelombang harga biaya pendaftaran satu tingkat lomba.
     *
     * `competition_categories.registration_fee` tetap jadi harga biasa dan
     * dipakai bila tanggal pendaftaran jatuh di luar semua gelombang — sama
     * seperti `eventners.ticket_price` yang tetap dibaca tempat tanpa harga
     * sendiri. Tabel ini hanya MENAMBAH rentang, tidak menggantikan kolom itu,
     * jadi tingkat yang belum punya gelombang berperilaku persis seperti
     * sebelumnya.
     *
     * Sengaja tabel terpisah, bukan kolom di competition_categories: jumlah
     * gelombang tidak tetap, dan setiap gelombang punya rentang sendiri.
     *
     * `decimal(12,2)` mengikuti registration_fee — bukan `unsignedInteger`
     * seperti harga tiket venue, supaya tidak ada dua kolom biaya pendaftaran
     * dengan presisi berbeda.
     *
     * `date`, bukan `datetime`: rentangnya inklusif per hari, jadi panitia
     * menulis "1 Okt sampai 31 Okt" tanpa memikirkan jam.
     *
     * Tanpa kolom urutan: jadwal harga hanya masuk akal dibaca kronologis,
     * jadi `starts_on` yang mengurutkan — bukan angka terpisah yang bisa
     * menyimpang dari tanggalnya sendiri.
     */
    public function up(): void
    {
        Schema::create('competition_fee_waves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eventner_id')->constrained('eventners')->cascadeOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            // Pencarian satu-satunya: "gelombang mana yang mencakup hari ini
            // untuk tingkat ini". Urutannya juga starts_on, jadi indeks ini
            // melayani keduanya.
            $table->index(['competition_category_id', 'starts_on'], 'competition_fee_waves_lookup');

            // Nama gelombang unik per tingkat (dijaga juga di saveWave()).
            // Mengikuti competition_groups_unique: tanpa indeksnya, dua baris
            // bernama sama bisa masuk lewat seeder/impor dan pesan galat
            // bentrok jadi menunjuk gelombang yang salah.
            $table->unique(['competition_category_id', 'name'], 'competition_fee_waves_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_fee_waves');
    }
};
