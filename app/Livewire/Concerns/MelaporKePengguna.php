<?php

namespace App\Livewire\Concerns;

/**
 * Melaporkan hasil aksi ke pengguna lewat toast.
 *
 * Kenapa BUKAN `session()->flash()`: pesan flash hanya tampil di berkas blade
 * yang kebetulan membacanya. Di proyek ini jumlah pembacanya jauh lebih
 * sedikit daripada penulisnya, dan hasilnya berbeda-beda per layar:
 *
 *   - Kategori Lomba  : 17 penulis, NOL pembaca. Pesan blokirnya benar-benar
 *                       tak pernah terlihat — tombol diklik, tak ada yang
 *                       berubah, dan operator menyimpulkan fiturnya rusak.
 *   - Format Penilaian: 16 penulis, SATU pembaca (kartu "Pengurangan Nilai
 *                       Tingkat", di dasar halaman). Pesannya muncul, tapi
 *                       jauh dari tombol yang diklik — di luar layar.
 *
 * Toast menghilangkan seluruh kelas masalah itu: satu jalur render, dan
 * posisinya tetap di sudut layar, bukan tergantung di mana pesannya ditulis.
 *
 * Bug ini sudah pernah ditemukan dan diperbaiki untuk satu alur saja
 * (lihat FormatNilaiCopyToTest::test_gagal_melapor_lewat_copy_done), jadi
 * trait ini menyatukan jalurnya supaya alur berikutnya tidak mengulanginya.
 *
 * `dispatch()` dipakai, bukan properti komponen: event dikirim setelah morph
 * selesai, jadi pesannya sampai sebelum pengguna sempat mengklik lagi.
 *
 * `$url` dipakai untuk pesan yang menuntut operator pindah layar — tanpa
 * tautan, pesan blokir hanya memberi tahu apa yang salah tanpa jalan keluar.
 */
trait MelaporKePengguna
{
    public function toast(string $message, string $type = 'success', ?string $url = null, ?string $label = null): void
    {
        $this->dispatch('toast', message: $message, type: $type, url: $url, label: $label);
    }

    /**
     * Pesan blokir, dengan tautan opsional ke layar yang harus dibuka dulu.
     */
    public function gagal(string $message, ?string $url = null, ?string $label = null): void
    {
        $this->toast($message, 'error', $url, $label);
    }
}
