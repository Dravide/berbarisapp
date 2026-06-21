<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? get_setting('site_title', 'BARIS APP') }}</title>
    <meta name="description" content="{{ get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu') }}">
    <meta name="keywords" content="{{ get_setting('meta_keywords', 'event, kompetisi, lomba, baris, pendaftaran') }}">

    @isset($favicon)
        <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
        <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}" type="image/x-icon">
    @endisset

    {{-- Fonts: Plus Jakarta Sans (display) + Inter (body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Tabler Icons (konsisten dgn app) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    {{-- Tailwind v4 build (landing tokens) --}}
    @vite(['resources/css/landing.css'])

    @livewireStyles
</head>

<body class="min-h-screen bg-surface text-on-surface">

    {{-- Navbar (Glassmorphism) --}}
    <header class="glass-nav sticky top-0 z-50">
        <div class="container-landing">
            <div class="flex h-16 items-center justify-between md:h-20">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    @if(($logoPath ?? null) && is_string($logoPath))
                        <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'BARIS APP') }}" class="h-9 w-auto md:h-10" style="max-height: 40px; object-fit: contain;">
                    @else
                        <span class="font-display text-lg font-extrabold tracking-tight text-deep-slate">
                            {{ get_setting('site_title', 'BARIS APP') }}
                        </span>
                    @endif
                </a>

                {{-- Desktop nav --}}
                <nav class="hidden items-center gap-8 lg:flex">
                    <a href="#hero" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">Beranda</a>
                    <a href="#features" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">Fitur</a>
                    <a href="#about" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">Tentang</a>
                    <a href="#eventners" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">Eventner</a>
                    <a href="#testimonials" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">Testimoni</a>
                    <a href="#faq" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">FAQ</a>
                    <a href="#contact" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">Kontak</a>
                </nav>

                {{-- Desktop CTAs --}}
                <div class="hidden items-center gap-3 lg:flex">
                    <a href="{{ route('login') }}" class="btn-ghost">Login</a>
                    <a href="{{ route('login') }}" class="btn-primary">
                        Mulai Sekarang
                        <i class="ti ti-arrow-right"></i>
                    </a>
                </div>

                {{-- Mobile hamburger --}}
                <button type="button" id="landing-menu-toggle" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-deep-slate transition hover:bg-surface-container lg:hidden" aria-label="Buka menu">
                    <i class="ti ti-menu-2 text-2xl"></i>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="landing-mobile-menu" class="hidden border-t border-outline-variant/50 bg-white/95 backdrop-blur-xl lg:hidden">
            <nav class="container-landing flex flex-col gap-1 py-4">
                <a href="#hero" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Beranda</a>
                <a href="#features" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Fitur</a>
                <a href="#about" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Tentang</a>
                <a href="#eventners" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Eventner</a>
                <a href="#testimonials" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Testimoni</a>
                <a href="#faq" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">FAQ</a>
                <a href="#contact" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Kontak</a>
                <div class="mt-2 flex flex-col gap-2 border-t border-outline-variant/40 pt-3">
                    <a href="{{ route('login') }}" class="btn-ghost w-full">Login</a>
                    <a href="{{ route('login') }}" class="btn-primary w-full">Mulai Sekarang</a>
                </div>
            </nav>
        </div>
    </header>

    {{-- Main --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer (Deep Slate) --}}
    <footer class="bg-deep-slate text-white/70">
        <div class="container-landing py-16">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
                {{-- Brand --}}
                <div class="lg:col-span-1">
                    <a href="{{ url('/') }}" class="mb-4 flex items-center gap-2">
                        @if(($logoPath ?? null) && is_string($logoPath))
                            <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'BARIS APP') }}" class="h-9 w-auto" style="max-height: 40px; object-fit: contain; filter: brightness(0) invert(1);">
                        @else
                            <span class="font-display text-lg font-extrabold tracking-tight text-white">
                                {{ get_setting('site_title', 'BARIS APP') }}
                            </span>
                        @endif
                    </a>
                    <p class="text-sm leading-relaxed">
                        {{ get_setting('site_title', 'BARIS APP') }} — Platform manajemen event dan kompetisi terpadu untuk penyelenggara dan peserta.
                    </p>
                </div>

                {{-- Navigasi --}}
                <div>
                    <h4 class="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-white">Navigasi</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#hero" class="transition hover:text-secondary">Beranda</a></li>
                        <li><a href="#features" class="transition hover:text-secondary">Fitur</a></li>
                        <li><a href="#about" class="transition hover:text-secondary">Tentang</a></li>
                        <li><a href="#faq" class="transition hover:text-secondary">FAQ</a></li>
                        <li><a href="#contact" class="transition hover:text-secondary">Kontak</a></li>
                    </ul>
                </div>

                {{-- Layanan --}}
                <div>
                    <h4 class="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-white">Layanan</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#features" class="transition hover:text-secondary">Pendaftaran Event</a></li>
                        <li><a href="#features" class="transition hover:text-secondary">Penilaian Juri</a></li>
                        <li><a href="#features" class="transition hover:text-secondary">Voting Online</a></li>
                        <li><a href="#features" class="transition hover:text-secondary">E-Tiket</a></li>
                        <li><a href="#features" class="transition hover:text-secondary">Live Scoreboard</a></li>
                    </ul>
                </div>

                {{-- Kontak / Legal --}}
                <div>
                    <h4 class="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-white">Kontak</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('privacy') }}" class="transition hover:text-secondary">Kebijakan Privasi</a></li>
                        <li><a href="{{ route('terms') }}" class="transition hover:text-secondary">Syarat &amp; Ketentuan</a></li>
                        <li><a href="{{ route('help') }}" class="transition hover:text-secondary">Bantuan &amp; Support</a></li>
                    </ul>
                    @php $socials = json_decode(get_setting('landing_social_links', '{}'), true); @endphp
                    @if(!empty($socials))
                    <div class="mt-4 flex items-center gap-3">
                        @if(!empty($socials['instagram']))<a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-instagram"></i></a>@endif
                        @if(!empty($socials['tiktok']))<a href="{{ $socials['tiktok'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-tiktok"></i></a>@endif
                        @if(!empty($socials['youtube']))<a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-youtube"></i></a>@endif
                        @if(!empty($socials['facebook']))<a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-facebook"></i></a>@endif
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs sm:flex-row">
                <p>&copy; {{ date('Y') }} {{ get_setting('site_title', 'BARIS APP') }}. All Rights Reserved.</p>
                <p>Powered by <span class="font-semibold text-secondary">BARIS APP</span></p>
            </div>
        </div>
    </footer>

    @livewireScripts

    {{-- Mobile menu toggle (vanilla, no jQuery) --}}
    <script>
        (function () {
            var btn = document.getElementById('landing-menu-toggle');
            var menu = document.getElementById('landing-mobile-menu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function () {
                menu.classList.toggle('hidden');
                var icon = btn.querySelector('i');
                if (icon) {
                    var open = !menu.classList.contains('hidden');
                    icon.className = open ? 'ti ti-x text-2xl' : 'ti ti-menu-2 text-2xl';
                }
            });
            menu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    menu.classList.add('hidden');
                    var icon = btn.querySelector('i');
                    if (icon) icon.className = 'ti ti-menu-2 text-2xl';
                });
            });
        })();
    </script>
</body>

</html>
