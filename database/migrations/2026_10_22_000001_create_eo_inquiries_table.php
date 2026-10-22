<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan event dari formulir /ajukan di subdomain EO.
     *
     * Kenapa tabel, bukan sekadar tautan WhatsApp: halaman /ajukan menjanjikan
     * "kami balas hari kerja berikutnya". Janji itu hanya bisa ditepati kalau
     * pengajuannya punya tempat yang diperiksa tiap hari — pesan WhatsApp yang
     * masuk ke satu ponsel hilang begitu percakapannya tergeser.
     *
     * `status` dipakai admin untuk menandai pengajuan yang sudah dibalas.
     * Tanpa itu, daftar pengajuan hanya jadi tumpukan yang tidak pernah
     * kosong dan akhirnya tidak dibuka lagi.
     *
     * Data yang disimpan sengaja hanya yang diketik pengaju: instansi,
     * penanggung jawab, kontak, jenis lomba, perkiraan tanggal/lokasi/peserta,
     * dan centang pekerjaan. Tidak ada kolom harga atau taksiran biaya —
     * angka itu keluar dari RAB yang disusun manual setelah konsultasi.
     */
    public function up(): void
    {
        Schema::create('eo_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('instansi');
            $table->string('penanggung_jawab');
            $table->string('whatsapp', 32);
            $table->string('email')->nullable();
            $table->string('jenis_lomba');
            $table->date('tanggal')->nullable();
            $table->string('lokasi')->nullable();
            $table->unsignedInteger('jumlah_peserta')->nullable();
            // JSON, bukan tabel pivot: isinya daftar centang bebas dari
            // EoProfile::pekerjaanPilihan(), tidak ada yang perlu di-query
            // per butir.
            $table->json('pekerjaan')->nullable();
            $table->text('catatan')->nullable();
            $table->string('status', 20)->default('baru');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('dibalas_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eo_inquiries');
    }
};
