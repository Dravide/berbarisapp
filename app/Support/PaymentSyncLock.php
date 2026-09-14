<?php

namespace App\Support;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Kunci bersama untuk semua sinkronizer AutoGoPay.
 *
 * Ada dua jalur yang merekonsiliasi transaksi AutoGoPay: command
 * `payment:sync-pending` (tiap menit, jalur tanpa queue worker) dan job
 * `SyncPendingPayments` (tiap lima menit, lebih berat). Keduanya menembak
 * endpoint /qris/status yang sama.
 *
 * `withoutOverlapping()` TIDAK melindungi dari itu — mutex-nya dibuat dari
 * nama event scheduler, jadi jadwal yang berbeda punya kunci yang berbeda
 * sama sekali. Keduanya bisa berjalan bersamaan dan memanggil gateway dua
 * kali untuk transaksi yang sama (rate limit gateway terpakai jatah dobel,
 * dan status bisa saling menimpa).
 *
 * Satu kunci di sini menutup keduanya.
 */
class PaymentSyncLock
{
    private const KEY = 'payment-sync';

    /** Batas waktu lock (detik) — cukup panjang untuk satu siklus penuh. */
    private const SECONDS = 300;

    /**
     * Jalankan $callback bila tidak ada sinkronizer lain yang sedang jalan.
     *
     * @return bool true bila callback dijalankan, false bila dilewati.
     */
    public static function run(callable $callback): bool
    {
        try {
            return (bool) Cache::lock(self::KEY, self::SECONDS)->block(1, function () use ($callback) {
                $callback();

                return true;
            });
        } catch (LockTimeoutException) {
            // Sinkronizer lain sedang jalan — siklus ini dilewati, bukan
            // digagalkan. Siklus berikutnya yang mengambil alih.
            return false;
        }
    }
}
