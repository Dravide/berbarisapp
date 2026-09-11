<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

@php
    // Layout ini dipakai halaman publik (login, daftar) sekaligus halaman privat
    // (dashboard eventner belum aktif, input nilai). SEO dihitung di sini supaya
    // title/description/OG tidak diulang di tiap komponen.
    $siteTitle = get_setting('site_title', 'Berbaris App');
    $pageTitle = $title ?? "{$siteTitle} - Masuk";

    // Halaman privat jangan diindeks; hanya halaman tamu yang layak muncul di mesin pencari.
    $robots = auth()->check() ? 'noindex, nofollow' : 'index, follow';

    $metaDescription = get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu');

    // Gambar pratinjau: logo situs dari Pengaturan, fallback ke favicon template.
    $ogImage = get_setting('logo_dark')
        ? Storage::disk('public')->url(get_setting('logo_dark'))
        : asset('templates/assets/images/logos/favicon.png');

    // Favicon: pakai favicon dari Pengaturan Situs bila ada.
    $favicon = get_setting('favicon')
        ? Storage::disk('public')->url(get_setting('favicon'))
        : asset('templates/assets/images/logos/favicon.png');

    $canonical = url()->current();
@endphp
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ $favicon }}" />
    <link rel="icon" href="{{ $favicon }}" />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ Str::limit($metaDescription, 160) }}">
    <meta name="keywords" content="{{ get_setting('meta_keywords', 'event, kompetisi, lomba, baris, pendaftaran, panitia') }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ Str::limit($metaDescription, 200) }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:locale" content="id_ID">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ Str::limit($metaDescription, 200) }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />
    @livewireStyles
</head>

<body>
    <div class="preloader">
        <img src="{{ $favicon }}" alt="loader" class="lds-ripple img-fluid" />
    </div>
    <div id="main-wrapper" class="auth-customizer-none">
        {{ $slot }}
    </div>

    <script src="{{ asset('templates/assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('templates/assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
    <script src="{{ asset('templates/assets/js/theme/app.init.js') }}"></script>
    <script src="{{ asset('templates/assets/js/theme/theme.js') }}"></script>
    <script src="{{ asset('templates/assets/js/theme/app.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    @livewireScripts
</body>

</html>
