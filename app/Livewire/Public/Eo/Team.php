<?php

namespace App\Livewire\Public\Eo;

use App\Livewire\Public\Eo\Concerns\MenyiapkanHalamanEo;
use App\Models\Setting;
use App\Support\EoProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * /tim: enam belas peran dalam empat kelompok, dengan nama menyusul.
 *
 * Peran dan tugasnya datang dari `EoProfile::tim()`; namanya dari setelan
 * `eo_team` (JSON `{"Direktur Event": "Nama", ...}`). Dua sumber terpisah itu
 * disengaja: peran adalah bagian dari penawaran dan tidak boleh hilang karena
 * admin belum mengisi nama.
 *
 * Kartu TANPA nama tetap dirender, dengan penanda "menyusul". Ini berbeda dari
 * komponen tim di laman utama (`components/landing/team.blade.php`) yang
 * memfilter `!empty($item['name'])` sehingga section-nya hilang total kalau
 * nama kosong, di sini justru peran yang kosong yang tidak masuk akal, bukan
 * nama. Karena itu halaman ini punya view sendiri, bukan memakai komponen itu.
 */
#[Layout('layouts.eo')]
class Team extends Component
{
    use MenyiapkanHalamanEo;

    public function render()
    {
        $nama = json_decode(Setting::get('eo_team') ?: '{}', true) ?: [];

        $kelompok = array_map(function (array $grup) use ($nama) {
            $grup['peran'] = array_map(function (array $peran) use ($nama) {
                $terisi = trim((string) ($nama[$peran['peran']] ?? ''));
                $peran['nama'] = $terisi !== '' ? $terisi : null;

                return $peran;
            }, $grup['peran']);

            return $grup;
        }, EoProfile::tim());

        $semua = collect($kelompok)->flatMap(fn ($g) => $g['peran']);
        $jumlah = $semua->count();
        $terisi = $semua->filter(fn ($p) => $p['nama'] !== null)->count();

        return view('livewire.public.eo.tim', array_merge($this->dataEo(), [
            'kelompok' => $kelompok,
            'jumlahPeran' => $jumlah,
            'jumlahTerisi' => $terisi,
        ]))
            ->layoutData($this->dataEo())
            ->title('Tim | ' . self::namaEo());
    }
}
