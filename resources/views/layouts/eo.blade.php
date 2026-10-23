<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- `$title` sudah berisi nama halaman + nama EO (dipasang ->title() di
         komponen). Jangan menambahkan $eoNama lagi di sini: hasilnya jadi
         "Lingkup Kerja - Berbaris App - Berbaris App". --}}
    <title>{{ $title ?? ('Event Organizer ' . ($eoNama ?? get_setting('site_title', 'Berbaris App'))) }}</title>
    <meta name="description" content="{{ $deskripsi ?? 'Penyelenggara lomba baris-berbaris: dari poster dan pendaftaran, penilaian juri di lapangan, sampai rekap juara dan laporan ke sponsor.' }}">
    <link rel="canonical" href="{{ url()->current() }}">

    @isset($favicon)
        <link rel="shortcut icon" href="{{ $favicon }}" type="image/x-icon">
        <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
    @else
        <link rel="shortcut icon" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}" type="image/x-icon">
    @endisset

    {{-- Bahasa visual halaman ini mengikuti doc/eo-wireframe.html: IBM Plex,
         garis rambut 1px, label monospace, permukaan kertas. Sengaja TIDAK
         memuat landing.css: kelas landing (surface-card, chip, btn-primary)
         tidak ada di sini, dan memakainya akan menghasilkan elemen tanpa gaya.
         Palet tetap diturunkan dari setelan situs supaya dua subdomain terbaca
         satu keluarga; yang berbeda adalah cara menyusunnya. --}}
    @php
        $accentColor = get_setting('site_primary_color', '#0062ff');
    @endphp

    {{-- Warna merek diserahkan sebagai `--eo-accent`, bukan sebagai
         `--color-accent`. `eo.css` satu-satunya penulis `--color-accent`, dan
         ia memakai nilai ini lewat `var(--eo-accent, …)`. Dua penulis untuk
         satu token membuat nilai yang menang ditentukan urutan CSS, bukan
         niat; satu penulis membuatnya ditentukan niat. --}}
    <style>
        :root {
            --eo-accent: {{ $accentColor }};
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">

    @vite(['resources/css/eo.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen">

    {{-- Navbar. Tujuh tautan tetap, bukan anchor: semua halaman EO punya
         route-nya sendiri, jadi tidak ada tautan yang bisa mati karena
         section-nya kebetulan kosong (pelajaran nav laman utama). --}}
    @php
        $menuEo = [
            ['route' => 'eo.layanan', 'label' => 'Layanan'],
            ['route' => 'eo.alur', 'label' => 'Alur'],
            ['route' => 'eo.event', 'label' => 'Event'],
            ['route' => 'eo.tim', 'label' => 'Tim'],
            ['route' => 'eo.sponsor', 'label' => 'Sponsor'],
        ];
    @endphp

    <header class="eo-nav sticky top-0 z-50">
        <div class="eo-wrap">
            <div class="flex h-14 items-center justify-between gap-4 md:h-16">
                {{-- Brand: logo ATAU nama, tidak keduanya. Logo yang diunggah
                     sudah memuat nama, jadi menaruh teks di sebelahnya membuat
                     nama terbaca dua kali. Kalau belum ada logo, nama teks yang
                     berdiri sendiri, bukan kotak kosong sebagai pengganti,
                     karena kotak gelap tanpa isi terbaca sebagai gambar rusak.
                     Pola ini sama dengan header laman platform. --}}
                <a href="{{ route('eo.beranda') }}" class="flex items-center gap-2.5 no-underline">
                    @if(($logoPath ?? null) && is_string($logoPath))
                        <img src="{{ $logoPath }}" alt="{{ $eoNama }}" class="h-7 w-auto" style="object-fit: contain;">
                    @else
                        <span class="text-[13px] font-bold tracking-tight" style="color: var(--color-ink);">{{ $eoNama }}</span>
                    @endif
                    <span class="eo-chip hidden sm:inline-flex">EO</span>
                </a>

                <nav class="hidden items-center gap-6 lg:flex" aria-label="Halaman EO">
                    @foreach($menuEo as $item)
                        <a href="{{ route($item['route']) }}"
                           class="eo-nav-link"
                           @if(request()->routeIs($item['route'])) aria-current="page" @endif>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-2 lg:flex">
                    <a href="{{ platform_url('/') }}" class="eo-btn eo-btn-ghost">Platform</a>
                    <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary">Ajukan Event</a>
                </div>

                {{-- 44px: ini satu-satunya kendali navigasi di lebar ponsel, jadi
                     ia harus bisa ditekan dengan ibu jari tanpa membidik.
                     Tinggi minimum, bukan padding, karena padding 7px pada teks
                     14px hanya menghasilkan sekitar 34px. --}}
                <button type="button" id="eo-menu-toggle"
                        class="eo-btn eo-btn-ghost lg:hidden"
                        aria-label="Buka menu" aria-expanded="false" aria-controls="eo-mobile-menu"
                        style="min-height: 44px; padding: 0 14px;">
                    <span aria-hidden="true">Menu</span>
                </button>
            </div>
        </div>

        <div id="eo-mobile-menu" class="hidden border-t lg:hidden" style="border-color: var(--color-line); background: var(--color-paper);">
            <nav class="eo-wrap flex flex-col gap-1 py-3" aria-label="Halaman EO (ponsel)">
                @foreach($menuEo as $item)
                    {{-- 44px: tautan ini hanya muncul di ponsel, tempat ia
                         ditekan ibu jari. --}}
                    <a href="{{ route($item['route']) }}" class="eo-nav-link flex items-center py-2.5" style="min-height: 44px;" @if(request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <div class="mt-2 flex flex-col gap-2 border-t pt-3" style="border-color: var(--color-line);">
                    <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary w-full">Ajukan Event</a>
                    <a href="{{ platform_url('/') }}" class="eo-btn eo-btn-ghost w-full">Buka Platform</a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer style="border-top: 1px solid var(--color-line);">
        <div class="eo-wrap py-12">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 md:grid-cols-4">
                <div>
                    <p class="eo-kaki-h">{{ $eoNama }}</p>
                    <p class="eo-small eo-muted">{{ $eoRingkas }}</p>
                </div>

                <div>
                    <p class="eo-kaki-h">Halaman</p>
                    <ul class="space-y-1.5 text-[13.5px]">
                        <li><a href="{{ route('eo.layanan') }}" class="no-underline" style="color: var(--color-muted);">Lingkup Kerja</a></li>
                        <li><a href="{{ route('eo.alur') }}" class="no-underline" style="color: var(--color-muted);">Cara Kerja</a></li>
                        <li><a href="{{ route('eo.event') }}" class="no-underline" style="color: var(--color-muted);">Event &amp; Portofolio</a></li>
                    </ul>
                </div>

                <div>
                    <p class="eo-kaki-h">Tim</p>
                    <ul class="space-y-1.5 text-[13.5px]">
                        <li><a href="{{ route('eo.tim') }}" class="no-underline" style="color: var(--color-muted);">Susunan Tim</a></li>
                        <li><a href="{{ route('eo.sponsor') }}" class="no-underline" style="color: var(--color-muted);">Sponsor &amp; Mitra</a></li>
                        <li><a href="{{ platform_url('/') }}" class="no-underline" style="color: var(--color-muted);">Platform BARIS APP</a></li>
                    </ul>
                </div>

                <div>
                    <p class="eo-kaki-h">Kontak</p>
                    {{-- Kontak EO punya kunci setelan sendiri (eo_contact), BUKAN
                         landing_contact: kotak surat dan nomor tim EO berbeda dari
                         kontak platform, dan mewarisinya berarti halaman ini
                         menampilkan alamat contoh milik laman utama. --}}
                    @php
                        $kontakEo = json_decode(get_setting('eo_contact') ?: '{}', true) ?: [];
                    @endphp
                    <ul class="space-y-1.5 text-[13.5px]">
                        @if(!empty($kontakEo['email']))
                            <li class="select-all" style="color: var(--color-muted);">{{ $kontakEo['email'] }}</li>
                        @endif
                        @if(!empty($kontakEo['phone']))
                            <li class="select-all" style="color: var(--color-muted);">{{ $kontakEo['phone'] }}</li>
                        @endif
                        @if(!empty($kontakEo['instagram']))
                            <li><a href="{{ $kontakEo['instagram'] }}" target="_blank" rel="noopener" class="no-underline" style="color: var(--color-muted);">Instagram</a></li>
                        @endif
                        <li><a href="{{ route('eo.ajukan') }}" class="no-underline" style="color: var(--color-muted);">Ajukan Event</a></li>
                    </ul>
                    @if(empty($kontakEo['email']) && empty($kontakEo['phone']))
                        <p class="eo-mono mt-2" style="font-size: 11px;">Kontak langsung belum diisi.</p>
                    @endif
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-2 border-t pt-5 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--color-line);">
                <p class="eo-mono" style="font-size: 11px;">&copy; {{ date('Y') }} {{ $eoNama }}</p>
                <p class="eo-mono" style="font-size: 11px;">Diselenggarakan dengan {{ get_setting('site_title', 'Berbaris App') }}</p>
            </div>
        </div>
    </footer>

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Pembaca event 'toast' dari trait MelaporKePengguna. Formulir /ajukan
        // memakainya untuk mengabarkan pengajuan terkirim; tanpa blok ini
        // pesannya hilang tanpa jejak dan pengunjung hanya melihat formulir
        // yang mendadak kosong, terbaca sebagai isian yang gagal terkirim.
        window.beToast = function (message, icon) {
            var tipe = icon || 'success';

            // Swal datang dari CDN. Kalau gagal dimuat, satu baris teks di tepi
            // atas tetap memberi tahu hasilnya, lebih baik daripada diam.
            if (!window.Swal) {
                var el = document.getElementById('be-toast-fallback');
                if (!el) {
                    el = document.createElement('div');
                    el.id = 'be-toast-fallback';
                    el.style.cssText = 'position:fixed;top:12px;left:50%;transform:translateX(-50%);' +
                        'z-index:9999;max-width:90vw;padding:10px 16px;border-radius:8px;' +
                        'font-family:ui-monospace,monospace;font-size:12.5px;font-weight:500;color:#fff;' +
                        'box-shadow:0 6px 20px rgba(0,0,0,.18);';
                    document.body.appendChild(el);
                }

                el.style.background = tipe === 'error' ? '#c2410c' : '#14181f';
                el.textContent = message;
                el.style.display = 'block';

                clearTimeout(window.__beToastTimer);
                window.__beToastTimer = setTimeout(function () { el.style.display = 'none'; }, 5000);

                return;
            }

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: tipe,
                title: message,
                showConfirmButton: false,
                timer: 4500,
                timerProgressBar: true,
            });
        };

        document.addEventListener('livewire:init', function () {
            Livewire.on('toast', function (event) {
                // Livewire menyerahkan `event.detail` sudah dilepas, jadi yang
                // sampai adalah objek dispatchnya sendiri. Cabang `detail`
                // dipertahankan untuk `$dispatch` dari Alpine.
                var d = (event && event.detail) || event || {};
                var message = typeof d === 'string' ? d : (d.message || 'Berhasil.');
                var type = (typeof d === 'object' && d.type) || 'success';
                window.beToast(message, type);
            });
        });

        (function () {
            var btn = document.getElementById('eo-menu-toggle');
            var menu = document.getElementById('eo-mobile-menu');
            if (!btn || !menu) return;
            btn.addEventListener('click', function () {
                menu.classList.toggle('hidden');
                var open = !menu.classList.contains('hidden');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                btn.querySelector('span').textContent = open ? 'Tutup' : 'Menu';
            });
        })();
    </script>
</body>

</html>
