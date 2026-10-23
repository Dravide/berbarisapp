<?php

namespace App\Livewire\Public\Eo\Concerns;

use App\Models\Eventner;
use Illuminate\Support\Collection;

/**
 * Query event untuk halaman EO: beranda (tiga terdekat) dan /event (portofolio).
 *
 * Sumbernya `eventners` berstatus approved, sama seperti section event di laman
 * utama dan `/eventner`. Yang membedakan: di sini event dipisah menjadi
 * "akan datang" dan "sudah terlaksana", karena halaman /event memakainya
 * sebagai dua hal yang berbeda: yang pertama ditawarkan ke peserta, yang
 * kedua ditawarkan sebagai bukti rekam jejak.
 *
 * Batas "terlaksana" mengikuti `eventner-card.blade.php`: `tanggal_akhir`
 * kalau ada, kalau tidak `tanggal`, dan dihitung sampai akhir hari. Kalau
 * aturannya beda, kartu yang sama akan berlabel "Terlaksana" di satu halaman
 * dan "Dibuka" di halaman lain.
 */
trait MengambilEventEo
{
    /**
     * Event yang belum selesai, paling dekat lebih dulu.
     *
     * @return Collection<int, Eventner>
     */
    protected function eventAkanDatang(int $limit = 3): Collection
    {
        return Eventner::approved()
            ->withCount('registrations')
            ->whereRaw(
                'COALESCE(tanggal_akhir, tanggal) >= ?',
                [now()->toDateString()]
            )
            ->orderByRaw('COALESCE(tanggal_akhir, tanggal) ASC')
            ->limit($limit)
            ->get();
    }

    /**
     * Event yang sudah lewat, terbaru lebih dulu, bahan portofolio.
     *
     * @return Collection<int, Eventner>
     */
    protected function eventTerlaksana(int $limit = 8): Collection
    {
        return Eventner::approved()
            ->withCount('registrations')
            ->whereRaw(
                'COALESCE(tanggal_akhir, tanggal) < ?',
                [now()->toDateString()]
            )
            ->orderByRaw('COALESCE(tanggal_akhir, tanggal) DESC')
            ->limit($limit)
            ->get();
    }
}
