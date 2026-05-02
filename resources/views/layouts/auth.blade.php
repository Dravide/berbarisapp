<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="{{ asset('templates/assets/images/logos/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />
    <title>{{ $title ?? 'Akses Panitia - BARIS APP' }}</title>
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
