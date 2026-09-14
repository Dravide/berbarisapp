<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Deadline Pendaftaran kosong = pendaftaran publik tertutup.
     *
     * Sebelumnya deadline kosong berarti terbuka tanpa batas, padahal
     * pendaftar biasanya diinput manual oleh panitia. Event yang belum
     * punya deadline ditutup di sini supaya status tersimpan cocok dengan
     * yang sekarang dihitung Eventner::computeRegistrationStatus().
     *
     * Hanya status yang diubah — data pendaftar yang sudah ada tidak disentuh.
     */
    public function up(): void
    {
        $kosong = fn ($q) => $q->whereNull('tanggal_pendaftaran')->orWhere('tanggal_pendaftaran', '');

        DB::table('eventners')
            ->where($kosong)
            ->where('registration_status', '!=', 'closed')
            ->update(['registration_status' => 'closed']);
    }

    public function down(): void
    {
        // Tidak dikembalikan: membuka pendaftaran otomatis untuk event yang
        // deadline-nya masih kosong justru membalikkan maksud perubahannya.
    }
};
