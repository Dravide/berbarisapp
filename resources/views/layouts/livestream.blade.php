<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="description" content="{{ $eventner?->nama_event ? 'Live streaming ' . $eventner->nama_event : 'BARIS APP - Platform manajemen event' }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title . ' - ' . get_setting('site_title', 'BARIS APP') : ($eventner?->nama_event ?? get_setting('site_title', 'BARIS APP')) . ' - Live' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    @php
        $themeConfig = $eventner?->theme_config ?? [];
        $primaryColor = $themeConfig['primary_color'] ?? '#0062ff';
        $accentColor = $themeConfig['accent_color'] ?? '#a3e635';
        // Convert hex to RGB for CSS variable use in rgba()
        $hex2rgb = fn($hex) => implode(', ', sscanf($hex, '#%02x%02x%02x'));
        $primaryRgb = $hex2rgb($primaryColor);
        $accentRgb = $hex2rgb($accentColor);

        // Fonts
        $fontSans = $themeConfig['font_sans'] ?? 'Bricolage Grotesque';
        $fontDisplay = $themeConfig['font_display'] ?? 'Bricolage Grotesque';
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

    {{-- Fonts (Dynamic) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontSans) }}:{{ $sansWeight }}&family={{ str_replace(' ', '+', $fontDisplay) }}:{{ $displayWeight }}&display=swap" rel="stylesheet">

    <style>
        :root {
            --event-primary: {{ $primaryColor }};
            --event-accent: {{ $accentColor }};
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
            --color-primary-rgb: {{ $primaryRgb }};
            --color-accent-rgb: {{ $accentRgb }};
            --font-sans: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif;
            --font-display: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif;
        }
    </style>

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

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }
        .overlay-container { width: 1920px; height: 1080px; position: relative; transform-origin: top left; }
        @media (max-width: 1920px) { .overlay-container { transform: scale(calc(100vw / 1920)); } }
    </style>

    @livewireStyles
</head>
<body class="bg-surface text-on-surface font-sans antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
