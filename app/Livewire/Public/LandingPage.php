<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Setting;
use App\Models\Eventner;
use Illuminate\Support\Facades\Storage;

class LandingPage extends Component
{
    public $sections = [];
    public $sectionsOrder = [];
    public $sectionsActive = [];
    public $logoPath = null;
    public $favicon = null;

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

        // Load sections order & active state
        //
        // Tujuh section, bukan dua belas: yang dibuang adalah blok yang isinya
        // mengulang blok lain (statistik muncul dua kali; daftar fitur muncul
        // lagi di footer) atau yang tidak pernah render sama sekali karena
        // datanya kosong (vote, galeri) — nav-nya tetap aktif, jadi kliknya
        // tidak terjadi apa-apa.
        $this->sectionsOrder = json_decode(Setting::get('landing_sections_order', '["hero","features","pricing","eventners","ticket","faq","cta"]'), true);
        $this->sectionsActive = json_decode(Setting::get('landing_sections_active', '{"hero":true,"features":true,"pricing":true,"eventners":true,"ticket":true,"faq":true,"cta":true}'), true);

        // Load each section's content
        foreach ($this->sectionsOrder as $type) {
            $active = $this->sectionsActive[$type] ?? true;
            if (!$active) {
                continue;
            }

            $content = Setting::get("landing_{$type}");
            $this->sections[] = [
                'type' => $type,
                'content' => $content,
            ];
        }
    }

    public function render()
    {
        // Urut tanggal pelaksanaan, bukan tanggal dibuat — penyelenggara yang
        // eventnya paling dekat tampil di depan, bukan yang paling baru daftar.
        //
        // Hanya event yang sudah disetujui: yang masih `pending` (belum
        // dibayar/diverifikasi admin) belum punya halaman publik sama sekali,
        // jadi kartunya akan menautkan ke 404. Sama seperti scopeApproved()
        // yang dipakai semua halaman publik lain.
        // Enam kartu, bukan dua belas: section ini satu-satunya tempat hasil
        // event lampau muncul di laman publik, jadi event yang sudah lewat
        // tetap ditampilkan (bertanda "Terlaksana") — hanya jumlahnya yang
        // dikurangi supaya halamannya tidak jadi katalog.
        $eventners = Eventner::approved()
            ->withCount('registrations')
            ->orderBy('tanggal')
            ->limit(6)
            ->get();

        // Event yang menjual tiket per tempat tidak punya `eventners.ticket_price`,
        // jadi penyaring harga dipindah ke hasTicketPrice() — kalau tidak, event
        // itu hilang dari section E-Tiket di landing.
        $ticketEvents = Eventner::approved()
            ->where('ticket_active', true)
            ->with('venues')
            ->where(function ($q) {
                $q->whereNotNull('ticket_price')
                  ->orWhereHas('venues', fn ($v) => $v->where('is_active', true)->whereNotNull('ticket_price'));
            })
            ->where(function ($q) {
                $q->whereNull('ticket_end')
                  ->orWhere('ticket_end', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->filter(fn ($event) => $event->hasTicketPrice())
            ->values();

        // Section yang benar-benar menghasilkan markup. Saklar di pengaturan
        // saja tidak cukup: beberapa komponen punya gerbang datanya sendiri
        // (`@if($events->count() > 0)`), jadi section yang menyala tapi
        // datanya kosong tidak render apa-apa — dan nav-nya jadi tautan yang
        // kliknya tidak terjadi apa-apa. Persis masalah #vote dan #gallery
        // dulu, yang cuma kebetulan tidak terlihat karena keduanya juga
        // dikeluarkan dari urutan.
        //
        // Daftar ini harus sepakat dengan gerbang di
        // `resources/views/components/landing/*.blade.php`. Yang menjaga
        // kesepakatannya adalah `test_tautan_nav_landing_menunjuk_ke_section_yang_ada`,
        // bukan disiplin manual.
        $sectionsRender = [
            'hero' => true,
            'features' => true,
            'pricing' => true,
            'eventners' => $eventners->isNotEmpty(),
            'ticket' => $ticketEvents->isNotEmpty(),
            'cta' => true,
            'faq' => $this->faqRenderable(),
        ];

        return view('livewire.public.landing-page', [
            'eventners' => $eventners,
            'ticketEvents' => $ticketEvents,
        ])
            ->layout('layouts.landing', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
                'sectionsActive' => $this->sectionsActive,
                'sectionsRender' => $sectionsRender,
            ])
            ->title(app_name());
    }

    /**
     * Section FAQ merender kalau ada pertanyaan, atau ada satu saja kolom
     * kontak — kartu "Masih ada pertanyaan?" menempel di kolom kanannya.
     */
    private function faqRenderable(): bool
    {
        $faq = json_decode(Setting::get('landing_faq') ?? 'null', true) ?? [];
        if (count($faq['items'] ?? []) > 0) {
            return true;
        }

        $kontak = json_decode(Setting::get('landing_contact') ?? 'null', true) ?? [];

        foreach (['phone', 'email', 'address'] as $kolom) {
            if (! empty($kontak[$kolom])) {
                return true;
            }
        }

        return false;
    }
}
