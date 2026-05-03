<?php

namespace App\Livewire\Admin\Setting;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
#[Title('Pengaturan Landing Page - BARIS APP')]
class LandingPage extends Component
{
    use WithFileUploads;

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

    // About fields
    public $about_heading;
    public $about_description;
    public $about_image;
    public $about_image_current;
    public $about_points = [];

    // CTA fields
    public $cta_heading;
    public $cta_description;
    public $cta_button_text;
    public $cta_button_url;
    public $cta_image;
    public $cta_image_current;

    // Testimonials fields
    public $testimonials_title;
    public $testimonials_items = [];

    // Statistics fields
    public $statistics_items = [];

    // FAQ fields
    public $faq_title;
    public $faq_items = [];

    // Gallery fields
    public $gallery_title;
    public $gallery_items = [];

    // Social links
    public $social_instagram;
    public $social_tiktok;
    public $social_youtube;
    public $social_facebook;

    public function mount()
    {
        // Load section order & active
        $this->sectionsOrder = json_decode(Setting::get('landing_sections_order', '["hero","features","about","eventners","cta"]'), true);
        $this->sectionsActive = json_decode(Setting::get('landing_sections_active', '{"hero":true,"features":true,"about":true,"eventners":true,"cta":true}'), true);

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

        // Load About
        $about = json_decode(Setting::get('landing_about', '{}'), true) ?? [];
        $this->about_heading = $about['heading'] ?? 'Platform Event & Kompetisi Terpadu';
        $this->about_description = $about['description'] ?? '';
        $this->about_image_current = $about['image'] ?? '';
        $this->about_points = $about['points'] ?? [];

        // Load CTA
        $cta = json_decode(Setting::get('landing_cta', '{}'), true) ?? [];
        $this->cta_heading = $cta['heading'] ?? 'Siap Mengelola Event Lebih Efisien?';
        $this->cta_description = $cta['description'] ?? '';
        $this->cta_button_text = $cta['button_text'] ?? 'Daftar Sekarang';
        $this->cta_button_url = $cta['button_url'] ?? route('login');
        $this->cta_image_current = $cta['image'] ?? '';

        // Load Testimonials
        $testimonials = json_decode(Setting::get('landing_testimonials', '{}'), true) ?? [];
        $this->testimonials_title = $testimonials['title'] ?? 'Apa Kata Mereka?';
        $this->testimonials_items = $testimonials['items'] ?? [];

        // Load Statistics
        $statistics = json_decode(Setting::get('landing_statistics', '{}'), true) ?? [];
        $this->statistics_items = $statistics['items'] ?? [];

        // Load FAQ
        $faq = json_decode(Setting::get('landing_faq', '{}'), true) ?? [];
        $this->faq_title = $faq['title'] ?? 'Pertanyaan yang Sering Diajukan';
        $this->faq_items = $faq['items'] ?? [];

        // Load Gallery
        $gallery = json_decode(Setting::get('landing_gallery', '{}'), true) ?? [];
        $this->gallery_title = $gallery['title'] ?? 'Galeri';
        $this->gallery_items = $gallery['items'] ?? [];

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
        $this->sectionsActive[$type] = !($this->sectionsActive[$type] ?? true);
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

    // -- Testimonial item management --
    public function addTestimonialItem()
    {
        $this->testimonials_items[] = ['name' => '', 'role' => '', 'text' => '', 'rating' => 5, 'avatar' => ''];
    }

    public function removeTestimonialItem($index)
    {
        unset($this->testimonials_items[$index]);
        $this->testimonials_items = array_values($this->testimonials_items);
    }

    // -- Statistics item management --
    public function addStatisticItem()
    {
        $this->statistics_items[] = ['value' => '', 'label' => '', 'suffix' => '+'];
    }

    public function removeStatisticItem($index)
    {
        unset($this->statistics_items[$index]);
        $this->statistics_items = array_values($this->statistics_items);
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

    // -- Gallery item management --
    public function addGalleryItem()
    {
        $this->gallery_items[] = ['image' => '', 'caption' => ''];
    }

    public function removeGalleryItem($index)
    {
        // Delete image if exists
        if (!empty($this->gallery_items[$index]['image'])) {
            Storage::disk('public')->delete($this->gallery_items[$index]['image']);
        }
        unset($this->gallery_items[$index]);
        $this->gallery_items = array_values($this->gallery_items);
    }

    public function save()
    {
        // Save section order & active
        Setting::set('landing_sections_order', json_encode($this->sectionsOrder));
        Setting::set('landing_sections_active', json_encode($this->sectionsActive));

        // Save Hero
        $heroBg = $this->hero_bg_current;
        if ($this->hero_background_image) {
            if ($heroBg) Storage::disk('public')->delete($heroBg);
            $heroBg = $this->hero_background_image->store('landing', 'public');
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

        // Save About
        $aboutImage = $this->about_image_current;
        if ($this->about_image) {
            if ($aboutImage) Storage::disk('public')->delete($aboutImage);
            $aboutImage = $this->about_image->store('landing', 'public');
        }
        Setting::set('landing_about', json_encode([
            'heading' => $this->about_heading,
            'description' => $this->about_description,
            'image' => $aboutImage,
            'points' => $this->about_points,
        ]));

        // Save CTA
        $ctaImage = $this->cta_image_current;
        if ($this->cta_image) {
            if ($ctaImage) Storage::disk('public')->delete($ctaImage);
            $ctaImage = $this->cta_image->store('landing', 'public');
        }
        Setting::set('landing_cta', json_encode([
            'heading' => $this->cta_heading,
            'description' => $this->cta_description,
            'button_text' => $this->cta_button_text,
            'button_url' => $this->cta_button_url,
            'image' => $ctaImage,
        ]));

        // Save Testimonials
        Setting::set('landing_testimonials', json_encode([
            'title' => $this->testimonials_title,
            'items' => $this->testimonials_items,
        ]));

        // Save Statistics
        Setting::set('landing_statistics', json_encode([
            'items' => $this->statistics_items,
        ]));

        // Save FAQ
        Setting::set('landing_faq', json_encode([
            'title' => $this->faq_title,
            'items' => $this->faq_items,
        ]));

        // Save Gallery (process uploaded images)
        $galleryItems = $this->gallery_items;
        foreach ($galleryItems as $i => &$item) {
            if (isset($item['image_upload']) && $item['image_upload']) {
                if (!empty($item['image'])) {
                    Storage::disk('public')->delete($item['image']);
                }
                $item['image'] = $item['image_upload']->store('landing/gallery', 'public');
            }
            unset($item['image_upload']);
        }
        unset($item);
        Setting::set('landing_gallery', json_encode([
            'title' => $this->gallery_title,
            'items' => $galleryItems,
        ]));

        // Save Social Links
        Setting::set('landing_social_links', json_encode([
            'instagram' => $this->social_instagram,
            'tiktok' => $this->social_tiktok,
            'youtube' => $this->social_youtube,
            'facebook' => $this->social_facebook,
        ]));

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
        return view('livewire.admin.setting.landing-page');
    }
}
