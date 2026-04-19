<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="{{ asset('templates/assets/images/logos/favicon.png') }}" />

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
        <img src="{{ asset('templates/assets/images/logos/favicon.png') }}" alt="loader" class="lds-ripple img-fluid" />
    </div>

    <!-- Header Start -->
    <header class="header-fp p-0 w-100">
        <nav class="navbar navbar-expand-lg bg-primary-subtle py-2">
            <div class="custom-container d-flex align-items-center justify-content-between">
                <a href="{{ url('/') }}" class="text-nowrap logo-img">
                    <img src="{{ asset('templates/assets/images/logos/dark-logo.svg') }}" class="dark-logo" alt="Logo" />
                    <img src="{{ asset('templates/assets/images/logos/light-logo.svg') }}" class="light-logo" alt="Logo" />
                </a>
                <button class="navbar-toggler border-0 p-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
                    <i class="ti ti-menu-2 fs-8"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav mx-auto mb-2 gap-xl-7 gap-8 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link fs-4 fw-bold text-dark link-primary" href="{{ url('/') }}">Beranda</a>
                        </li>
                    </ul>
                    <div>
                        <a href="{{ route('login') }}" class="btn btn-primary py-2 px-4 shadow-sm">Log In</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Header End -->

    <!-- Responsive Sidebar Start -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
        <div class="offcanvas-header">
            <a href="{{ url('/') }}">
                <img src="{{ asset('templates/assets/images/logos/dark-logo.svg') }}" alt="Logo" />
            </a>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-unstyled ps-0">
                <li class="mb-1">
                    <a href="{{ url('/') }}" class="px-0 fs-4 d-block text-dark link-primary w-100 py-2">
                        Beranda
                    </a>
                </li>
                <li class="mt-3">
                    <a href="{{ route('login') }}" class="btn btn-primary w-100">Log In</a>
                </li>
            </ul>
        </div>
    </div>
    <!-- Responsive Sidebar End -->

    <div class="main-wrapper overflow-hidden">
        {{ $slot }}
    </div>

    <!-- Footer Start -->
    <footer>
        <div class="container-fluid">
            <div class="d-flex justify-content-between py-7 flex-md-nowrap flex-wrap gap-sm-0 gap-3">
                <div class="d-flex gap-3 align-items-center">
                    <img src="{{ asset('templates/assets/images/logos/favicon.png') }}" alt="icon">
                    <p class="fs-4 mb-0">All rights reserved by BERBARIS APP. </p>
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
