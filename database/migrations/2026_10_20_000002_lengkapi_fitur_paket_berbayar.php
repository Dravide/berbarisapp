<?php

use App\Models\SaasPlan;
use Illuminate\Database\Migrations\Migration;

/**
 * Tiga kunci config/eventner_features.php tidak diberikan paket mana pun.
 *
 * `layout`, `schedule`, dan `vote_comments` ditambahkan ke config setelah paket
 * "Event Penuh" sudah disemai, jadi barisnya tak pernah ikut tersalin. Efeknya
 * bukan "fitur gratis dibuka", tapi kebalikannya: canAccessFeature() untuk tiga
 * kunci itu mengembalikan false bagi SEMUA orang, termasuk pemilik paket
 * berbayar — dan halaman yang menuntutnya (Layout/Index, Layout/Show,
 * Schedule/Index, VoteComment/Index) melempar yang sudah membayar ke /upgrade.
 *
 * Cacat jenis ini tidak bisa ditangkap tes kode mana pun: kodenya benar, datanya
 * yang bolong. Karena itu migrasi ini menyembuhkan data yang ada, sementara
 * PaywallCompletenessTest menjaga agar tidak terulang.
 *
 * Idempoten lewat firstOrCreate per kunci — bukan delete-lalu-create-many(),
 * yang akan menghapus penyesuaian manual admin pada paket yang sedang hidup.
 */
return new class extends Migration
{
    /**
     * Tiga kunci yang hilang saat migrasi ini ditulis.
     *
     * Sengaja berupa daftar tetap, BUKAN "semua kunci config yang belum
     * dipegang paket". Versi luasnya terlihat lebih tahan lama, tapi ia menimpa
     * pilihan admin: fitur yang sengaja dicabut dari sebuah paket akan
     * dikembalikan diam-diam saat migrasi dijalankan. Penambahan kunci config
     * berikutnya memang menuntut migrasi kecilnya sendiri — itu harga yang
     * pantas untuk tidak menulis ulang keputusan admin.
     */
    private const KUNCI_HILANG = ['layout', 'schedule', 'vote_comments'];

    public function up(): void
    {
        // Paket gratis tak pernah menyimpan fitur premium (is_free menang di
        // fiturPaket()), jadi ia sengaja dilewati.
        foreach (SaasPlan::where('is_free', false)->get() as $plan) {
            foreach (self::KUNCI_HILANG as $kunci) {
                $plan->features()->firstOrCreate(['feature_key' => $kunci]);
            }
        }
    }

    public function down(): void
    {
        // Kembalikan ke keadaan sebelum migrasi: tak satu pun paket memegang
        // tiga kunci ini.
        SaasPlan::where('is_free', false)
            ->get()
            ->each(fn (SaasPlan $plan) => $plan->features()
                ->whereIn('feature_key', self::KUNCI_HILANG)
                ->delete());
    }
};
