<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use App\Support\ProductFeatures;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Halaman /fitur — katalog lengkap fitur produk.
 *
 * Landing cuma menjual sembilan ringkasan. Katalog penuhnya hidup di sini,
 * disusun dari App\Support\ProductFeatures — kode, bukan Setting, jadi admin
 * tidak bisa mengubah halaman ini dari panel. Jumlah kelompoknya sengaja
 * tidak ditulis di sini: satu-satunya hitungan yang boleh beredar adalah
 * ProductFeatures::total(), dan angka yang disalin ke komentar akan basi
 * tanpa ada yang gagal.
 *
 * Sengaja TIDAK mengirim `sectionsActive`/`sectionsRender`/`diLamanDepan` ke
 * layout: halaman ini tidak punya satu pun section #hero…#contact, dan
 * layouts/landing.blade.php memang memperlakukan ketiadaan itu sebagai
 * "bukan laman depan" lalu memberi prefiks url('/') pada tautan nav supaya
 * tidak jadi anchor mati.
 */
class FeaturesPage extends Component
{
    public $logoPath = null;

    public $favicon = null;

    public function mount()
    {
        $this->logoPath = Setting::get('logo_dark')
            ? Storage::disk('public')->url(Setting::get('logo_dark'))
            : null;

        $faviconSetting = Setting::get('favicon');
        $this->favicon = $faviconSetting
            ? Storage::disk('public')->url($faviconSetting)
            : null;
    }

    public function render()
    {
        return view('livewire.public.features-page', [
            'groups' => ProductFeatures::groups(),
            'total' => ProductFeatures::total(),
        ])
            ->layout('layouts.landing', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
            ])
            ->title('Semua Fitur - ' . app_name());
    }
}
