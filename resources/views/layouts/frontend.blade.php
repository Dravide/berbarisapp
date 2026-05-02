<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon -->
    @isset($eventner?->logo_event)
        <link rel="shortcut icon" type="image/png" href="{{ asset('storage/' . $eventner->logo_event) }}" />
    @else
        <link rel="shortcut icon" type="image/png" href="{{ asset('templates/assets/images/logos/favicon.png') }}" />
    @endisset

    <!-- Core Css -->
    <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />

    <title>{{ $title ?? 'Public - BARIS APP' }}</title>
    <!-- Owl Carousel  -->
    <link rel="stylesheet" href="{{ asset('templates/assets/libs/owl.carousel/dist/assets/owl.carousel.min.css') }}" />
    @livewireStyles
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        @isset($eventner?->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="loader" class="lds-ripple img-fluid" style="max-height:60px;" />
        @else
            <img src="{{ asset('templates/assets/images/logos/favicon.png') }}" alt="loader" class="lds-ripple img-fluid" />
        @endisset
    </div>

    <!-- Header Start -->
    <header class="header-fp p-0 w-100">
        <nav class="navbar bg-white py-2 border-bottom">
            <div class="custom-container d-flex align-items-center">
                <a href="{{ url('/') }}" class="text-nowrap logo-img">
                    @isset($eventner?->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" style="max-height:40px; object-fit:contain;" />
                    @else
                        <img src="{{ asset('templates/assets/images/logos/dark-logo.svg') }}" class="dark-logo" alt="Logo" />
                        <img src="{{ asset('templates/assets/images/logos/light-logo.svg') }}" class="light-logo" alt="Logo" />
                    @endisset
                </a>
            </div>
        </nav>
    </header>
    <!-- Header End -->

    <div class="main-wrapper overflow-hidden">
        {{ $slot }}
    </div>

    <!-- Footer Start -->
    <footer>
        <div class="container-fluid">
            <div class="d-flex justify-content-between py-7 flex-md-nowrap flex-wrap gap-sm-0 gap-3">
                <div class="d-flex gap-3 align-items-center">
                    @isset($eventner?->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="icon" style="max-height:24px; object-fit:contain;">
                    @else
                        <img src="{{ asset('templates/assets/images/logos/favicon.png') }}" alt="icon">
                    @endisset
                    <p class="fs-4 mb-0">All rights reserved by {{ $eventner?->diselenggarakan_oleh ?? 'BERBARIS APP' }}.</p>
                </div>
                <div>
                    <p class="mb-0">Dikelola oleh <a target="_blank" href="#" class="text-primary link-primary">Developer</a>.</p>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer End -->

    <!-- Scroll Top -->
    <a href="javascript:void(0)" class="top-btn btn btn-primary d-flex align-items-center justify-content-center round-54 p-0 rounded-circle">
        <i class="ti ti-arrow-up fs-7"></i>
    </a>

    <script src="{{ asset('templates/assets/js/vendor.min.js') }}"></script>
    <!-- Import Js Files -->
    <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('templates/assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
    <script src="{{ asset('templates/assets/js/theme/app.init.js') }}"></script>
    <script src="{{ asset('templates/assets/js/theme/theme.js') }}"></script>
    <script src="{{ asset('templates/assets/js/theme/app.min.js') }}"></script>

    <!-- solar icons -->
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script src="{{ asset('templates/assets/libs/owl.carousel/dist/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('templates/assets/js/frontend-landingpage/homepage.js') }}"></script>
    @livewireScripts
</body>

</html>
