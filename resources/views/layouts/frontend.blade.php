<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Favicon --}}
    @isset($eventner?->logo_event)
        <link rel="shortcut icon" type="image/png" href="{{ asset('storage/' . $eventner->logo_event) }}">
    @else
        <link rel="shortcut icon" type="image/png" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}">
    @endisset

    <meta name="description" content="{{ $eventner?->nama_event ?? get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu') }}">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Raleway:wght@600;700&display=swap" rel="stylesheet">

    {{-- Zubaz CSS --}}
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/zubaz/assets/css/app.min.css') }}">

    {{-- Tabler Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <title>{{ $title ?? ($eventner?->nama_event ?? 'BARIS APP') }}</title>

    @livewireStyles
</head>

<body class="light">

    {{-- Preloader --}}
    <div class="zubuz-preloader-wrap">
        <div class="zubuz-preloader">
            <div></div><div></div><div></div><div></div>
        </div>
    </div>

    {{-- Header --}}
    <header class="site-header site-header--menu-center zubuz-header-section" id="sticky-menu">
        <div class="container">
            <nav class="navbar site-navbar">
                <div class="brand-logo">
                    <a href="{{ url('/') }}">
                        @isset($eventner?->logo_event)
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" style="max-height: 40px; object-fit: contain;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/logo/logo-dark.png') }}" alt="BARIS APP" class="light-version-logo">
                        @endisset
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
                            @isset($eventner?->slug)
                            <li class="nav-item">
                                <a href="{{ route('event.detail', $eventner->slug) }}" class="nav-link-item">Info Event</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('event.participant', $eventner->slug) }}" class="nav-link-item">Peserta</a>
                            </li>
                            @if($eventner->ticket_active && $eventner->ticket_price)
                            <li class="nav-item">
                                <a href="{{ route('event.ticket', $eventner->slug) }}" class="nav-link-item">Tiket</a>
                            </li>
                            @endif
                            <li class="nav-item">
                                <a href="{{ route('event.vote', $eventner->slug) }}" class="nav-link-item">Vote</a>
                            </li>
                            @endisset
                        </ul>
                    </nav>
                </div>

                <div class="header-btn header-btn-l1 ms-auto d-none d-xs-inline-flex">
                    <div class="zubuz-header-btn-wrap">
                        <a class="zubuz-login-btn" href="{{ url('/') }}">
                            <i class="fa fa-arrow-left"></i> Beranda
                        </a>
                    </div>
                    @isset($eventner?->slug)
                    <a class="zubuz-default-btn zubuz-header-btn" href="{{ route('event.register', $eventner->slug) }}">
                        <span>Daftar Sekarang</span>
                    </a>
                    @endisset
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
                                @isset($eventner?->logo_event)
                                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="" style="max-height: 40px; object-fit: contain;">
                                @else
                                    <img src="{{ asset('templates/zubaz/assets/images/logo/logo-dark.png') }}" alt="">
                                @endisset
                            </a>
                            <p>{{ $eventner?->nama_event ?? get_setting('site_title', 'BARIS APP') }} — {{ $eventner?->deskripsi ? Str::limit(strip_tags($eventner->deskripsi), 120) : 'Platform manajemen event dan kompetisi terpadu.' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <div class="zubuz-footer-menu extar-margin">
                            <div class="zubuz-footer-title"><p>Navigasi</p></div>
                            <ul>
                                @isset($eventner?->slug)
                                <li><a href="{{ route('event.detail', $eventner->slug) }}">Info Event</a></li>
                                <li><a href="{{ route('event.participant', $eventner->slug) }}">Daftar Peserta</a></li>
                                <li><a href="{{ route('event.vote', $eventner->slug) }}">Voting</a></li>
                                @if($eventner->ticket_active)
                                <li><a href="{{ route('event.ticket', $eventner->slug) }}">Beli Tiket</a></li>
                                @endif
                                @endisset
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4">
                        <div class="zubuz-footer-menu">
                            <div class="zubuz-footer-title"><p>Kontak</p></div>
                            <ul>
                                @if($eventner?->link_whatsapp)
                                <li><a href="{{ Str::startsWith($eventner->link_whatsapp, ['http://', 'https://']) ? $eventner->link_whatsapp : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank">WhatsApp</a></li>
                                @endif
                                @if($eventner?->link_instagram)
                                <li><a href="{{ $eventner->link_instagram }}" target="_blank">Instagram</a></li>
                                @endif
                                @if($eventner?->link_tiktok)
                                <li><a href="{{ $eventner->link_tiktok }}" target="_blank">TikTok</a></li>
                                @endif
                                @if($eventner?->link_livestreaming)
                                <li><a href="{{ $eventner->link_livestreaming }}" target="_blank">Live Stream</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-4">
                        <div class="zubuz-footer-menu extar-margin">
                            <div class="zubuz-footer-title"><p>Penyelenggara</p></div>
                            <ul>
                                <li><a href="#">{{ $eventner?->diselenggarakan_oleh ?? '-' }}</a></li>
                                @if($eventner?->lokasi)
                                <li><a href="#"><i class="fa fa-map-marker-alt"></i> {{ $eventner->lokasi }}</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="zubuz-footer-bottom">
                <div class="zubuz-social-icon order-md-2">
                    <ul>
                        @if($eventner?->link_instagram)
                        <li><a href="{{ $eventner->link_instagram }}" target="_blank"><i class="fab fa-instagram"></i></a></li>
                        @endif
                        @if($eventner?->link_tiktok)
                        <li><a href="{{ $eventner->link_tiktok }}" target="_blank"><i class="fab fa-tiktok"></i></a></li>
                        @endif
                        @if($eventner?->link_whatsapp)
                        <li><a href="{{ $eventner->link_whatsapp }}" target="_blank"><i class="fab fa-whatsapp"></i></a></li>
                        @endif
                    </ul>
                </div>
                <div class="zubuz-copywright">
                    <p>&copy; {{ date('Y') }} {{ $eventner?->diselenggarakan_oleh ?? get_setting('site_title', 'BARIS APP') }}. Powered by <a href="{{ url('/') }}" style="color: #0072FF;">BARIS APP</a></p>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script src="{{ asset('templates/zubaz/assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/menu/menu.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/wow.min.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/faq.js') }}"></script>
    <script src="{{ asset('templates/zubaz/assets/js/app.js') }}"></script>

    <script>
        new WOW().init();
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>
