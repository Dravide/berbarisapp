<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Public\Eo\Concerns\MengambilEventEo;
use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /event: dua niat dalam satu halaman, yang ingin ikut lomba, dan yang ingin
 * menyewa penyelenggara. Keduanya sama-sama mencari bukti bahwa ini bukan
 * pertama kali.
 *
 * Portofolio dibaca dari `eventners` yang approved, bukan ditulis tangan:
 * setiap event di sana punya halaman publik sendiri (hasil, papan skor,
 * galeri), jadi kartunya bisa dibuka dan diperiksa. Ditulis manual, halaman ini
 * akan basi dalam sebulan.
 */
#[Layout('layouts.eo')]
class Event extends Component
{
    use MengambilEventEo;
    use MenyiapkanHalamanEo;

    /** `akan-datang` | `terlaksana` */
    public string $saring = 'semua';

    protected $queryString = [
        'saring' => ['except' => 'semua'],
    ];

    public function setSaring(string $saring): void
    {
        $this->saring = in_array($saring, ['semua', 'akan-datang', 'terlaksana'], true)
            ? $saring
            : 'semua';
    }

    public function render()
    {
        $akanDatang = $this->saring === 'terlaksana' ? collect() : $this->eventAkanDatang(6);
        $terlaksana = $this->saring === 'akan-datang' ? collect() : $this->eventTerlaksana(8);

        return view('livewire.public.eo.event', array_merge($this->dataEo(), [
            'akanDatang' => $akanDatang,
            'terlaksana' => $terlaksana,
            'jenisLomba' => EoProfile::jenisLomba(),
        ]))
            ->layoutData($this->dataEo())
            ->title('Event & Portofolio | ' . self::namaEo());
    }
}
