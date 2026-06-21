<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>{{ $title ?? 'Overlay - BARIS APP' }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/landing.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { width: 1920px; height: 1080px; overflow: hidden; }
        body { width: 1920px; height: 1080px; overflow: hidden; }
    </style>

    @livewireStyles
</head>
<body class="bg-surface text-on-surface font-sans antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
