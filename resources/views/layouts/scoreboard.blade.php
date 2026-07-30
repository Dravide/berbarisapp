<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/png" href="{{ asset('templates/assets/images/logos/favicon.png') }}" />
    <meta name="description" content="Live scoreboard - {{ $eventner?->nama_event ?? 'BARIS APP' }}. Pantau skor dan peringkat peserta secara real-time.">
    <meta name="keywords" content="live scoreboard, skor langsung, {{ $eventner?->nama_event ?? 'kompetisi' }}, papan skor, hasil lomba">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="noindex, follow">
    <title>{{ $eventner?->nama_event ?? 'BARIS APP' }} - Live Scoreboard</title>
    <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />
    @livewireStyles
</head>
<body>
    <div class="container-fluid py-4">
        {{ $slot }}
    </div>

    <script src="{{ asset('templates/assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @livewireScripts
</body>
</html>
