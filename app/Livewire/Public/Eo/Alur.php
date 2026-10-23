<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /alur: tujuh langkah, dari pengajuan sampai aftermovie.
 *
 * Nomornya memang urutan, jadi penomoran di sini layak dipakai (beda dari
 * nomor hiasan di halaman pemasaran yang tidak menandakan apa pun).
 *
 * Perkiraan waktu sengaja TIDAK ditulis sebagai angka di kelas ini: janji
 * "kami balas hari kerja berikutnya" dan "H-7 kunci produksi" harus disepakati
 * tim operasional lebih dulu. Sampai itu terjadi, halaman menampilkan tiga
 * penanda waktu tanpa angka, dan admin mengisinya lewat setelan `eo_alur_waktu`
 * (JSON: `{"pengajuan":"...","kunci_produksi":"...","pelaksanaan":"..."}`).
 */
#[Layout('layouts.eo')]
class Alur extends Component
{
    use MenyiapkanHalamanEo;

    public function render()
    {
        // Kosong = tiga penanda waktu belum diisi, dan halaman mengatakannya
        // apa adanya alih-alih menampilkan angka karangan.
        $waktu = json_decode(get_setting('eo_alur_waktu') ?: '{}', true) ?: [];

        return view('livewire.public.eo.alur', array_merge($this->dataEo(), [
            'alur' => EoProfile::alur(),
            'waktu' => $waktu,
        ]))
            ->layoutData($this->dataEo())
            ->title('Cara Kerja | ' . self::namaEo());
    }
}
