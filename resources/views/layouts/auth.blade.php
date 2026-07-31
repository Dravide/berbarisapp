<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="{{ asset('templates/assets/images/logos/favicon.png') }}" />
    <meta name="description" content="{{ get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu') }}">
    <meta name="keywords" content="{{ get_setting('meta_keywords', 'event, kompetisi, lomba, baris, pendaftaran, panitia') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ get_setting('site_title', 'BARIS APP') }}">
    <meta property="og:title" content="{{ $title ?? 'Login - ' . get_setting('site_title', 'BARIS APP') }}">
    <meta property="og:description" content="{{ get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $title ?? 'Login - ' . get_setting('site_title', 'BARIS APP') }}">

    <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />
    <title>{{ $title ?? get_setting('site_title', 'BARIS APP') . ' - Masuk' }}</title>
    @livewireStyles
</head>

<body>
    <div class="preloader">
        <img src="{{ asset('templates/assets/images/logos/favicon.png') }}" alt="loader" class="lds-ripple img-fluid" />
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
