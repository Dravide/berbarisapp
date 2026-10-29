@php
    $accentColor = get_setting('site_primary_color', '#0062ff');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Unduh E-Sertifikat' }} — {{ app_name() }}</title>
    <meta name="description" content="Masukkan NISN untuk melihat dan mengunduh e-sertifikat dari seluruh lomba baris-berbaris yang pernah Anda ikuti.">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="shortcut icon" href="{{ asset('templates/zubaz/assets/images/favicon.ico') }}" type="image/x-icon">

    {{-- Bahasa visualnya satu keluarga dengan halaman EO (IBM Plex, garis
         rambut 1px, permukaan kertas), tapi disusun jauh lebih rapat: satu
         kolom sempit, satu medan, satu daftar. Kelas landing TIDAK dimuat di
         sini — jangan memakai <x-landing.*>. --}}
    <style>
        :root {
            --es-accent: {{ $accentColor }};
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">

    @vite(['resources/css/esertifikat.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen flex flex-col">

    <header class="es-nav">
        <div class="es-wrap">
            <div class="flex h-14 items-center justify-between gap-4">
                <a href="{{ route('cert.home') }}" class="es-brand">
                    <span class="es-mark" aria-hidden="true">ES</span>
                    E-Sertifikat
                </a>

                {{-- Satu tautan keluar saja. Halaman ini bukan pintu masuk ke
                     platform: yang datang ke sini sedang mencari satu berkas,
                     dan menu yang panjang hanya menambah pilihan yang salah. --}}
                <a href="{{ route('help') }}" class="es-nav-link">Bantuan</a>
            </div>
        </div>
    </header>

    <main class="flex-1 py-6 sm:py-10">
        <div class="es-wrap">
            {{ $slot }}
        </div>
    </main>

    <footer class="es-kaki py-5">
        <div class="es-wrap flex flex-wrap items-center gap-x-3 gap-y-1">
            <span>Sertifikat diterbitkan penyelenggara lomba.</span>
            <span class="ml-auto font-mono">{{ app_name() }}</span>
        </div>
    </footer>

    @livewireScripts
</body>

</html>
