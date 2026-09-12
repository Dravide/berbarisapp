<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kuota tempat terlampaui. Dilempar dari dalam transaksi reservasi supaya
 * ROLLBACK terjadi dan tidak ada tiket setengah jadi.
 */
class TicketQuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly ?int $remaining = null,
        public readonly ?string $venueName = null,
    ) {
        $pesan = $venueName
            ? "Kuota tempat {$venueName} tidak cukup."
            : 'Kuota tempat tidak cukup.';

        if ($remaining !== null) {
            $pesan = $venueName
                ? "Sisa kuota {$venueName} tinggal {$remaining} tiket."
                : "Sisa kuota tempat tinggal {$remaining} tiket.";
        }

        parent::__construct($pesan);
    }
}
