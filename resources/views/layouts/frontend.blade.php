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
        $_evTitle = $_ev?->nama_event ?? get_setting('site_title', 'BARIS APP');
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

    {{-- Meta SEO --}}
    <meta name="description" content="{{ Str::limit($_evDesc, 160) }}">
    <meta name="keywords" content="{{ $_ev?->nama_event }}, {{ $_ev?->diselenggarakan_oleh }}, lomba, kompetisi, event, {{ $_ev?->lokasi }}, {{ $_ev?->tingkat_perlombaan }}">
    <link rel="canonical" href="{{ $_currentUrl }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ get_setting('site_title', 'BARIS APP') }}">
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

    {{-- JSON-LD Structured Data (Event Schema) — built in PHP to avoid Blade @if nesting in JSON braces --}}
    @php
        $_jsonLd = null;
        if ($_ev) {
            $_ld = [
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => $_ev->nama_event,
                'description' => Str::limit(strip_tags($_ev->deskripsi ?? ''), 400),
            ];
            if ($_ev->poster || $_ev->logo_event) {
                $_ld['image'] = $_ogImage;
            }
            if ($_ev->tanggal) {
                $_ld['startDate'] = \Carbon\Carbon::parse($_ev->tanggal)->toIso8601String();
                $_ld['endDate'] = $_ev->tanggal_akhir
                    ? \Carbon\Carbon::parse($_ev->tanggal_akhir)->toIso8601String()
                    : \Carbon\Carbon::parse($_ev->tanggal)->toIso8601String();
            }
            if ($_ev->lokasi) {
                $_loc = [
                    '@type' => 'Place',
                    'name' => $_ev->venue ?: $_ev->lokasi,
                    'address' => $_ev->lokasi,
                ];
                if ($_ev->latitude && $_ev->longitude) {
                    $_loc['geo'] = [
                        '@type' => 'GeoCoordinates',
                        'latitude' => $_ev->latitude,
                        'longitude' => $_ev->longitude,
                    ];
                }
                $_ld['location'] = $_loc;
            }
            $_ld['organizer'] = [
                '@type' => 'Organization',
                'name' => $_ev->diselenggarakan_oleh ?: 'BARIS APP',
            ];
            $_ld['eventStatus'] = 'https://schema.org/EventScheduled';
            $_ld['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
            $_jsonLd = json_encode($_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    @endphp
    @if($_jsonLd)
    <script type="application/ld+json">{!! $_jsonLd !!}</script>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
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

    <title>{{ $title ?? ($eventner?->nama_event ?? 'BARIS APP') }}</title>

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-surface text-on-surface font-sans antialiased min-h-screen flex flex-col justify-between" style="padding-bottom: env(safe-area-inset-bottom);">

    {{-- Header / Navigation --}}
    <header class="glass-nav sticky top-0 z-50">
        <div class="container-landing flex h-16 items-center justify-between">
            {{-- Brand logo & name --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-decoration-none">
                @isset($eventner?->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" class="h-9 w-9 rounded-lg object-cover">
                @else
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <i class="ti ti-calendar-event text-xl"></i>
                    </div>
                @endisset
                <span class="font-display text-base font-bold text-deep-slate tracking-tight">
                    {{ $eventner?->nama_event ?? get_setting('site_title', 'BARIS APP') }}
                </span>
            </a>

            {{-- Desktop navigation --}}
            <nav class="hidden items-center gap-2 md:flex">
                @isset($eventner?->slug)
                    <a href="{{ event_url($eventner, 'detail') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.detail') || request()->routeIs('subdomain.detail') ? 'text-primary bg-primary/5' : '' }}">Info</a>
                    <a href="{{ event_url($eventner, 'participant') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.participant') || request()->routeIs('subdomain.participant') ? 'text-primary bg-primary/5' : '' }}">Peserta</a>
                    <a href="{{ event_url($eventner, 'results') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.results') || request()->routeIs('subdomain.results') ? 'text-primary bg-primary/5' : '' }}">Hasil</a>
                    <a href="{{ event_url($eventner, 'vote') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.vote') || request()->routeIs('subdomain.vote') ? 'text-primary bg-primary/5' : '' }}">Vote</a>
                    @if($eventner?->ticket_active && $eventner?->ticket_price)
                        <a href="{{ event_url($eventner, 'ticket') }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.ticket') || request()->routeIs('subdomain.ticket') ? 'text-primary bg-primary/5' : '' }}">Tiket</a>
                    @endif
                @else
                    <a href="{{ url('/') }}#features" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant hover:text-primary">Fitur</a>
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
                    <a href="{{ route('login') }}" class="btn-primary py-2 px-4 text-xs font-bold leading-none inline-flex">Mulai Sekarang</a>
                @endisset
            </div>
        </div>
    </header>

    {{-- Main Content Slot --}}
    <main class="flex-1 w-full @isset($eventner?->slug) pb-20 md:pb-0 @endisset">
        {{ $slot }}
    </main>

    {{-- Footer section --}}
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
                        <span class="font-display text-base font-bold tracking-tight text-white">{{ $eventner?->nama_event ?? get_setting('site_title', 'BARIS APP') }}</span>
                    </a>
                    <p class="text-sm text-white/60 leading-relaxed">
                        {{ ($eventner?->deskripsi ?? null) ? Str::limit(strip_tags($eventner->deskripsi), 120) : 'Platform manajemen event dan kompetisi terpadu.' }}
                    </p>
                </div>

                {{-- Navigasi Column --}}
                <div>
                    <h4 class="overline !text-white/90 mb-4">Navigasi</h4>
                    <ul class="space-y-2 text-sm list-none p-0 m-0">
                        @isset($eventner?->slug)
                            <li><a href="{{ event_url($eventner, 'detail') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Info Event</a></li>
                            <li><a href="{{ event_url($eventner, 'participant') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Daftar Peserta</a></li>
                            <li><a href="{{ event_url($eventner, 'results') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Hasil Perlombaan</a></li>
                            <li><a href="{{ event_url($eventner, 'vote') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Voting</a></li>
                            @if($eventner?->ticket_active)
                                <li><a href="{{ event_url($eventner, 'ticket') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Beli Tiket</a></li>
                            @endif
                        @else
                            <li><a href="{{ url('/') }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Beranda</a></li>
                        @endisset
                    </ul>
                </div>

                {{-- Kontak Column --}}
                <div>
                    <h4 class="overline !text-white/90 mb-4">Kontak</h4>
                    <ul class="space-y-2 text-sm list-none p-0 m-0">
                        @if($eventner?->link_whatsapp)
                            <li>
                                <a href="{{ Str::startsWith($eventner->link_whatsapp, ['http://', 'https://']) ? $eventner->link_whatsapp : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="text-white/60 hover:text-secondary text-decoration-none transition inline-flex items-center gap-1.5">
                                    <i class="ti ti-brand-whatsapp text-base"></i> WhatsApp
                                </a>
                            </li>
                        @endif
                        @if($eventner?->link_instagram)
                            <li>
                                <a href="{{ $eventner->link_instagram }}" target="_blank" class="text-white/60 hover:text-secondary text-decoration-none transition inline-flex items-center gap-1.5">
                                    <i class="ti ti-brand-instagram text-base"></i> Instagram
                                </a>
                            </li>
                        @endif
                        @if($eventner?->link_tiktok)
                            <li>
                                <a href="{{ $eventner->link_tiktok }}" target="_blank" class="text-white/60 hover:text-secondary text-decoration-none transition inline-flex items-center gap-1.5">
                                    <i class="ti ti-brand-tiktok text-base"></i> TikTok
                                </a>
                            </li>
                        @endif
                        @if($eventner?->link_livestreaming)
                            <li>
                                <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="text-white/60 hover:text-secondary text-decoration-none transition inline-flex items-center gap-1.5">
                                    <i class="ti ti-brand-youtube text-base"></i> Live Stream
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>

                {{-- Penyelenggara Column --}}
                <div>
                    <h4 class="overline !text-white/90 mb-4">Penyelenggara</h4>
                    <ul class="space-y-2 text-sm list-none p-0 m-0 text-white/60">
                        <li class="font-medium text-white/80">{{ $eventner?->diselenggarakan_oleh ?? '-' }}</li>
                        @if($eventner?->lokasi)
                            <li class="inline-flex items-center gap-1.5 leading-normal">
                                <i class="ti ti-map-pin text-primary text-base shrink-0"></i> {{ $eventner->lokasi }}
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- Bottom copyright --}}
            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-white/40 sm:flex-row">
                <p class="m-0">&copy; {{ date('Y') }} {{ $eventner?->diselenggarakan_oleh ?? get_setting('site_title', 'BARIS APP') }}. Hak cipta dilindungi.</p>
                <p class="m-0">Powered by <a href="{{ url('/') }}" class="text-secondary hover:text-secondary hover:underline text-decoration-none transition font-semibold">BARIS APP</a></p>
            </div>
        </div>
    </footer>

    {{-- Mobile bottom navigation (hanya halaman event) --}}
    @isset($eventner?->slug)
        @php
            $bottomNavLeft = [
                ['url' => event_url($eventner, 'detail'), 'label' => 'Info', 'icon' => 'ti-info-circle', 'active' => request()->routeIs('event.detail') || request()->routeIs('subdomain.detail')],
                ['url' => event_url($eventner, 'participant'), 'label' => 'Peserta', 'icon' => 'ti-users', 'active' => request()->routeIs('event.participant') || request()->routeIs('subdomain.participant')],
            ];
            $bottomNavRight = [
                ['url' => event_url($eventner, 'results'), 'label' => 'Hasil', 'icon' => 'ti-trophy', 'active' => request()->routeIs('event.results') || request()->routeIs('subdomain.results')],
                ['url' => event_url($eventner, 'vote'), 'label' => 'Vote', 'icon' => 'ti-heart-filled', 'active' => request()->routeIs('event.vote') || request()->routeIs('subdomain.vote')],
            ];
            if ($eventner->ticket_active && $eventner->ticket_price) {
                $bottomNavRight[] = ['url' => event_url($eventner, 'ticket'), 'label' => 'Tiket', 'icon' => 'ti-ticket', 'active' => request()->routeIs('event.ticket') || request()->routeIs('subdomain.ticket')];
            }
        @endphp
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-xl border-t border-outline-variant/50"
             style="padding-bottom: env(safe-area-inset-bottom);" aria-label="Navigasi utama">
            <div class="flex h-16 items-stretch">
                @foreach($bottomNavLeft as $item)
                    <a href="{{ $item['url'] }}" class="flex-1 flex flex-col items-center justify-center gap-1 text-decoration-none {{ $item['active'] ? 'text-primary' : 'text-on-surface-variant' }}" aria-current="{{ $item['active'] ? 'page' : 'false' }}">
                        <i class="ti {{ $item['icon'] }} text-xl"></i>
                        <span class="text-[10px] font-bold leading-none">{{ $item['label'] }}</span>
                    </a>
                @endforeach
                {{-- Spacer tengah — tombol Daftar sudah ada di header --}}
                <div class="flex-1"></div>
                @foreach($bottomNavRight as $item)
                    <a href="{{ $item['url'] }}" class="flex-1 flex flex-col items-center justify-center gap-1 text-decoration-none {{ $item['active'] ? 'text-primary' : 'text-on-surface-variant' }}" aria-current="{{ $item['active'] ? 'page' : 'false' }}">
                        <i class="ti {{ $item['icon'] }} text-xl"></i>
                        <span class="text-[10px] font-bold leading-none">{{ $item['label'] }}</span>
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
