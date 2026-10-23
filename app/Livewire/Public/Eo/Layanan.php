<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /layanan: lima kelompok kerja, dari identitas visual sampai laporan.
 *
 * Halaman ini menjawab "kalau saya serahkan semuanya, Anda urus apa saja?".
 * Tiap kelompok bisa dipesan terpisah: banyak sekolah sudah punya panitia
 * sendiri dan hanya butuh sistem penilaiannya, atau hanya butuh desainnya.
 *
 * Sengaja tanpa harga. Biaya jasa penyelenggaraan dihitung per acara (jumlah
 * kategori, peserta, hari, jarak, kebutuhan cetak), jadi angka tunggal di
 * halaman ini selalu salah untuk separuh pembaca. Yang ditulis adalah apa
 * yang menentukan biayanya, lalu ajakan minta penawaran.
 */
#[Layout('layouts.eo')]
class Layanan extends Component
{
    use MenyiapkanHalamanEo;

    public function render()
    {
        return view('livewire.public.eo.layanan', array_merge($this->dataEo(), [
            'layanan' => EoProfile::layanan(),
        ]))
            ->layoutData($this->dataEo())
            ->title('Lingkup Kerja | ' . self::namaEo());
    }
}
