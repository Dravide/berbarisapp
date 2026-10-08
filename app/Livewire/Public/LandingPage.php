<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Setting;
use App\Models\Eventner;
use Illuminate\Support\Facades\Storage;

class LandingPage extends Component
{
    /**
     * Section yang punya komponen Blade-nya, dalam urutan bawaan.
     *
     * Urutan yang tersimpan di settings hanya *preferensi urutan* milik admin;
     * daftar section yang benar-benar ada ditentukan di sini. Kalau halaman
     * mempercayai urutan tersimpan apa adanya, dua hal bisa terjadi: nama lama
     * yang view-nya sudah dihapus dipanggil (error 500), dan section yang
     * belum ada di urutan lama tidak pernah muncul sama sekali.
     *
     * Yang kedua itu nyata terjadi: `pricing` dulu diselipkan oleh shim di
     * dalam mount() karena urutan bawaan komponen admin tidak memuatnya.
     * Begitu shim-nya dibuang dan urutan lama masih tersimpan di DB,
     * halaman harga hilang dari laman produksi.
     */
    private const SECTION_DIKENAL = ['hero', 'features', 'pricing', 'perbandingan', 'eventners', 'ticket', 'faq', 'cta'];

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
        $tersimpan = json_decode(Setting::get('landing_sections_order', '[]'), true) ?: [];
        $this->sectionsOrder = $this->lengkapiUrutan($tersimpan);

        $this->sectionsActive = json_decode(
            Setting::get('landing_sections_active', '{"hero":true,"features":true,"pricing":true,"perbandingan":true,"eventners":true,"ticket":true,"faq":true,"cta":true}'),
            true
        );

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

    /**
     * Urutan tersimpan → urutan yang benar-benar bisa dirender.
     *
     * Dua penyaringan, dan keduanya perlu:
     *
     * 1. Nama yang tidak punya komponen Blade dibuang. Kalau tidak, urutan
     *    lama yang masih tersimpan (mis. `testimonials`, `gallery`, `vote`)
     *    akan memanggil view yang sudah dihapus — error 500 di laman depan.
     * 2. Nama yang belum ada di urutan tersimpan ditambahkan di posisi
     *    bawaannya. Ini yang bikin section baru muncul tanpa harus menunggu
     *    admin menyusun ulang atau migration dijalankan — persis kasus
     *    `pricing` di produksi.
     *
     * Urutan relatif pilihan admin tidak diusik: yang tersimpan tetap
     * berurutan seperti semula, tambahan hanya menempel di sekitarnya.
     */
    private function lengkapiUrutan(array $tersimpan): array
    {
        $urutan = array_values(array_filter(
            $tersimpan,
            fn ($type) => in_array($type, self::SECTION_DIKENAL, true)
        ));

        foreach (self::SECTION_DIKENAL as $posisi => $type) {
            if (in_array($type, $urutan, true)) {
                continue;
            }

            // Sisipkan setelah tetangga terdekat yang sudah ada di urutan
            // tersimpan, supaya section baru tidak selalu jatuh ke paling
            // belakang. Kalau tidak ada satu pun tetangga sebelumnya,
            // taruh di depan.
            $jangkarkan = null;
            for ($i = $posisi - 1; $i >= 0; $i--) {
                $kandidat = self::SECTION_DIKENAL[$i];
                $di = array_search($kandidat, $urutan, true);
                if ($di !== false) {
                    $jangkarkan = $di + 1;
                    break;
                }
            }

            array_splice($urutan, $jangkarkan ?? 0, 0, [$type]);
        }

        return $urutan;
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
            'perbandingan' => true,
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
