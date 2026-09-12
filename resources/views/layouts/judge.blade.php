@php
    // Layout khusus tablet juri: tanpa navigasi event, tanpa footer marketing,
    // tanpa AdSense/JSON-LD. Tujuannya satu — juri fokus mengisi nilai.
    // Token ada di URL (kredensial), jadi halaman tidak boleh diindeks.
    $eventner = $eventner ?? null;
    $judge = $judge ?? null;

    $themeConfig = $eventner?->theme_config ?? [];
    $primaryColor = $themeConfig['primary_color'] ?? '#0062ff';
    $accentColor = $themeConfig['accent_color'] ?? '#a3e635';
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
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $primaryColor }}">

    <link rel="shortcut icon" type="image/png"
          href="{{ $eventner?->logo_event ? asset('storage/' . $eventner->logo_event) : asset('templates/zubaz/assets/images/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontSans) }}:{{ $sansWeight }}&family={{ str_replace(' ', '+', $fontDisplay) }}:{{ $displayWeight }}&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
            --font-sans: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif;
            --font-display: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif;
        }
        [x-cloak] { display: none !important; }
        /* Tablet juri: cegah zoom tak sengaja saat mengetuk tombol nilai cepat. */
        button, a { -webkit-tap-highlight-color: transparent; }
    </style>

    @vite(['resources/css/landing.css', 'resources/js/app.js'])

    <style>
        body, .font-sans {
            font-family: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif !important;
        }
        .font-display, h1, h2, h3, h4, h5, h6,
        [class*="font-display"] {
            font-family: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif !important;
        }
    </style>

    <title>{{ $title ?? 'Penilaian Juri' }}</title>

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-surface text-on-surface font-sans antialiased min-h-screen flex flex-col"
      x-data="{
          online: navigator.onLine,
          init() {
              window.addEventListener('online', () => this.online = true);
              window.addEventListener('offline', () => this.online = false);
          }
      }">

    {{-- Banner koneksi putus — satu-satunya peringatan yang boleh menutupi layar --}}
    <div x-show="!online" x-cloak
         class="sticky top-0 z-50 bg-amber-500 text-white text-sm font-semibold px-4 py-2.5 text-center">
        <i class="ti ti-wifi-off mr-1"></i>
        Koneksi terputus — nilai belum tersimpan. Tunggu sampai koneksi kembali.
    </div>

    {{-- Header ringkas: identitas event + juri, tanpa menu apa pun --}}
    <header class="sticky top-0 z-40 border-b border-outline-variant/40 bg-white/95 backdrop-blur">
        <div class="container-landing flex h-14 items-center gap-3">
            @if($eventner?->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}"
                     class="h-8 w-8 shrink-0 rounded-lg border border-outline-variant/30 object-cover"
                     alt="{{ $eventner->nama_event }}">
            @else
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-outline-variant/30 bg-primary/10 text-primary">
                    <i class="ti ti-gavel"></i>
                </span>
            @endif

            <span class="min-w-0 flex-1">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-primary leading-none">Penilaian Juri</span>
                <span class="mt-0.5 block truncate font-display text-sm font-bold text-on-surface">{{ $eventner?->nama_event }}</span>
            </span>

            <span class="shrink-0 text-right">
                <span class="block text-[10px] uppercase tracking-wider text-on-surface-variant leading-none">Juri</span>
                <span class="mt-0.5 block max-w-[9rem] truncate text-sm font-bold text-on-surface">{{ $judge?->name }}</span>
            </span>

            <span class="hidden shrink-0 items-center gap-1.5 text-[11px] font-semibold sm:inline-flex"
                  :class="online ? 'text-emerald-600' : 'text-amber-600'">
                <span class="h-2 w-2 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                <span x-text="online ? 'Online' : 'Offline'">Online</span>
            </span>
        </div>
    </header>

    <main class="flex-1 w-full">
        {{ $slot }}
    </main>

    {{-- Footer minimal — tanpa tautan keluar, tanpa hak cipta marketing --}}
    <footer class="border-t border-outline-variant/30 py-4">
        <div class="container-landing flex items-center justify-between gap-3 text-[11px] text-on-surface-variant">
            <span class="truncate">{{ $eventner?->nama_event }}</span>
            <span class="shrink-0">Nilai tersimpan otomatis · {{ app_name() }}</span>
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')
</body>

</html>
