<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /sponsor: tiga tingkat kerja sama, dan apa yang didapat pihak yang membiayai.
 *
 * Halaman untuk pembaca yang tidak punya anak lomba dan tidak menyewa jasa:
 * dinas, bank daerah, toko seragam, merek minuman. Yang mereka cari cuma satu:
 * daftar yang mereka dapat.
 *
 * Angka paket sengaja tidak ada. Nilai kerja sama ditentukan per acara bersama
 * sponsor; angka karangan di halaman ini harus ditarik lagi setelah rapat
 * pertama, dan menarik harga yang sudah terlihat selalu merusak kepercayaan.
 */
#[Layout('layouts.eo')]
class Sponsor extends Component
{
    use MenyiapkanHalamanEo;

    public function render()
    {
        return view('livewire.public.eo.sponsor', array_merge($this->dataEo(), [
            'tingkat' => EoProfile::sponsor(),
            'mitra' => EoProfile::mitra(),
        ]))
            ->layoutData($this->dataEo())
            ->title('Sponsor & Mitra | ' . self::namaEo());
    }
}
