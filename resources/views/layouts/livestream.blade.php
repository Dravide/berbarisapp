<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>{{ $title ?? 'Overlay - BARIS APP' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    @php
        $themeConfig = $eventner?->theme_config ?? [];
        $primaryColor = $themeConfig['primary_color'] ?? '#0062ff';
        $accentColor = $themeConfig['accent_color'] ?? '#a3e635';
        // Convert hex to RGB for CSS variable use in rgba()
        $hex2rgb = fn($hex) => implode(', ', sscanf($hex, '#%02x%02x%02x'));
        $primaryRgb = $hex2rgb($primaryColor);
        $accentRgb = $hex2rgb($accentColor);
    @endphp
    <style>
        :root {
            --event-primary: {{ $primaryColor }};
            --event-accent: {{ $accentColor }};
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
            --color-primary-rgb: {{ $primaryRgb }};
            --color-accent-rgb: {{ $accentRgb }};
        }
    </style>

    @vite(['resources/css/landing.css', 'resources/js/app.js'])

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
