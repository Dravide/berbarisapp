<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.landing')]
#[Title('Bantuan & Support - BARIS APP')]
class HelpSupport extends Component
{
    public $logoPath = null;

    public $favicon = null;

    public $contact = [];

    public $social = [];

    public $faqs = [];

    public function mount()
    {
        // Load logo
        $this->logoPath = Setting::get('logo_dark')
            ? Storage::disk('public')->url(Setting::get('logo_dark'))
            : null;

        $faviconSetting = Setting::get('favicon');
        $this->favicon = $faviconSetting
            ? Storage::disk('public')->url($faviconSetting)
            : null;

        $this->contact = Setting::get('landing_contact', []);
        $this->social = Setting::get('landing_social_links', []);

        // FAQ dari Setting landing_faq (dikelola admin di tab FAQ); fallback ke default.
        $faqSetting = json_decode(Setting::get('landing_faq', '{}'), true) ?? [];
        $this->faqs = $faqSetting['items'] ?? $this->defaultFaqs();
    }

    private function defaultFaqs(): array
    {
        return [
            ['question' => 'Bagaimana cara mendaftar di sebuah event?', 'answer' => 'Buka halaman event yang ingin Anda ikuti, klik tombol "Booking Pendaftaran". Pilih kategori lomba, isi data sekolah, lalu konfirmasi booking. Anda akan menerima link magic untuk mengelola data pasukan selanjutnya.'],
            ['question' => 'Bagaimana cara voting digital?', 'answer' => 'Masuk ke halaman voting event, pilih kategori lomba, pilih kontingen yang ingin didukung, tentukan jumlah vote, lalu lakukan pembayaran via QRIS.'],
            ['question' => 'Bagaimana cara membeli tiket event?', 'answer' => 'Klik tombol "Beli Tiket" di halaman event. Isi data pembeli, tentukan jumlah tiket, lalu bayar via QRIS. QR code tiket akan langsung tersedia setelah pembayaran berhasil.'],
            ['question' => 'Saya lupa password, bagaimana cara reset?', 'answer' => 'Gunakan link magic yang dikirim saat booking pendaftaran. Jika link sudah kadaluarsa, hubungi penyelenggara event atau tim support kami via WhatsApp untuk bantuan reset password.'],
            ['question' => 'Apakah bisa membatalkan pendaftaran?', 'answer' => 'Pendaftaran dapat dibatalkan oleh penyelenggara event atau melalui permintaan ke tim support. Status pendaftaran akan berubah menjadi "Dibatalkan". Pengembalian dana voting/tiket mengikuti kebijakan masing-masing event.'],
            ['question' => 'Bagaimana cara melihat hasil kompetisi?', 'answer' => 'Hasil kompetisi ditampilkan di halaman event setelah penyelenggara mempublikasikannya. Anda juga dapat melihat rekapitulasi penilaian dan peringkat peserta jika fitur tersebut diaktifkan oleh penyelenggara.'],
            ['question' => 'Apakah data saya aman?', 'answer' => 'Ya, kami menggunakan enkripsi SSL/TLS dan menyimpan password dalam bentuk hash. Data Anda dilindungi sesuai Kebijakan Privasi kami. Akses data dibatasi hanya untuk pihak yang berkepentingan.'],
        ];
    }

    public function render()
    {
        return view('livewire.public.help-support')
            ->title('Bantuan & Support - '.get_setting('site_title', 'BARIS APP'));
    }
}
