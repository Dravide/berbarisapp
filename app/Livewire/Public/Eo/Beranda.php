<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Public\Eo\Concerns\MengambilEventEo;
use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Beranda subdomain EO.
 *
 * Satu kalimat janji, jenis lomba yang ditangani, empat kelompok kerja, dan
 * tiga event terdekat. Empat kelompok — bukan lima — karena kelompok kelima
 * (Administrasi & Laporan) paling jarang ditanyakan di rapat pertama; ia
 * muncul utuh di /layanan.
 *
 * Event terdekat dibaca dari basis data, bukan ditulis tangan. Kalau belum ada
 * event yang akan datang, blok itu hilang seluruhnya — bukan diisi contoh.
 */
#[Layout('layouts.eo')]
class Beranda extends Component
{
    use MengambilEventEo;
    use MenyiapkanHalamanEo;

    public function render()
    {
        $eventner = $this->eventAkanDatang(3);

        return view('livewire.public.eo.beranda', array_merge($this->dataEo(), [
            'jenisLomba' => EoProfile::jenisLomba(),
            'layananSorot' => array_slice(EoProfile::layanan(), 0, 4),
            'totalLayanan' => count(EoProfile::layanan()),
            'eventner' => $eventner,
        ]))
            ->layoutData($this->dataEo())
            ->title('Event Organizer Lomba Baris-Berbaris — ' . self::namaEo());
    }
}
