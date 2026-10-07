<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor HP akun (users.no_hp) — kontak admin platform ke pemilik event.
     *
     * Diletakkan di `users`, bukan di `eventners`: form pendaftaran meminta
     * nomor akun, dan nomor ini dipakai untuk menghubungi ORANG-nya (verifikasi,
     * urusan pembayaran), bukan untuk ditampilkan di laman publik event. Nomor
     * yang memang untuk peserta sudah punya tempat sendiri: `link_whatsapp`
     * dan `venue` di eventners.
     *
     * Nullable: akun lama tidak punya nomor, dan pendaftaran berikutnya juga
     * tidak boleh gagal hanya karena kolom ini kosong.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_hp', 20)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('no_hp');
        });
    }
};
