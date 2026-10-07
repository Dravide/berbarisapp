@php
    // Layout entry panitia: nol menu, nol sidebar, nol tautan keluar.
    // Petugas di meja sekretariat hanya boleh melihat satu hal — lembar nilai.
    //
    // Terpisah dari layouts/judge supaya perubahan di sini tidak pernah
    // menyentuh halaman tablet juri. Isinya sengaja sama-sama ramping.
    $eventner = $eventner ?? null;

    $themeConfig = $eventner?->theme_config ?? [];
    $primaryColor = $themeConfig['primary_color'] ?? '#0062ff';
    $accentColor = $themeConfig['accent_color'] ?? '#a3e635';
    $fontSans = $themeConfig['font_sans'] ?? 'Inter';
    $fontDisplay = $themeConfig['font_display'] ?? 'Plus Jakarta Sans';
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
    $displayWeight = $fontWeights[$fontDisplay] ?? 'wght@500;600;700;800';
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    {{-- Token event ada di URL. Tanpa ini browser mengirim URL penuh — termasuk
         token — lewat header Referer ke fonts.googleapis.com dan jsdelivr. --}}
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="{{ $primaryColor }}">

    <link rel="shortcut icon" type="image/png"
          href="{{ $eventner?->logo_event ? asset('storage/' . $eventner->logo_event) : asset('templates/zubaz/assets/images/favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $fontSans) }}:{{ $sansWeight }}&family={{ str_replace(' ', '+', $fontDisplay) }}:{{ $displayWeight }}&display=swap" rel="stylesheet">

    {{-- Versi di-pin persis: "@latest" membuat CDN bebas menyajikan versi apa pun. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.30.0/tabler-icons.min.css">

    <style>
        :root {
            --color-primary: {{ $primaryColor }};
            --color-secondary: {{ $accentColor }};
            --font-sans: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif;
            --font-display: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif;
        }
        button, a { -webkit-tap-highlight-color: transparent; }
        body, .font-sans { font-family: '{{ $fontSans }}', ui-sans-serif, system-ui, sans-serif !important; }
        .font-display, h1, h2, h3, h4, h5, h6, [class*="font-display"] {
            font-family: '{{ $fontDisplay }}', ui-sans-serif, system-ui, sans-serif !important;
        }
    </style>

    @vite(['resources/css/landing.css', 'resources/js/app.js'])

    <title>{{ $title ?? 'Entry Nilai Panitia' }}</title>

    @livewireStyles
    @stack('styles')
</head>

<body class="bg-surface text-on-surface font-sans antialiased min-h-screen flex flex-col">

    {{-- Header ringkas: identitas event + peran. Tanpa satu pun menu. --}}
    <header class="sticky top-0 z-40 border-b border-outline-variant/40 bg-white/95 backdrop-blur">
        <div class="container-landing flex h-14 items-center gap-3">
            @if($eventner?->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}"
                     class="h-8 w-8 shrink-0 rounded-lg border border-outline-variant/30 object-cover"
                     alt="{{ $eventner->nama_event }}">
            @else
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-outline-variant/30 bg-primary/10 text-primary">
                    <i class="ti ti-clipboard-text"></i>
                </span>
            @endif

            <span class="min-w-0 flex-1">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-primary leading-none">Entry Nilai Panitia</span>
                <span class="mt-0.5 block truncate font-display text-sm font-bold text-on-surface">{{ $eventner?->nama_event }}</span>
            </span>
        </div>
    </header>

    <main class="flex-1 w-full">
        {{ $slot }}
    </main>

    <footer class="border-t border-outline-variant/30 py-4">
        <div class="container-landing text-[11px] text-on-surface-variant">
            Nilai tersimpan otomatis · {{ app_name() }}
        </div>
    </footer>

    @livewireScripts
    @stack('scripts')

    {{-- SweetAlert2 dari CDN. Layout ini sudah memasang
         <meta name="referrer" content="no-referrer">, jadi permintaan ke CDN
         tidak mengirim URL halaman — yang berisi token event — di Referer. --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Pembaca event 'toast' dari trait MelaporKePengguna. Tanpa ini semua
        // pesan error komponen hilang tanpa jejak.
        //
        // wire:confirm sengaja TIDAK dibungkus SweetAlert seperti di layout lain:
        // confirm() bawaan browser sudah sinkron dan benar, sedangkan menimpa
        // window.confirm membuat aksi Livewire batal diam-diam.
        window.beToast = function(message, icon) {
            const tipe = icon || 'success';

            // SweetAlert2 datang dari CDN, dan meja sekretariat sering kehilangan
            // internet justru saat lomba berjalan. Dulu tanpa Swal fungsi ini
            // `return` diam-diam: PIN salah tak memunculkan apa pun, layar
            // tampak membeku, dan panitia menyimpulkan halamannya rusak.
            // Jadi sediakan penggantinya — satu baris teks di tepi atas.
            if (!window.Swal) {
                let el = document.getElementById('be-toast-fallback');
                if (!el) {
                    el = document.createElement('div');
                    el.id = 'be-toast-fallback';
                    el.style.cssText = 'position:fixed;top:12px;left:50%;transform:translateX(-50%);' +
                        'z-index:9999;max-width:90vw;padding:10px 16px;border-radius:12px;' +
                        'font-size:13px;font-weight:600;color:#fff;box-shadow:0 6px 20px rgba(0,0,0,.18);';
                    document.body.appendChild(el);
                }

                el.style.background = tipe === 'error' ? '#dc2626' : '#16a34a';
                el.textContent = message;
                el.style.display = 'block';

                clearTimeout(window.__beToastTimer);
                window.__beToastTimer = setTimeout(function() { el.style.display = 'none'; }, 4000);

                return;
            }

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: tipe,
                title: message,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
        };

        document.addEventListener('livewire:init', function () {
            Livewire.on('toast', function (event) {
                // Livewire v4 menyerahkan `event.detail` SUDAH dilepas: parameter
                // yang sampai ke sini adalah objek dispatchnya sendiri
                // ({message, type, url, label}). Bentuk `event.detail` yang lama
                // dipertahankan karena `$dispatch` dari Alpine memang memakai
                // CustomEvent ber-detail. Tanpa cabang pertama, pesannya jatuh
                // ke 'Berhasil.' — error tampil hijau "Berhasil.".
                const d = (event && event.detail) || event || {};
                const message = typeof d === 'string' ? d : (d.message || 'Berhasil.');
                const type = (typeof d === 'object' && d.type) || 'success';
                window.beToast(message, type);
            });
        });
    </script>
</body>

</html>
