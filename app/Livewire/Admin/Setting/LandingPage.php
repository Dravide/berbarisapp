<?php

namespace App\Livewire\Admin\Setting;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class LandingPage extends Component
{
    use WithFileUploads;

    /**
     * Section yang benar-benar punya komponen Blade-nya.
     *
     * Urutan section tersimpan di settings dan disunting lewat panel
     * naik/turun, jadi daftarnya bisa memuat nama lama (mis. dari susunan
     * 12 blok sebelum penyederhanaan). Nama yang tidak ada di sini disaring
     * saat menyimpan — kalau tidak, landing akan memanggil view yang sudah
     * dihapus dan error 500.
     */
    private const SECTION_DIKENAL = ['hero', 'features', 'pricing', 'eventners', 'ticket', 'faq', 'cta'];

    // Active tab
    public $activeTab = 'hero';

    // Section order & visibility
    public $sectionsOrder = [];

    public $sectionsActive = [];

    // Hero fields
    public $hero_heading;

    public $hero_subheading;

    public $hero_cta_text;

    public $hero_cta_url;

    public $hero_video_url;

    public $hero_background_image;

    public $hero_bg_current;

    // Features fields
    public $features_title;

    public $features_items = [];

    // Pricing fields
    public $pricing_title;

    public $pricing_subtitle;

    // Visual & poin (kunci settingnya masih `landing_about` — nama warisan;
    // isinya sekarang tampil di dalam section hero dan features).
    public $about_image;

    public $about_image_current;

    public $about_video;

    public $about_points = [];

    // CTA fields
    public $cta_heading;

    public $cta_description;

    public $cta_button_text;

    public $cta_button_url;

    public $cta_image;

    public $cta_image_current;

    // Contact fields
    public $contact_phone;

    public $contact_email;

    public $contact_address;

    public $contact_map_embed_url;

    // FAQ fields
    public $faq_title;

    public $faq_items = [];

    // Ticket section (live data; admin sets heading only)
    public $ticket_title;

    public $ticket_subtitle;

    // Social links
    public $social_instagram;

    public $social_tiktok;

    public $social_youtube;

    public $social_facebook;

    public function mount()
    {
        // Load section order & active
        $this->sectionsOrder = json_decode(Setting::get('landing_sections_order', '["hero","features","pricing","eventners","ticket","faq","cta"]'), true);
        $this->sectionsActive = json_decode(Setting::get('landing_sections_active', '{"hero":true,"features":true,"pricing":true,"eventners":true,"ticket":true,"faq":true,"cta":true}'), true);

        // Load Hero
        $hero = json_decode(Setting::get('landing_hero', '{}'), true) ?? [];
        $this->hero_heading = $hero['heading'] ?? 'Kelola Event & Kompetisi dengan Mudah';
        $this->hero_subheading = $hero['subheading'] ?? 'Platform manajemen event terpadu yang membantu penyelenggara mengelola pendaftaran, penilaian, voting, dan tiket secara digital.';
        $this->hero_cta_text = $hero['cta_text'] ?? 'Mulai Sekarang';
        $this->hero_cta_url = $hero['cta_url'] ?? route('login');
        $this->hero_video_url = $hero['video_url'] ?? '';
        $this->hero_bg_current = $hero['background_image'] ?? '';

        // Load Features
        $features = json_decode(Setting::get('landing_features', '{}'), true) ?? [];
        $this->features_title = $features['title'] ?? 'Fitur Lengkap untuk Event Sukses';
        $this->features_items = $features['items'] ?? $this->defaultFeatures();

        // Load Pricing
        $pricing = json_decode(Setting::get('landing_pricing', '{}'), true) ?? [];
        $this->pricing_title = $pricing['title'] ?? 'Harga & Paket';
        $this->pricing_subtitle = $pricing['subtitle'] ?? 'Kelola perlombaan sekolah dengan gratis. Aktifkan fitur premium sekali bayar per event — tanpa langganan bulanan.';

        // Load About (kunci warisan — lihat catatan di deklarasi propertinya)
        $about = json_decode(Setting::get('landing_about', '{}'), true) ?? [];
        $this->about_image_current = $about['image'] ?? '';
        $this->about_video = $about['video'] ?? '';
        $this->about_points = $about['points'] ?? [];

        // Load CTA
        $cta = json_decode(Setting::get('landing_cta', '{}'), true) ?? [];
        $this->cta_heading = $cta['heading'] ?? 'Siap Mengelola Event Lebih Efisien?';
        $this->cta_description = $cta['description'] ?? '';
        $this->cta_button_text = $cta['button_text'] ?? 'Daftar Sekarang';
        $this->cta_button_url = $cta['button_url'] ?? route('login');
        $this->cta_image_current = $cta['image'] ?? '';

        // Load Contact
        $contact = json_decode(Setting::get('landing_contact', '{}'), true) ?? [];
        $this->contact_phone = $contact['phone'] ?? '';
        $this->contact_email = $contact['email'] ?? '';
        $this->contact_address = $contact['address'] ?? '';
        $this->contact_map_embed_url = $contact['map_embed_url'] ?? '';

        // Load FAQ
        $faq = json_decode(Setting::get('landing_faq', '{}'), true) ?? [];
        $this->faq_title = $faq['title'] ?? 'Pertanyaan yang Sering Diajukan';
        $this->faq_items = $faq['items'] ?? [];

        // Load Ticket section
        $ticket = json_decode(Setting::get('landing_ticket', '{}'), true) ?? [];
        $this->ticket_title = $ticket['title'] ?? 'E-Tiket Digital';
        $this->ticket_subtitle = $ticket['subtitle'] ?? 'Beli tiket event favoritmu secara online. Praktis, aman, dengan QR code check-in.';

        // Load Social Links
        $socials = json_decode(Setting::get('landing_social_links', '{}'), true) ?? [];
        $this->social_instagram = $socials['instagram'] ?? '';
        $this->social_tiktok = $socials['tiktok'] ?? '';
        $this->social_youtube = $socials['youtube'] ?? '';
        $this->social_facebook = $socials['facebook'] ?? '';
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function toggleSection($type)
    {
        $this->sectionsActive[$type] = ! ($this->sectionsActive[$type] ?? true);
    }

    public function moveSectionUp($index)
    {
        if ($index > 0) {
            $temp = $this->sectionsOrder[$index];
            $this->sectionsOrder[$index] = $this->sectionsOrder[$index - 1];
            $this->sectionsOrder[$index - 1] = $temp;
        }
    }

    public function moveSectionDown($index)
    {
        if ($index < count($this->sectionsOrder) - 1) {
            $temp = $this->sectionsOrder[$index];
            $this->sectionsOrder[$index] = $this->sectionsOrder[$index + 1];
            $this->sectionsOrder[$index + 1] = $temp;
        }
    }

    // -- Feature item management --
    public function addFeatureItem()
    {
        $this->features_items[] = ['icon' => 'icon3.png', 'title' => '', 'description' => ''];
    }

    public function removeFeatureItem($index)
    {
        unset($this->features_items[$index]);
        $this->features_items = array_values($this->features_items);
    }

    // -- About point management --
    public function addAboutPoint()
    {
        $this->about_points[] = ['title' => '', 'text' => ''];
    }

    public function removeAboutPoint($index)
    {
        unset($this->about_points[$index]);
        $this->about_points = array_values($this->about_points);
    }

    // -- FAQ item management --
    public function addFaqItem()
    {
        $this->faq_items[] = ['question' => '', 'answer' => ''];
    }

    public function removeFaqItem($index)
    {
        unset($this->faq_items[$index]);
        $this->faq_items = array_values($this->faq_items);
    }

    public function save()
    {
        // Save section order & active — disaring dulu supaya nama section
        // lama yang komponennya sudah dihapus tidak ikut tersimpan.
        $this->sectionsOrder = array_values(array_filter(
            $this->sectionsOrder,
            fn ($type) => in_array($type, self::SECTION_DIKENAL, true)
        ));
        $this->sectionsActive = array_intersect_key($this->sectionsActive, array_flip(self::SECTION_DIKENAL));

        Setting::set('landing_sections_order', json_encode($this->sectionsOrder));
        Setting::set('landing_sections_active', json_encode($this->sectionsActive));

        // Save Hero
        $heroBg = $this->hero_bg_current;
        if ($this->hero_background_image) {
            if ($heroBg) {
                Storage::disk('public')->delete($heroBg);
            }
            $heroBg = $this->hero_background_image->store('landing', 'public');
            // Simpan path baru sebagai "current" supaya simpan kedua kali
            // tidak menghapus gambar yang baru diunggah lalu menulis path kosong.
            $this->hero_bg_current = $heroBg;
        }
        Setting::set('landing_hero', json_encode([
            'heading' => $this->hero_heading,
            'subheading' => $this->hero_subheading,
            'cta_text' => $this->hero_cta_text,
            'cta_url' => $this->hero_cta_url,
            'video_url' => $this->hero_video_url,
            'background_image' => $heroBg,
        ]));

        // Save Features
        Setting::set('landing_features', json_encode([
            'title' => $this->features_title,
            'items' => $this->features_items,
        ]));

        // Save Pricing
        Setting::set('landing_pricing', json_encode([
            'title' => $this->pricing_title,
            'subtitle' => $this->pricing_subtitle,
        ]));

        // Save About
        $aboutImage = $this->about_image_current;
        if ($this->about_image) {
            if ($aboutImage) {
                Storage::disk('public')->delete($aboutImage);
            }
            $aboutImage = $this->about_image->store('landing', 'public');
            $this->about_image_current = $aboutImage;
        }
        Setting::set('landing_about', json_encode([
            'image' => $aboutImage,
            'video' => $this->about_video,
            'points' => $this->about_points,
        ]));

        // Save CTA
        $ctaImage = $this->cta_image_current;
        if ($this->cta_image) {
            if ($ctaImage) {
                Storage::disk('public')->delete($ctaImage);
            }
            $ctaImage = $this->cta_image->store('landing', 'public');
            $this->cta_image_current = $ctaImage;
        }
        Setting::set('landing_cta', json_encode([
            'heading' => $this->cta_heading,
            'description' => $this->cta_description,
            'button_text' => $this->cta_button_text,
            'button_url' => $this->cta_button_url,
            'image' => $ctaImage,
        ]));

        // Save Contact
        Setting::set('landing_contact', json_encode([
            'phone' => $this->contact_phone,
            'email' => $this->contact_email,
            'address' => $this->contact_address,
            'map_embed_url' => $this->contact_map_embed_url,
        ]));

        // Save FAQ
        Setting::set('landing_faq', json_encode([
            'title' => $this->faq_title,
            'items' => $this->faq_items,
        ]));

        // Save Social Links
        Setting::set('landing_social_links', json_encode([
            'instagram' => $this->social_instagram,
            'tiktok' => $this->social_tiktok,
            'youtube' => $this->social_youtube,
            'facebook' => $this->social_facebook,
        ]));

        // Save Ticket section
        Setting::set('landing_ticket', json_encode([
            'title' => $this->ticket_title,
            'subtitle' => $this->ticket_subtitle,
        ]));

        // Simpan section yang sudah dibuang: baris settingnya sengaja
        // dibiarkan di DB (tulisan admin tidak boleh hilang diam-diam), tapi
        // tidak lagi ditulis ulang dari sini.

        $this->reset(['hero_background_image', 'about_image', 'cta_image']);
        session()->flash('success', 'Landing page berhasil diperbarui.');
    }

    private function defaultFeatures(): array
    {
        return [
            ['icon' => 'icon3.png', 'title' => 'Manajemen Pendaftaran', 'description' => 'Kelola pendaftaran peserta secara digital dengan verifikasi otomatis dan tracking status real-time.'],
            ['icon' => 'icon4.png', 'title' => 'Penilaian Juri Digital', 'description' => 'Sistem penilaian digital dengan format kustom, perhitungan otomatis, dan rekap nilai instan.'],
            ['icon' => 'icon5.png', 'title' => 'Voting Online', 'description' => 'Fitur voting online terintegrasi dengan pembayaran digital untuk penghargaan favorit penonton.'],
            ['icon' => 'icon6.png', 'title' => 'E-Tiket & Pembayaran', 'description' => 'Jual tiket event secara online dengan integrasi gateway pembayaran dan QR code check-in.'],
            ['icon' => 'icon7.png', 'title' => 'Live Scoreboard', 'description' => 'Papan skor real-time yang bisa dipancarkan ke layar proyektor untuk transparansi penilaian.'],
            ['icon' => 'icon8.png', 'title' => 'Drawing & Undian', 'description' => 'Sistem undian digital untuk menentukan urutan tampil peserta dengan animasi menarik.'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.setting.landing-page')->title('Pengaturan Landing Page - ' . app_name());
    }
}
