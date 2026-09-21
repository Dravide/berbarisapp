<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenggat bayar yang disimpan di baris, plus status FAILED untuk tiket.
 *
 * `expires_at` = tenggat dari gateway (dipakai hitungan mundur di UI).
 * `payable_until` = expires_at + tenggang; inilah yang mengikat — setelah
 * lewat, baris PENDING dianggap mati oleh PendingPaymentGuard.
 *
 * Sebelum ini `expiry_time` hanya hidup di properti Livewire dan tidak pernah
 * ditulis ke DB, sehingga "PENDING ini sudah mati" hanya bisa diketahui dengan
 * menembak /qris/status ke gateway — tidak bisa dipakai sebagai penjaga.
 *
 * Index (eventner_id, email, status) melayani satu query yang jalan di SETIAP
 * klik "Bayar" pada halaman publik anonim; tanpa index itu pemindaian tabel
 * penuh pada tabel yang paling panas.
 *
 * Enum `tickets.status` juga ditambah 'FAILED': AutoGoPay::mapStatus('cancel')
 * mengembalikan 'FAILED' dan beberapa jalur menulisnya apa adanya. Di MySQL
 * strict itu error 1265 (varchar/enum tidak menerima nilainya), dan penjaga
 * baru bergantung pada transisi FAILED/EXPIRED yang andal. VoteTransaction
 * sudah punya 'FAILED' sejak awal — ini menyamakan keduanya.
 *
 * Terakhir, `vote_transactions.qr_url` dilonggarkan jadi nullable. Kolomnya
 * diwarisi dari era Xendit sebagai NOT NULL, padahal pembatalan QR memang
 * meninggalkan baris tanpa QR — dan migrasi 2026_05_12 hanya mengganti
 * namanya, tidak sifatnya. Tiket sudah nullable sejak awal.
 */
return new class extends Migration
{
    /**
     * Buka SQLite: ENUM Laravel di-emulasi sebagai varchar tanpa CHECK (sudah
     * dibuktikan dengan insert 'FAILED' ke tabel hasil migrate), jadi tidak ada
     * yang perlu ditulis ulang — cukup tambah kolom.
     */
    public function up(): void
    {
        Schema::table('vote_transactions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->timestamp('payable_until')->nullable()->after('expires_at');
            $table->index(['eventner_id', 'voter_email', 'status'], 'vote_tx_pending_lookup_index');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('paid_at');
            $table->timestamp('payable_until')->nullable()->after('expires_at');
            $table->index(['eventner_id', 'buyer_email', 'status'], 'tickets_pending_lookup_index');
        });

        Schema::table('vote_transactions', function (Blueprint $table) {
            $table->string('qr_url')->nullable()->change();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('PENDING', 'PAID', 'EXPIRED', 'FAILED', 'CHECKED_IN', 'ACTIVE') NOT NULL DEFAULT 'PENDING'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('PENDING', 'PAID', 'EXPIRED', 'CHECKED_IN', 'ACTIVE') NOT NULL DEFAULT 'PENDING'");
        }

        // qr_url sengaja TIDAK dikembalikan ke NOT NULL: baris yang dibatalkan
        // (FAILED, tanpa QR) sudah ada dan akan menolak migrasi balik. Aman
        // dibiarkan nullable — tipe yang lebih longgar tidak merusak apa pun.

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_pending_lookup_index');
            $table->dropColumn(['expires_at', 'payable_until']);
        });

        Schema::table('vote_transactions', function (Blueprint $table) {
            $table->dropIndex('vote_tx_pending_lookup_index');
            $table->dropColumn(['expires_at', 'payable_until']);
        });
    }
};
