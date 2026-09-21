<?php

namespace App\Rules;

use App\Services\WilayahService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nilai field wilayah: hasil pilihan dropdown, bukan ketikan bebas.
 *
 * Dua hal yang sengaja TIDAK dilakukan di sini:
 *
 *  - **Tidak memanggil API.** Nama resminya sudah dijamin dropdown-nya saat
 *    pemilihan; mengulang panggilan HTTP di tiap submit hanya menambah satu
 *    titik gagal di jalur pendaftaran.
 *  - **Tidak menolak nilai yang bukan kode.** Baris `registration_field_values`
 *    dari era teks bebas ("Bandar Lampung") tetap harus bisa disubmit — kalau
 *    ditolak, pendaftar lama tidak bisa menyimpan formulirnya sama sekali.
 *    Pengetatannya hanya berlaku untuk nilai yang MEMANG berbentuk hasil
 *    gabung(), karena hanya nilai seperti itu yang dibuat oleh dropdown.
 *
 * Label ikut dikirim pemanggil supaya pesannya menyebut nama field panitia
 * ("Asal Kabupaten / Kota"), bukan nama properti Livewire
 * ("fieldValues.asal_kabupaten").
 */
class Wilayah implements ValidationRule
{
    public function __construct(
        private readonly int $kedalamanMaksimal = WilayahService::KECAMATAN,
        private readonly bool $wajib = false,
        private readonly string $label = 'wilayah',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nilai = trim((string) $value);

        if ($nilai === '') {
            return;
        }

        $kode = WilayahService::kodeDari($nilai);

        // Bukan format kode (teks bebas lama) — di luar wewenang rule ini.
        if ($kode === []) {
            if (str_contains($nilai, ' / ')) {
                $fail('Pilihan ' . $this->label . ' tidak dikenali. Silakan pilih ulang dari daftar.');
            }

            return;
        }

        if (count($kode) > $this->kedalamanMaksimal) {
            $fail('Pilihan ' . $this->label . ' melebihi tingkat wilayah yang diminta event ini.');

            return;
        }

        // Nilai ber-kode yang belum lengkap hanya masalah kalau fieldnya wajib:
        // peserta boleh berhenti di provinsi untuk field opsional (dan memang
        // itu yang diharapkan pada event tingkat provinsi).
        if ($this->wajib && count($kode) < $this->kedalamanMaksimal) {
            $fail('Lengkapi pilihan ' . $this->label . ' sampai tingkat yang diminta.');

            return;
        }

        // Kode anak harus berawalan kode induknya dan tingkatnya menaik:
        // "32.01" sah di bawah "32", tapi "32.01" di bawah "33" tidak.
        foreach ($kode as $i => $satu) {
            if ($i === 0) {
                continue;
            }

            $induk = $kode[$i - 1];

            if (! str_starts_with($satu, $induk . '.') || $satu === $induk) {
                $fail('Pilihan ' . $this->label . ' tidak berurutan. Silakan pilih ulang dari daftar.');

                return;
            }
        }
    }
}
