<?php

namespace App\Services;

use App\Exceptions\TicketQuotaExceededException;
use App\Models\EventnerVenue;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Penjaga kuota tiket per tempat.
 *
 * Satu-satunya tempat pengecekan kuota, dipakai jalur web (`Public\EventTicket`)
 * maupun jalur API Flutter (`Api\V1\TicketController`). Jangan duplikasi
 * pengecekannya di pemanggil — di situlah pembeli kedua bisa menyelinap.
 *
 * Kuota TIDAK diklaim lewat kolom counter; yang menahan slot adalah baris
 * `tickets` itu sendiri (lihat EventnerVenue::ticketsSoldCount()). Karena itu
 * pengecekan dan pembuatan tiket harus berada dalam SATU transaksi, dan baris
 * venue harus dikunci lebih dulu supaya pembeli bersamaan antre, bukan
 * sama-sama lolos.
 */
class TicketQuota
{
    /**
     * Jalankan $create di dalam transaksi berkunci, setelah kuota venue
     * dipastikan cukup untuk $quantity tiket.
     *
     * @param  callable(EventnerVenue): Ticket  $create  dipanggil di dalam transaksi yang sama
     *
     * @throws TicketQuotaExceededException
     */
    public static function reserve(EventnerVenue $venue, int $quantity, callable $create): Ticket
    {
        return DB::transaction(function () use ($venue, $quantity, $create) {
            // lockForUpdate: pembeli kedua memblokir di sini sampai transaksi
            // pertama selesai, sehingga perhitungan sisa kuota selalu segar.
            $locked = EventnerVenue::whereKey($venue->id)->lockForUpdate()->firstOrFail();

            $remaining = $locked->remainingTicketSlots();

            if ($remaining !== null && $remaining < $quantity) {
                throw new TicketQuotaExceededException($remaining, $locked->name);
            }

            return $create($locked);
        });
    }

    /**
     * Versi tanpa membuat tiket — untuk menampilkan pesan "tinggal N tiket"
     * sebelum pembeli menekan bayar, atau untuk penambahan tiket manual panitia.
     */
    public static function remaining(EventnerVenue $venue): ?int
    {
        return $venue->remainingTicketSlots();
    }
}
