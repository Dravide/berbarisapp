<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Favicon --}}
    @isset($eventner?->logo_event)
        <link rel="shortcut icon" type="image/png" href="{{ asset('storage/' . $eventner->logo_event) }}">
    @else
        <link rel="shortcut icon" type="image/png" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}">
    @endisset

    <meta name="description" content="{{ $eventner?->nama_event ?? get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu') }}">

    {{-- Fonts (Plus Jakarta Sans + Inter) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    {{-- Tabler Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    {{-- CSS Assets (new Design System via landing.css) --}}
    @vite(['resources/css/landing.css', 'resources/js/app.js'])

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
                    <a href="{{ route('event.detail', $eventner->slug) }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.detail') ? 'text-primary bg-primary/5' : '' }}">Info</a>
                    <a href="{{ route('event.participant', $eventner->slug) }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.participant') ? 'text-primary bg-primary/5' : '' }}">Peserta</a>
                    <a href="{{ route('event.vote', $eventner->slug) }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.vote') ? 'text-primary bg-primary/5' : '' }}">Vote</a>
                    @if($eventner->ticket_active && $eventner->ticket_price)
                        <a href="{{ route('event.ticket', $eventner->slug) }}" class="rounded-md px-3 py-2 text-sm font-semibold text-on-surface-variant transition hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.ticket') ? 'text-primary bg-primary/5' : '' }}">Tiket</a>
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
                    <a href="{{ route('event.register', $eventner->slug) }}" class="btn-primary py-2 px-4 text-xs font-bold leading-none hidden sm:inline-flex">
                        Daftar
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost py-2 px-4 text-xs font-bold leading-none hidden sm:inline-flex">Login</a>
                    <a href="{{ route('login') }}" class="btn-primary py-2 px-4 text-xs font-bold leading-none hidden sm:inline-flex">Mulai Sekarang</a>
                @endisset

                {{-- Mobile menu toggle button --}}
                <button id="nav-toggle" class="md:hidden flex h-10 w-10 items-center justify-center rounded-md text-on-surface hover:bg-primary/5" aria-label="Toggle Menu">
                    <i class="ti ti-menu-2 text-2xl"></i>
                </button>
            </div>
        </div>

        {{-- Mobile menu (hidden by default) --}}
        <div id="mobile-menu" class="hidden border-t border-outline-variant/50 md:hidden bg-white/95 backdrop-blur-xl">
            <div class="container-landing flex flex-col gap-1 py-3">
                @isset($eventner?->slug)
                    <a href="{{ route('event.detail', $eventner->slug) }}" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.detail') ? 'text-primary bg-primary/5' : '' }}">Info Event</a>
                    <a href="{{ route('event.participant', $eventner->slug) }}" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.participant') ? 'text-primary bg-primary/5' : '' }}">Peserta</a>
                    <a href="{{ route('event.vote', $eventner->slug) }}" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.vote') ? 'text-primary bg-primary/5' : '' }}">Vote</a>
                    @if($eventner->ticket_active && $eventner->ticket_price)
                        <a href="{{ route('event.ticket', $eventner->slug) }}" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5 hover:text-primary {{ request()->routeIs('event.ticket') ? 'text-primary bg-primary/5' : '' }}">Tiket</a>
                    @endif
                    <a href="{{ route('event.register', $eventner->slug) }}" class="btn-primary text-center mt-2 py-2.5">Daftar Sekarang</a>
                @else
                    <a href="{{ url('/') }}#features" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5">Fitur</a>
                    <a href="{{ url('/') }}#eventners" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5">Event</a>
                    <a href="{{ url('/') }}#contact" class="block rounded-md px-3 py-2.5 text-sm font-semibold text-on-surface-variant hover:bg-primary/5">Kontak</a>
                    <a href="{{ route('login') }}" class="btn-ghost text-center mt-2 py-2.5">Login</a>
                    <a href="{{ route('login') }}" class="btn-primary text-center mt-1 py-2.5">Mulai Sekarang</a>
                @endisset
            </div>
        </div>
    </header>

    {{-- Main Content Slot --}}
    <main class="flex-1 w-full">
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
                        {{ $eventner?->deskripsi ? Str::limit(strip_tags($eventner->deskripsi), 120) : 'Platform manajemen event dan kompetisi terpadu.' }}
                    </p>
                </div>

                {{-- Navigasi Column --}}
                <div>
                    <h4 class="overline !text-white/90 mb-4">Navigasi</h4>
                    <ul class="space-y-2 text-sm list-none p-0 m-0">
                        @isset($eventner?->slug)
                            <li><a href="{{ route('event.detail', $eventner->slug) }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Info Event</a></li>
                            <li><a href="{{ route('event.participant', $eventner->slug) }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Daftar Peserta</a></li>
                            <li><a href="{{ route('event.vote', $eventner->slug) }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Voting</a></li>
                            @if($eventner->ticket_active)
                                <li><a href="{{ route('event.ticket', $eventner->slug) }}" class="text-white/60 hover:text-secondary text-decoration-none transition">Beli Tiket</a></li>
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

    {{-- Scripts --}}
    <script>
        document.getElementById('nav-toggle')?.addEventListener('click', () => {
            document.getElementById('mobile-menu')?.classList.toggle('hidden');
        });
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>
