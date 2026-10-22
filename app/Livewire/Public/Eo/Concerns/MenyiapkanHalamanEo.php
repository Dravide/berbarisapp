<?php

namespace App\Livewire\Public\Eo\Concerns;

use App\Models\Setting;
use App\Support\EoProfile;
use Illuminate\Support\Facades\Storage;

/**
 * Data yang dipakai SETIAP halaman subdomain EO: logo, favicon, dan nama EO.
 *
 * Ditaruh di satu tempat karena ketujuhnya harus sama — logo yang muncul di
 * beranda tapi hilang di /sponsor terbaca sebagai halaman lain. Sama seperti
 * FeaturesPage, tiap komponen memanggil `layoutEo()` saat merender.
 *
 * Nama EO diambil dari setelan `eo_nama`; kosong berarti nama platform. Nama
 * contoh tidak pernah dipakai sebagai cadangan: halaman yang menyebut nama
 * tim karangan akan terbaca sebagai contoh, bukan sebagai penawaran.
 */
trait MenyiapkanHalamanEo
{
    protected function dataEo(): array
    {
        return [
            'logoPath' => Setting::get('logo_dark')
                ? Storage::disk('public')->url(Setting::get('logo_dark'))
                : null,
            'favicon' => Setting::get('favicon')
                ? Storage::disk('public')->url(Setting::get('favicon'))
                : null,
            'eoNama' => self::namaEo(),
            'eoRingkas' => EoProfile::IDENTITAS['ringkas'],
        ];
    }

    public static function namaEo(): string
    {
        $nama = trim((string) Setting::get('eo_nama', ''));

        return $nama !== '' ? $nama : app_name();
    }
}
