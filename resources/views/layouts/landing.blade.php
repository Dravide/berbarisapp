<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? get_setting('site_title', 'Berbaris App') }} - Platform Manajemen Event & Kompetisi</title>
    <meta name="description" content="{{ get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu. Solusi lengkap untuk penyelenggara event, lomba, dan kompetisi di Indonesia.') }}">
    <meta name="keywords" content="{{ get_setting('meta_keywords', 'event, kompetisi, lomba, baris, pendaftaran, manajemen event, platform kompetisi, penyelenggara event') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ get_setting('site_title', 'Berbaris App') }}">
    <meta property="og:title" content="{{ $title ?? get_setting('site_title', 'Berbaris App') }}">
    <meta property="og:description" content="{{ get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu di Indonesia.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">
    @if(($logoPath ?? null) && is_string($logoPath))
    <meta property="og:image" content="{{ $logoPath }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? get_setting('site_title', 'Berbaris App') }}">
    <meta name="twitter:description" content="{{ Str::limit(get_setting('meta_description', 'Platform manajemen event dan kompetisi terpadu'), 200) }}">

    @isset($favicon)
        <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
        <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}" type="image/x-icon">
    @endisset

    {{-- Dynamic Theme + Fonts (from Admin Settings) — define BEFORE font link --}}
    @php
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
            'Outfit' => 'wght@400;500;600;700;800',
            'Onest' => 'wght@400;500;600;700;800',
            'Plus Jakarta Sans' => 'wght@400;500;600;700;800',
            'DM Serif Display' => 'wght@400',
            'Playfair Display' => 'wght@400;500;600;700;800',
            'Bebas Neue' => 'wght@400',
        ];
        $sansWeight = $fontWeights[$fontSans] ?? 'wght@400;500;600;700';
        $displayWeight = $fontWeights[$fontDisplay] ?? 'wght@600;700;800';
    @endphp

    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
        }
    </style>

    {{-- Fonts (Dynamic) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontSans) }}:{{ $sansWeight }}&family={{ str_replace(' ', '+', $fontDisplay) }}:{{ $displayWeight }}&display=swap" rel="stylesheet">

    {{-- Tabler Icons (konsisten dgn app) --}}
    <link rel="stylesheet" href="{{ asset('templates/assets/fonts/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        .ti-rosette-discount-check:before { content: "\f1f8"; }
    </style>

    {{-- Tailwind v4 build (landing tokens) --}}
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

    @livewireStyles
</head>

<body class="min-h-screen bg-surface text-on-surface">

    {{-- Navbar (Glassmorphism) --}}
    <header class="glass-nav sticky top-0 z-50">
        <div class="container-landing">
            <div class="flex h-16 items-center justify-between md:h-20">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    @if(($logoPath ?? null) && is_string($logoPath))
                        <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'Berbaris App') }}" class="h-9 w-auto md:h-10" style="max-height: 40px; object-fit: contain;">
                    @else
                        <span class="font-display text-lg font-extrabold tracking-tight text-deep-slate">
                            {{ get_setting('site_title', 'Berbaris App') }}
                        </span>
                    @endif
                </a>

                {{-- Desktop nav — dynamic based on active sections --}}
                @php
                    $sectionsActive = $sectionsActive ?? [];
                    $sectionsRender = $sectionsRender ?? [];
                    // Hanya section yang benar-benar dirender. Dulu di sini ada
                    // 'gallery': tautan yang tidak menuju apa pun karena
                    // komponennya berhenti render begitu datanya kosong.
                    // Saklar di pengaturan tidak cukup untuk tahu itu — section
                    // yang menyala tapi datanya kosong tetap tidak menghasilkan
                    // markup, jadi `$sectionsRender` (dihitung di komponen dari
                    // hasil query yang sama) yang menentukan. 'vote' kembali
                    // dengan gerbang yang sama: tampil hanya kalau ada event
                    // yang membuka voting.
                    //
                    // Kolom ketiga = section yang menaungi anchor itu. 'contact'
                    // sekarang kartu di dalam #faq, jadi dia ikut hidup-matinya
                    // FAQ, bukan punya kunci sendiri.
                    //
                    // Urutannya TIDAK ditulis di sini lagi. Daftarnya turun dari
                    // `LandingSections::navItems()`, yang dibangun dari urutan
                    // `DIKENAL` yang sama dengan urutan render section — jadi
                    // nav tidak mungkin lagi menggulir melawan alur baca.
                    // Sebelumnya array ini disalin tangan dan memang berselisih:
                    // nav menaruh Tim sebelum FAQ, halamannya sebaliknya.
                    //
                    // Kolom ketiga = section yang menaungi anchor itu. 'contact'
                    // sekarang kartu di dalam #faq, jadi dia ikut hidup-matinya
                    // FAQ, bukan punya kunci sendiri.
                    $navItems = \App\Support\LandingSections::navItems($sectionsOrder ?? null);
                    $navTampil = fn (string $gate) => ($sectionsActive[$gate] ?? true) && ($sectionsRender[$gate] ?? true);

                    // Halaman ini benar-benar punya section ber-anchor. Hanya
                    // LandingPage yang mengirim `sectionsActive`/`sectionsRender`
                    // (dan `diLamanDepan`); /pricing, /fitur, /help, /privacy,
                    // /terms, dan /eventner memakai layout yang sama tapi tidak
                    // punya #hero/#features/… sama sekali. Sebelum ini default
                    // `[]` membuat semua gerbang lolos `?? true` di atas dan
                    // sembilan anchor mati dirender di halaman-halaman itu —
                    // termasuk enam di footer.
                    //
                    // Default `false` (bukan `true`) justru yang aman: halaman
                    // yang lupa meneruskan flag ini mendapat tautan berprefiks
                    // absolute yang tetap bekerja — mendarat di beranda pada
                    // section yang dituju — bukan anchor mati yang diam-diam
                    // tidak melakukan apa pun. LandingPage meneruskan `true`
                    // eksplisit, jadi laman depan tidak berubah satu byte pun.
                    //
                    // Sengaja TIDAK disimpulkan dari `empty($sectionsRender)`:
                    // sentinel "kosong" itu rapuh — landing yang seluruh
                    // section-nya mati akan ikut dianggap bukan laman depan.
                    $diLamanDepan = $diLamanDepan ?? false;
                    $prefiksNav = $diLamanDepan ? '' : url('/');

                    // Halaman jasa Event Organizer hidup di host sendiri
                    // (eo.berbaris.app), jadi tautannya absolut dan TIDAK ikut
                    // daftar anchor di atas: nav anchor digerakkan scroll-spy
                    // yang mencari elemen `#id` di halaman ini, dan tautan lintas
                    // host tidak punya elemen seperti itu. Ditaruh terpisah di
                    // sisi kanan navbar supaya tetap terbaca sebagai "keluar
                    // dari halaman ini", bukan section lain di laman depan.
                    $tautanEo = eo_host() ? eo_url('/') : null;
                @endphp
                <nav class="hidden items-center gap-8 lg:flex" id="landing-desktop-nav">
                    @foreach($navItems as [$id, $label, $gate])
                        @if($id === 'hero' || $navTampil($gate))
                            <a href="{{ $prefiksNav }}#{{ $id }}" data-nav="{{ $id }}"
                               class="text-sm font-medium transition-colors duration-200 text-on-surface-variant hover:text-primary">
                                {{ $label }}
                            </a>
                        @endif
                    @endforeach
                </nav>

                {{-- Desktop CTAs --}}
                <div class="hidden items-center gap-3 lg:flex">
                    @if($tautanEo)
                        <a href="{{ $tautanEo }}" class="text-sm font-medium text-on-surface-variant transition-colors duration-200 hover:text-primary">
                            Jasa Event Organizer
                        </a>
                    @endif
                    <a href="{{ route('login') }}" class="btn-ghost">Login</a>
                    <a href="{{ route('register.eventner') }}" class="btn-primary">
                        Daftar Eventner
                    </a>
                </div>

                {{-- Mobile hamburger --}}
                <button type="button" id="landing-menu-toggle" class="inline-flex h-10 w-10 items-center justify-center rounded-md text-deep-slate transition hover:bg-surface-container lg:hidden" aria-label="Buka menu">
                    <i class="ti ti-menu-2 text-2xl"></i>
                </button>
            </div>
        </div>

        {{-- Mobile menu --}}
        <div id="landing-mobile-menu" class="hidden border-t border-outline-variant/50 bg-white/95 backdrop-blur-xl lg:hidden">
            <nav class="container-landing flex flex-col gap-1 py-4">
                @foreach($navItems as [$id, $label, $gate])
                    @if($id === 'hero' || $navTampil($gate))
                        <a href="{{ $prefiksNav }}#{{ $id }}" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">{{ $label }}</a>
                    @endif
                @endforeach
                <div class="mt-2 flex flex-col gap-2 border-t border-outline-variant/40 pt-3">
                    @if($tautanEo)
                        <a href="{{ $tautanEo }}" class="rounded-md px-3 py-2.5 text-sm font-medium text-on-surface-variant transition hover:bg-surface-container hover:text-primary">Jasa Event Organizer</a>
                    @endif
                    <a href="{{ route('register.eventner') }}" class="btn-primary w-full">Daftar Eventner</a>
                    <a href="{{ route('login') }}" class="btn-ghost w-full">Login</a>
                </div>
            </nav>
        </div>
    </header>

    {{-- Main --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer (Deep Slate) --}}
    <footer class="bg-deep-slate text-white/70">
        <div class="container-landing py-16">
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                {{-- Brand --}}
                <div class="lg:col-span-1">
                    <a href="{{ url('/') }}" class="mb-4 flex items-center gap-2">
                        @if(($logoPath ?? null) && is_string($logoPath))
                            <img src="{{ $logoPath }}" alt="{{ get_setting('site_title', 'Berbaris App') }}" class="h-9 w-auto" style="max-height: 40px; object-fit: contain;">
                        @else
                            <span class="font-display text-lg font-extrabold tracking-tight text-white">
                                {{ get_setting('site_title', 'Berbaris App') }}
                            </span>
                        @endif
                    </a>
                    <p class="text-sm leading-relaxed">
                        {{ get_setting('site_title', 'Berbaris App') }} — Platform manajemen event dan kompetisi terpadu untuk penyelenggara dan peserta.
                    </p>
                </div>

                {{-- Navigasi --}}
                <div>
                    <h4 class="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-white">Navigasi</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ $prefiksNav }}#hero" class="transition hover:text-secondary">Beranda</a></li>
                        <li><a href="{{ $prefiksNav }}#eventners" class="transition hover:text-secondary">Event</a></li>
                        {{-- Vote digerbangi `sectionsRender`, bukan sekadar
                             saklar: section-nya cuma render kalau ada event
                             yang membuka voting, dan footer ini juga dipakai
                             halaman lain yang tidak punya anchor sama sekali
                             ($sectionsRender kosong di sana). --}}
                        @if(($sectionsActive['vote'] ?? true) && ($sectionsRender['vote'] ?? false))
                        <li><a href="{{ $prefiksNav }}#vote" class="transition hover:text-secondary">Vote</a></li>
                        @endif
                        <li><a href="{{ $prefiksNav }}#features" class="transition hover:text-secondary">Fitur</a></li>
                        <li><a href="{{ route('public.features') }}" class="transition hover:text-secondary">Semua Fitur</a></li>
                        <li><a href="{{ $prefiksNav }}#pricing" class="transition hover:text-secondary">Harga</a></li>
                        {{-- FAQ sebelum Tim, sama seperti urutan section di
                             `LandingSections` — footer ini juga tautan gulir,
                             jadi ia tunduk pada urutan baca yang sama. --}}
                        <li><a href="{{ $prefiksNav }}#faq" class="transition hover:text-secondary">FAQ</a></li>
                        <li><a href="{{ $prefiksNav }}#team" class="transition hover:text-secondary">Tim</a></li>
                    </ul>
                </div>

                {{-- Legal + sosial. Dulu ada kolom "Layanan" yang isinya lima
                     tautan yang semuanya menunjuk #features — daftar fitur yang
                     sudah tampil utuh beberapa layar di atasnya. --}}
                <div>
                    <h4 class="mb-4 font-display text-sm font-semibold uppercase tracking-wider text-white">Kontak</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ $prefiksNav }}#contact" class="transition hover:text-secondary">Hubungi Kami</a></li>
                        <li><a href="{{ route('privacy') }}" class="transition hover:text-secondary">Kebijakan Privasi</a></li>
                        <li><a href="{{ route('terms') }}" class="transition hover:text-secondary">Syarat &amp; Ketentuan</a></li>
                        <li><a href="{{ route('help') }}" class="transition hover:text-secondary">Bantuan &amp; Support</a></li>
                        <li><a href="{{ route('public.eventners') }}" class="transition hover:text-secondary">Penyelenggara Event</a></li>
                        @if($tautanEo)
                            {{-- Lintas host: jasa penyelenggaraan lomba oleh tim
                                 EO. Ditaruh di kolom ini, bukan kolom Navigasi —
                                 kolom Navigasi isinya anchor gulir halaman ini. --}}
                            <li><a href="{{ $tautanEo }}" class="transition hover:text-secondary">Jasa Event Organizer</a></li>
                        @endif
                    </ul>
                    @php $socials = json_decode(get_setting('landing_social_links', '{}'), true); @endphp
                    @if(!empty($socials))
                    <div class="mt-4 flex items-center gap-3">
                        @if(!empty($socials['instagram']))<a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-instagram"></i></a>@endif
                        @if(!empty($socials['tiktok']))<a href="{{ $socials['tiktok'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-tiktok"></i></a>@endif
                        @if(!empty($socials['youtube']))<a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-youtube"></i></a>@endif
                        @if(!empty($socials['facebook']))<a href="{{ $socials['facebook'] }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 transition hover:bg-secondary hover:text-deep-slate"><i class="ti ti-brand-facebook"></i></a>@endif
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs sm:flex-row">
                <p>&copy; {{ date('Y') }} {{ get_setting('site_title', 'Berbaris App') }}. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    @livewireScripts

    {{-- Tombol WhatsApp mengapung — kanan bawah, semua halaman ber-layout landing.
         Nomornya satu-satunya dari setting kontak; tanpa isian, tombolnya tidak
         dirender (tidak ada tombol yang menaut ke "wa.me/"). --}}
    @php $waFloatUrl = wa_me_url(get_setting('landing_contact') ? (json_decode(get_setting('landing_contact'), true)['phone'] ?? '') : ''); @endphp
    @if($waFloatUrl)
    <a href="{{ $waFloatUrl }}" target="_blank" rel="noopener"
       aria-label="Hubungi kami via WhatsApp"
       class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_4px_16px_rgba(17,24,39,0.25)] transition-transform duration-200 hover:scale-105">
        <i class="ti ti-brand-whatsapp text-3xl"></i>
    </a>
    @endif

    {{-- Mobile menu toggle + scroll spy (vanilla JS) --}}
    <script>
        (function () {
            var btn = document.getElementById('landing-menu-toggle');
            var menu = document.getElementById('landing-mobile-menu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function () {
                menu.classList.toggle('hidden');
                var icon = btn.querySelector('i');
                if (icon) {
                    var open = !menu.classList.contains('hidden');
                    icon.className = open ? 'ti ti-x text-2xl' : 'ti ti-menu-2 text-2xl';
                }
            });
            menu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    menu.classList.add('hidden');
                    var icon = btn.querySelector('i');
                    if (icon) icon.className = 'ti ti-menu-2 text-2xl';
                });
            });
        })();

        // Scroll spy — highlight active nav link
        (function () {
            var navLinks = document.querySelectorAll('#landing-desktop-nav a[data-nav]');
            var sections = [];
            navLinks.forEach(function (a) {
                var el = document.getElementById(a.getAttribute('data-nav'));
                if (el) sections.push({ id: a.getAttribute('data-nav'), el: el, link: a });
            });
            if (!sections.length) return;

            function update() {
                var scrollY = window.scrollY + 100;
                var active = sections[0].id;
                for (var i = 0; i < sections.length; i++) {
                    if (scrollY >= sections[i].el.offsetTop) {
                        active = sections[i].id;
                    }
                }
                navLinks.forEach(function (a) {
                    if (a.getAttribute('data-nav') === active) {
                        a.classList.add('text-primary');
                        a.classList.remove('text-on-surface-variant');
                    } else {
                        a.classList.remove('text-primary');
                        a.classList.add('text-on-surface-variant');
                    }
                });
            }

            window.addEventListener('scroll', update, { passive: true });
            update();
        })();
    </script>
</body>

</html>
