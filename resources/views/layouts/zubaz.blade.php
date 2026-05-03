<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? get_setting('site_title', 'BARIS APP') }}</title>
    <meta name="description" content="{{ get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu') }}">
    <meta name="keywords" content="{{ get_setting('meta_keywords', 'event, kompetisi, lomba, baris, pendaftaran') }}">

    @isset($favicon)
        <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
        <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}" type="image/x-icon">
        <link rel="icon" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}" type="image/x-icon">
    @endisset

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Raleway:wght@600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/app.min.css') }}">

    @livewireStyles
</head>

<body class="light">

    {{-- Preloader --}}
    <div class="zubuz-preloader-wrap">
        <div class="zubuz-preloader">
            <div></div><div></div><div></div><div></div>
        </div>
    </div>

    {{-- Header / Navbar --}}
    <header class="site-header site-header--menu-center zubuz-header-section" id="sticky-menu">
        <div class="container">
            <nav class="navbar site-navbar">
                <div class="brand-logo">
                    <a href="{{ url('/') }}" style="display: inline-block;">
                        @if(($logoPath ?? null) && is_string($logoPath))
                            <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'BARIS APP') }}" class="light-version-logo" style="display: block; width: 128px;">
                            <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'BARIS APP') }}" class="dark-version-logo" style="display: none; width: 128px;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/logo/logo-dark.png') }}" alt="BARIS APP" class="light-version-logo">
                            <img src="{{ asset('templates/zubaz/assets/images/logo/logo-dark.png') }}" alt="BARIS APP" class="dark-version-logo" style="display: none;">
                        @endif
                    </a>
                </div>

                <div class="menu-block-wrapper">
                    <div class="menu-overlay"></div>
                    <nav class="menu-block" id="append-menu-header">
                        <div class="mobile-menu-head">
                            <div class="go-back"><i class="fa fa-angle-left"></i></div>
                            <div class="current-menu-title"></div>
                            <div class="mobile-menu-close">&times;</div>
                        </div>
                        <ul class="site-menu-main">
                            <li class="nav-item">
                                <a href="#hero" class="nav-link-item">Beranda</a>
                            </li>
                            <li class="nav-item">
                                <a href="#features" class="nav-link-item">Fitur</a>
                            </li>
                            <li class="nav-item">
                                <a href="#about" class="nav-link-item">Tentang</a>
                            </li>
                            <li class="nav-item">
                                <a href="#eventners" class="nav-link-item">Eventner</a>
                            </li>
                            <li class="nav-item">
                                <a href="#testimonials" class="nav-link-item">Testimoni</a>
                            </li>
                            <li class="nav-item">
                                <a href="#faq" class="nav-link-item">FAQ</a>
                            </li>
                            <li class="nav-item">
                                <a href="#contact" class="nav-link-item">Kontak</a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="header-btn header-btn-l1 ms-auto d-none d-xs-inline-flex">
                    <div class="zubuz-header-btn-wrap">
                        <a class="zubuz-login-btn" href="{{ route('login') }}">Login</a>
                    </div>
                    <a class="zubuz-default-btn zubuz-header-btn" href="{{ route('login') }}">
                        <span>Mulai Sekarang</span>
                    </a>
                </div>

                <div class="mobile-menu-trigger light">
                    <span></span>
                </div>
            </nav>
        </div>
    </header>

    {{-- Main Content --}}
    {{ $slot }}

    {{-- Footer --}}
    <footer class="zubuz-footer-section main-footer">
        <div class="container">
            <div class="zubuz-footer-top">
                <div class="row">
                    <div class="col-xl-4 col-lg-12">
                        <div class="zubuz-footer-textarea">
                            <a href="{{ url('/') }}">
                                @if(($logoPath ?? null) && is_string($logoPath))
                            <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'BARIS APP') }}" class="light-version-logo" style="display: block; width: 128px;">
                            <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'BARIS APP') }}" class="dark-version-logo" style="display: none; width: 128px;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/logo/logo-dark.png') }}" alt="BARIS APP" class="light-version-logo">
                            <img src="{{ asset('templates/zubaz/assets/images/logo/logo-dark.png') }}" alt="BARIS APP" class="dark-version-logo" style="display: none;">
                        @endif
                            </a>
                            <p>{{ get_setting('site_title', 'BARIS APP') }} — Platform manajemen event dan kompetisi terpadu untuk penyelenggara dan peserta.</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <div class="zubuz-footer-menu extar-margin">
                            <div class="zubuz-footer-title"><p>Navigasi</p></div>
                            <ul>
                                <li><a href="#hero">Beranda</a></li>
                                <li><a href="#features">Fitur</a></li>
                                <li><a href="#about">Tentang</a></li>
                                <li><a href="#faq">FAQ</a></li>
                                <li><a href="#contact">Kontak</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="zubuz-footer-menu">
                            <div class="zubuz-footer-title"><p>Layanan</p></div>
                            <ul>
                                <li><a href="#">Pendaftaran Event</a></li>
                                <li><a href="#">Penilaian Juri</a></li>
                                <li><a href="#">Voting Online</a></li>
                                <li><a href="#">E-Tiket</a></li>
                                <li><a href="#">Live Scoreboard</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <div class="zubuz-footer-menu extar-margin">
                            <div class="zubuz-footer-title"><p>Kontak</p></div>
                            <ul>
                                <li><a href="mailto:{{ get_setting('landing_contact') ? json_decode(get_setting('landing_contact'))->email ?? '#' : '#' }}">Email Kami</a></li>
                                <li><a href="{{ route('privacy') }}">Kebijakan Privasi</a></li>
                                <li><a href="{{ route('terms') }}">Syarat & Ketentuan</a></li>
                                <li><a href="{{ route('help') }}">Bantuan & Support</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="zubuz-footer-bottom">
                <div class="zubuz-social-icon order-md-2">
                    <ul>
                        @php
                            $socials = json_decode(get_setting('landing_social_links', '{}'), true);
                        @endphp
                        @if(!empty($socials['instagram']))
                        <li><a href="{{ $socials['instagram'] }}" target="_blank"><i class="fab fa-instagram"></i></a></li>
                        @endif
                        @if(!empty($socials['tiktok']))
                        <li><a href="{{ $socials['tiktok'] }}" target="_blank"><i class="fab fa-tiktok"></i></a></li>
                        @endif
                        @if(!empty($socials['youtube']))
                        <li><a href="{{ $socials['youtube'] }}" target="_blank"><i class="fab fa-youtube"></i></a></li>
                        @endif
                        @if(!empty($socials['facebook']))
                        <li><a href="{{ $socials['facebook'] }}" target="_blank"><i class="fab fa-facebook-f"></i></a></li>
                        @endif
                    </ul>
                </div>
                <div class="zubuz-copywright">
                    <p>&copy; {{ date('Y') }} {{ get_setting('site_title', 'BARIS APP') }}. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="{{ asset('templates/zubaz/assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/menu/menu.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/slick.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/faq.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/app.js') }}"></script>

    <script>
        // Initialize WOW animations
        new WOW().init();
    </script>

    @livewireScripts
</body>

</html>
