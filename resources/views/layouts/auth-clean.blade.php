<!DOCTYPE html>
<html lang="id">

@php
    // Layout khusus halaman tamu (login). Sengaja TIDAK memakai navbar/footer
    // landing karena tautannya berupa anchor (#hero, #fitur) yang tidak ada di
    // sini. Token warna & font tetap dibaca dari Pengaturan supaya seragam
    // dengan landing dan pricing.
    $siteTitle = get_setting('site_title', 'Berbaris App');
    $pageTitle = $title ?? "Masuk - {$siteTitle}";

    $primaryColor = get_setting('site_primary_color', '#0062ff');
    $accentColor = get_setting('site_accent_color', '#a3e635');

    $fontSans = get_setting('site_font_sans', 'Inter');
    $fontDisplay = get_setting('site_font_display', 'Plus Jakarta Sans');
    $fontWeights = [
        'Inter' => 'wght@400;500;600;700',
        'Bricolage Grotesque' => 'wght@400;500;600;700;800',
        'DM Sans' => 'wght@400;500;700',
        'Poppins' => 'wght@400;500;600;700;800',
        'Nunito' => 'wght@400;500;600;700;800',
        'Work Sans' => 'wght@400;500;600;700',
        'Plus Jakarta Sans' => 'wght@400;500;600;700;800',
        'Outfit' => 'wght@400;500;600;700;800',
        'Manrope' => 'wght@400;500;600;700;800',
    ];
    $sansWeight = $fontWeights[$fontSans] ?? 'wght@400;500;600;700';
    $displayWeight = $fontWeights[$fontDisplay] ?? 'wght@600;700;800';

    $favicon = get_setting('favicon')
        ? Storage::disk('public')->url(get_setting('favicon'))
        : asset('templates/zubaz/assets/images/favicon.ico');

    $metaDescription = get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu');
    $ogImage = get_setting('logo_dark')
        ? Storage::disk('public')->url(get_setting('logo_dark'))
        : asset('templates/assets/images/logos/favicon.png');

    // Login sendiri tidak perlu diindeks, tapi halaman tamu lain di layout ini
    // (mis. daftar eventner) tetap boleh. Diset dari komponen bila perlu.
    $robots = $robots ?? 'index, follow';
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ Str::limit($metaDescription, 160) }}">
    <meta name="keywords" content="{{ get_setting('meta_keywords', 'event, kompetisi, lomba, baris, pendaftaran, panitia') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="{{ $robots }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ Str::limit($metaDescription, 200) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="id_ID">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ Str::limit($metaDescription, 200) }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
    <link rel="icon" href="{{ $favicon }}" type="image/x-icon">

    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontSans) }}:{{ $sansWeight }}&family={{ str_replace(' ', '+', $fontDisplay) }}:{{ $displayWeight }}&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

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

    @livewireStyles
</head>

<body class="min-h-screen bg-surface text-on-surface">
    {{ $slot }}
    @livewireScripts
</body>

</html>
