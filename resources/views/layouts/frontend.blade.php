<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Favicon --}}
    @php
        // Halaman non-event (landing, pricing, dll) tidak punya $eventner — defaultkan null
        $eventner = $eventner ?? $subdomainEventner ?? null;
        $_ev = $eventner;
        $_evTitle = $_ev?->nama_event ?? get_setting('site_title', 'Berbaris App');
        $_evDesc = $_ev?->deskripsi ? strip_tags($_ev->deskripsi) : ($_ev?->nama_event ?? get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu'));
        $_evPoster = $_ev?->poster ? asset('storage/' . $_ev->poster) : null;
        $_evLogo = $_ev?->logo_event ? asset('storage/' . $_ev->logo_event) : null;
        $_ogImage = $_evPoster ?? $_evLogo ?? asset('templates/zubaz/assets/images/favicon.ico');
        $_currentUrl = url()->current();
    @endphp

    @isset($_ev?->logo_event)
        <link rel="shortcut icon" type="image/png" href="{{ asset('storage/' . $_ev->logo_event) }}">
    @else
        <link rel="shortcut icon" type="image/png" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}">
    @endisset

    {{-- Halaman ber-token (penilaian juri) memuat kredensial di URL —
         jangan pernah diindeks mesin pencari. Diisi via layoutData('robots'). --}}
    @isset($robots)
        <meta name="robots" content="{{ $robots }}">
    @endisset

    {{-- Meta SEO --}}
    <meta name="description" content="{{ Str::limit($_evDesc, 160) }}">
    <meta name="keywords" content="{{ $_ev?->nama_event }}, {{ $_ev?->diselenggarakan_oleh }}, lomba, kompetisi, event, {{ $_ev?->lokasi }}, {{ $_ev?->tingkat_perlombaan }}">
    <link rel="canonical" href="{{ $_currentUrl }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ get_setting('site_title', 'Berbaris App') }}">
    <meta property="og:title" content="{{ $_evTitle }}">
    <meta property="og:description" content="{{ Str::limit($_evDesc, 200) }}">
    <meta property="og:url" content="{{ $_currentUrl }}">
    <meta property="og:image" content="{{ $_ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">
    @if($_ev?->tanggal)
        <meta property="article:published_time" content="{{ \Carbon\Carbon::parse($_ev->tanggal)->toIso8601String() }}">
    @endif
    @if($_ev?->updated_at)
        <meta property="article:modified_time" content="{{ $_ev->updated_at->toIso8601String() }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $_evTitle }}">
    <meta name="twitter:description" content="{{ Str::limit($_evDesc, 200) }}">
    <meta name="twitter:image" content="{{ $_ogImage }}">

    {{-- JSON-LD Structured Data (Event Schema) — di-gerbang route-name:
         hanya halaman detail event yang meng-emit schema Event. Layout ini
         dipakai bersama halaman ber-token (juri, panitia, scan, scoreboard,
         magic link) yang juga membawa eventner — tanpa gerbang, schema
         Event ikut ter-render di sana (keliru). --}}
    @if($_ev && in_array(request()->route()?->getName(), ['event.detail', 'subdomain.detail']))
        @include('components.seo.json-ld-event', ['eventner' => $_ev])
    @endif

    {{-- Dynamic Theme + Fonts (from Eventner profile) — define BEFORE font link --}}
    @php
        $themeConfig = $eventner?->theme_config ?? [];
        $primaryColor = $themeConfig['primary_color'] ?? '#0062ff';
        $accentColor = $themeConfig['accent_color'] ?? '#a3e635';
        $bgType = $themeConfig['bg_type'] ?? 'solid';
        $bgImage = ($bgType === 'image' && !empty($themeConfig['bg_image'])) ? 'url(' . asset('storage/' . $themeConfig['bg_image']) . ')' : '';

        // Fonts
        $fontSans = $themeConfig['font_sans'] ?? 'Inter';
        $fontDisplay = $themeConfig['font_display'] ?? 'Plus Jakarta Sans';
        $fontWeights = [
            'Inter' => 'wght@400;500;600;700',
            'Bricolage Grotesque' => 'wght@400;500;600;700;800',
            'DM Sans' => 'wght@400;500;700',
            'Poppins' => 'wght@400;500;600;700;800',
            'Nunito' => 'wght@400;500;600;700;800',
            'Work Sans' => 'wght@400;500;600;700',
            'Outfit' => 'wght@400;500;600;700;800',
            'Onest' => 'wght@400;500;600;700;800',
            'Plus Jakarta Sans' => 'wght@400;500;600;700;800',
            'DM Serif Display' => 'wght@400',
            'Playfair Display' => 'wght@400;500;600;700;800',
            'Bebas Neue' => 'wght@400',
        ];
        $sansWeight = $fontWeights[$fontSans] ?? 'wght@400;500;600;700';
        $displayWeight = $fontWeights[$fontDisplay] ?? 'wght@500;600;700;800';

        if ($bgType === 'image' && $bgImage) {
            $bgOverlay = "background-image: {$bgImage}; background-size: cover; background-position: center; background-attachment: fixed;";
        } elseif ($bgType === 'gradient') {
            $dir = $themeConfig['gradient_dir'] ?? 'to bottom right';
            $color2 = $themeConfig['gradient_color'] ?? '#0ea5e9';
            $bgOverlay = "background: linear-gradient({$dir}, {$primaryColor}, {$color2}) fixed; background-size: cover;";
        } else {
            $bgOverlay = '';
        }
    @endphp

    {{-- Fonts (Dynamic) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontSans) }}:{{ $sansWeight }}&family={{ str_replace(' ', '+', $fontDisplay) }}:{{ $displayWeight }}&display=swap" rel="stylesheet">

    {{-- Tabler Icons --}}
    <link rel="stylesheet" href="{{ asset('templates/assets/fonts/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        .ti-rosette-discount-check:before { content: "\f1f8"; }
        :root {
            --event-primary: {{ $primaryColor }};
            --event-accent: {{ $accentColor }};
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
            --font-sans: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif;
            --font-display: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif;
        }
        @if($bgOverlay)
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            {{ $bgOverlay }}
        }
        @endif
    </style>

    {{-- CSS Assets (new Design System via landing.css) --}}
    @vite(['resources/css/landing.css', 'resources/js/app.js'])

    {{-- Dynamic font override (must come after @vite to beat Tailwind @theme) --}}
    <style>
        body, .font-sans {
            font-family: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif !important;
        }
        .font-display, h1, h2, h3, h4, h5, h6,
        [class*="font-display"] {
            font-family: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif !important;
        }
    </style>

    <title>{{ $title ?? ($eventner?->nama_event ?? get_setting('site_title', 'Berbaris App')) }}</title>

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-surface text-on-surface font-sans antialiased min-h-screen flex flex-col justify-between" style="padding-bottom: env(safe-area-inset-bottom);">

    {{-- Header / Navigation --}}
    <header class="glass-nav sticky top-0 z-50">
        <div class="container-landing flex h-16 items-center justify-between">
            {{-- Brand logo & name --}}
            @php
                $platformLogo = get_setting('logo_dark') ? Storage::url(get_setting('logo_dark')) : null;
            @endphp
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-decoration-none">
                @if($eventner)
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" class="h-9 w-9 rounded-lg object-cover">
                    @else
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                            <i class="ti ti-calendar-event text-lg"></i>
                        </div>
                    @endif
                    <span class="font-display text-base font-bold text-deep-slate tracking-tight inline-flex items-center gap-1">
                        <span class="truncate max-w-[200px] sm:max-w-none">{{ $eventner->nama_event }}</span>
                    </span>
                @else
                    @if($platformLogo && is_string($platformLogo))
                        <img src="{{ $platformLogo }}" alt="{{ get_setting('site_title', 'Berbaris App') }}" class="h-9 w-auto md:h-10" style="max-height: 40px; object-fit: contain;">
                    @else
                        <span class="font-display text-lg font-extrabold tracking-tight text-deep-slate">
                            {{ get_setting('site_title', 'Berbaris App') }}
                        </span>
                    @endif
                @endif
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden items-center gap-2 md:flex">
                @isset($eventner?->slug)
                    <a href="{{ event_url($eventner, 'detail') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.detail') || request()->routeIs('subdomain.detail') ? 'text-primary bg-primary/5' : '' }}">Info</a>
                    <a href="{{ event_url($eventner, 'participant') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.participant') || request()->routeIs('subdomain.participant') ? 'text-primary bg-primary/5' : '' }}">Peserta</a>
                    <a href="{{ event_url($eventner, 'results') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.results') || request()->routeIs('subdomain.results') ? 'text-primary bg-primary/5' : '' }}">Hasil</a>
                    @if($eventner?->hasPublishedLayout())
                        {{-- Denah draf tak punya rute publik, jadi menu ini hanya
                             muncul saat halamannya benar-benar ada isinya. --}}
                        <a href="{{ event_url($eventner, 'denah') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.denah') || request()->routeIs('subdomain.denah') ? 'text-primary bg-primary/5' : '' }}">Denah</a>
                    @endif
                    {{-- Vote disembunyikan bila penyelenggara mematikannya di
                         /eventner/vote-settings — pola sama dengan Tiket. --}}
                    @if($eventner?->vote_active)
                        <a href="{{ event_url($eventner, 'vote') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.vote') || request()->routeIs('subdomain.vote') ? 'text-primary bg-primary/5' : '' }}">Vote</a>
                    @endif
                    @if($eventner?->ticket_active && $eventner?->hasTicketPrice())
                        <a href="{{ event_url($eventner, 'ticket') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.ticket') || request()->routeIs('subdomain.ticket') ? 'text-primary bg-primary/5' : '' }}">Tiket</a>
                    @endif
                @else
                    <a href="{{ url('/') }}#features" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Fitur</a>
                    <a href="{{ route('pricing') }}" class="rounded-md px-3 py-2 text-sm font-semibold transition {{ request()->routeIs('pricing') ? 'text-primary' : 'text-on-surface-variant hover:text-primary' }}">Harga</a>
                    <a href="{{ url('/') }}#eventners" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Event</a>
                    <a href="{{ url('/') }}#contact" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Kontak</a>
                @endisset
            </nav>

            {{-- CTA / Action Buttons --}}
            <div class="flex items-center gap-2">
                @isset($eventner?->slug)
                    <a href="{{ event_url($eventner, 'register') }}" class="btn-primary py-2 px-4 text-xs font-bold leading-none inline-flex">
                        Daftar
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost py-2 px-4 text-xs font-bold leading-none hidden sm:inline-flex">Login</a>
                    <a href="{{ route('register.eventner') }}" class="btn-primary py-2 px-4 text-xs font-bold leading-none inline-flex">Daftar Eventner</a>
                @endisset
            </div>
        </div>
    </header>

    {{-- Main Content Slot --}}
    <main class="flex-1 w-full @isset($eventner?->slug) pb-20 md:pb-0 @endisset">
        {{ $slot }}
    </main>

    {{-- Footer section --}}
    @isset($eventner?->slug)
        {{-- Footer halaman EVENT — pita identitas, bukan empat kolom.

             Layout ini dipakai bersama halaman publik lain, tapi footer halaman
             event tidak lagi boleh sama dengan mereka. Seluruh isi kolom
             "Navigasi" sudah dirender dua kali di halaman yang sama — header
             (baris 197–212) dan nav bawah ponsel (342–375) — dan keduanya
             dijaga sakelar yang sama (hasPublishedLayout/vote_active/
             ticket_active), jadi kolom itu tidak memuat satu pun tautan unik.
             Nama penyelenggara sudah ada di hero event, lokasi punya kartu
             sendiri, dan Instagram/TikTok sudah jadi pill di hero sekaligus
             tombol di kartu "Hubungi Penyelenggara". Yang tersisa hanya
             pengulangan setinggi ±320 px.

             Gantinya pita ini menyebut fakta acara — tanggal, lokasi, tingkat —
             yang justru tak muncul di footer mana pun. Tepi atasnya memakai
             warna brand event: --color-primary dan --color-secondary sudah
             disuntik dari theme_config event di baris 123–130, jadi tiap acara
             punya warna footernya sendiri tanpa konfigurasi baru.

             pb-24 (96 px) di ponsel bukan selera: nav bawah `fixed bottom-0`
             setinggi h-16 (64 px) + safe-area menindih dasar halaman, sehingga
             `py-12` yang lama (48 px) menutupi baris hak cipta. --}}
        @php
            $ftMulai = $eventner->tanggal ? \Carbon\Carbon::parse($eventner->tanggal) : null;
            $ftSelesai = $eventner->tanggal_akhir ? \Carbon\Carbon::parse($eventner->tanggal_akhir) : null;
            $ftRentang = null;

            if ($ftMulai) {
                $ftRentang = ($ftSelesai && ! $ftSelesai->isSameDay($ftMulai))
                    ? $ftMulai->translatedFormat('d M') . ' – ' . $ftSelesai->translatedFormat('d F Y')
                    : $ftMulai->translatedFormat('d F Y');
            }

            $ftPenyelenggara = $eventner->diselenggarakan_oleh ?: get_setting('site_title', 'Berbaris App');
        @endphp
        <footer class="bg-deep-slate text-white/80 border-t-2 border-primary">
            <div class="container-landing pt-10 pb-24 md:pt-12 md:pb-12">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-center">
                    {{-- Identitas: siapa yang memiliki acara ini --}}
                    <div class="flex min-w-0 items-center gap-3">
                        @if($eventner->logo_event)
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="" class="h-11 w-11 shrink-0 rounded-xl object-cover">
                        @else
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                                <i class="ti ti-calendar-event text-xl"></i>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <span class="font-display flex items-center gap-1.5 text-base font-bold tracking-tight text-white">
                                <span class="truncate">{{ $eventner->nama_event }}</span>
                                @if($eventner->is_verified)
                                    <x-verified-badge size="sm" />
                                @endif
                            </span>
                            <span class="block truncate text-sm text-white/50">{{ $ftPenyelenggara }}</span>
                        </div>
                    </div>

                    {{-- Fakta acara, lalu tautan sosial sebagai ikon.
                         Tiap butir disembunyikan bila kolomnya kosong — chip
                         kosong terbaca seperti render yang gagal. --}}
                    <div class="flex flex-wrap items-center gap-2">
                        @if($ftRentang)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-white/70">
                                <i class="ti ti-calendar text-sm text-secondary"></i> {{ $ftRentang }}
                            </span>
                        @endif
                        @if($eventner->lokasi)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-white/70">
                                <i class="ti ti-map-pin text-sm text-secondary"></i> {{ $eventner->lokasi }}
                            </span>
                        @endif
                        @if($eventner->tingkat_perlombaan)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-white/70">
                                <i class="ti ti-trophy text-sm text-secondary"></i> Tingkat {{ $eventner->tingkat_perlombaan }}
                            </span>
                        @endif
                        @if($eventner->link_instagram)
                            <a href="{{ $eventner->link_instagram }}" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/70 hover:border-secondary/40 hover:text-secondary transition-colors text-decoration-none">
                                <i class="ti ti-brand-instagram text-sm"></i>
                            </a>
                        @endif
                        @if($eventner->link_tiktok)
                            <a href="{{ $eventner->link_tiktok }}" target="_blank" rel="noopener" aria-label="TikTok" title="TikTok"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/70 hover:border-secondary/40 hover:text-secondary transition-colors text-decoration-none">
                                <i class="ti ti-brand-tiktok text-sm"></i>
                            </a>
                        @endif
                        @if($eventner->link_whatsapp)
                            <a href="{{ Str::startsWith($eventner->link_whatsapp, ['http://', 'https://']) ? $eventner->link_whatsapp : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" rel="noopener" aria-label="WhatsApp" title="WhatsApp"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/70 hover:border-secondary/40 hover:text-secondary transition-colors text-decoration-none">
                                <i class="ti ti-brand-whatsapp text-sm"></i>
                            </a>
                        @endif
                        @if($eventner->link_livestreaming)
                            <a href="{{ $eventner->link_livestreaming }}" target="_blank" rel="noopener" aria-label="Live Stream" title="Live Stream"
                               class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/70 hover:border-secondary/40 hover:text-secondary transition-colors text-decoration-none">
                                <i class="ti ti-brand-youtube text-sm"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Bottom copyright --}}
                <div class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-white/40 sm:flex-row">
                    <p class="m-0">&copy; {{ date('Y') }} {{ $ftPenyelenggara }}. Hak cipta dilindungi.</p>
                    <p class="m-0">Powered by <a href="{{ platform_url('/') }}" class="text-secondary hover:text-secondary hover:underline text-decoration-none transition font-semibold">{{ app_name() }}</a></p>
                </div>
            </div>
        </footer>
    @else
        {{-- Footer non-event: halaman Hall of Fame memakai layout ini tanpa
             layoutData, jadi $eventner kosong di sana. Empat kolom lama tetap
             dipakai apa adanya — footer platform tidak ikut berubah. --}}
        <footer class="bg-deep-slate text-white/80 border-t border-white/5">
            <div class="container-landing py-12 md:py-16">
                <div class="grid gap-10 sm:grid-cols-2 md:grid-cols-4">
                    {{-- Brand Column --}}
                    <div class="flex flex-col gap-4">
                        <a href="{{ url('/') }}" class="flex items-center gap-2 text-decoration-none text-white">
                            @isset($eventner?->logo_event)
                                <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="" class="h-9 w-9 rounded-lg object-cover">
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white">
                                    <i class="ti ti-calendar-event text-xl"></i>
                                </div>
                            @endisset
                            <span class="font-display text-base font-bold tracking-tight text-white">{{ $eventner?->nama_event ?? get_setting('site_title', 'Berbaris App') }}</span>
                        </a>
                        <p class="text-sm text-white/60 leading-relaxed">
                            {{ ($eventner?->deskripsi ?? null) ? Str::limit(strip_tags($eventner->deskripsi), 120) : 'Platform manajemen event dan kompetisi terpadu.' }}
                        </p>
                    </div>

                    {{-- Navigasi Column --}}
                    <div>
                        <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.15em] text-white/90">Navigasi</h4>
                        <ul class="space-y-2 text-sm list-none p-0 m-0">
                            <li><a href="{{ url('/') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Beranda</a></li>
                            <li><a href="{{ route('pricing') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Harga & Paket</a></li>
                        </ul>
                    </div>

                    {{-- Kontak Column --}}
                    <div>
                        <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.15em] text-white/90">Kontak</h4>
                        <ul class="space-y-2 text-sm list-none p-0 m-0">
                            <li><a href="{{ route('privacy') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Kebijakan Privasi</a></li>
                            <li><a href="{{ route('terms') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Syarat & Ketentuan</a></li>
                            <li><a href="{{ route('help') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Bantuan</a></li>
                        </ul>
                    </div>

                    {{-- Penyelenggara Column --}}
                    <div>
                        <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.15em] text-white/90">Penyelenggara</h4>
                        <ul class="space-y-2 text-sm list-none p-0 m-0 text-white/60">
                            <li class="font-medium text-white/80">{{ $eventner?->diselenggarakan_oleh ?? '-' }}</li>
                            @if($eventner?->lokasi)
                                <li class="inline-flex items-center gap-1.5 leading-normal">
                                    <i class="ti ti-map-pin text-base shrink-0"></i> {{ $eventner->lokasi }}
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- Bottom copyright --}}
                <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-white/40 sm:flex-row">
                    <p class="m-0">&copy; {{ date('Y') }} {{ $eventner?->diselenggarakan_oleh ?? get_setting('site_title', 'Berbaris App') }}. Hak cipta dilindungi.</p>
                    <p class="m-0">Powered by <a href="{{ platform_url('/') }}" class="text-secondary hover:text-secondary hover:underline text-decoration-none transition font-semibold">{{ app_name() }}</a></p>
                </div>
            </div>
        </footer>
    @endisset

    {{-- Mobile bottom navigation (hanya halaman event) --}}
    @isset($eventner?->slug)
        @php
            $bottomNavItems = [
                ['url' => event_url($eventner, 'detail'), 'label' => 'Info', 'icon' => 'ti-info-circle', 'active' => request()->routeIs('event.detail') || request()->routeIs('subdomain.detail')],
                ['url' => event_url($eventner, 'participant'), 'label' => 'Peserta', 'icon' => 'ti-users', 'active' => request()->routeIs('event.participant') || request()->routeIs('subdomain.participant')],
                ['url' => event_url($eventner, 'results'), 'label' => 'Hasil', 'icon' => 'ti-trophy', 'active' => request()->routeIs('event.results') || request()->routeIs('subdomain.results')],
            ];
            // Denah hanya muncul kalau ada denah terbit.
            if ($eventner->hasPublishedLayout()) {
                $bottomNavItems[] = ['url' => event_url($eventner, 'denah'), 'label' => 'Denah', 'icon' => 'ti-map-2', 'active' => request()->routeIs('event.denah') || request()->routeIs('subdomain.denah')];
            }
            // Vote mengikuti saklar /eventner/vote-settings, sama seperti Tiket.
            if ($eventner->vote_active) {
                $bottomNavItems[] = ['url' => event_url($eventner, 'vote'), 'label' => 'Vote', 'icon' => 'ti-heart-filled', 'active' => request()->routeIs('event.vote') || request()->routeIs('subdomain.vote')];
            }
            if ($eventner->ticket_active && $eventner->hasTicketPrice()) {
                $bottomNavItems[] = ['url' => event_url($eventner, 'ticket'), 'label' => 'Tiket', 'icon' => 'ti-ticket', 'active' => request()->routeIs('event.ticket') || request()->routeIs('subdomain.ticket')];
            }
        @endphp
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-xl border-t border-outline-variant/50"
             style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Navigasi utama">
            <div class="flex h-16 items-stretch">
                @foreach($bottomNavItems as $item)
                    <a href="{{ $item['url'] }}" data-nav="{{ strtolower($item['label']) }}" class="flex-1 flex flex-col items-center justify-center gap-1 text-decoration-none {{ $item['active'] ? 'text-primary' : 'text-on-surface-variant' }}" aria-current="{{ $item['active'] ? 'page' : 'false' }}">
                        <i class="ti {{ $item['icon'] }} text-xl"></i>
                        <span class="text-[10px] font-bold leading-none">{{ $item['label'] }}</span>
                        {{-- data-nav dipakai test untuk mengunci item mana yang
                             tampil, tanpa bergantung pada ikon/label. --}}
                    </a>
                @endforeach
            </div>
        </nav>
    @endisset

    {{-- Scripts --}}
    @livewireScripts
    @stack('scripts')
</body>

</html>
