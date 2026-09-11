<!DOCTYPE html>
<html lang="id" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
@php
    // Data layout dikirim lewat ->layoutData() di masing-masing komponen, bukan
    // properti publik (Livewire tidak mewariskan properti ke view layout).
    // Drawing\Spin dan Drawing\Results hanya mengirim $eventner, jadi sisanya
    // diberi nilai default agar layout aman untuk keempat pemakainya.
    $eventner = $eventner ?? null;
    $categories = $categories ?? [];
    $selectedCategoryId = $selectedCategoryId ?? null;
    $championCategory = $championCategory ?? null;
    $eventTitle = $eventner?->nama_event ?? get_setting('site_title', 'Berbaris App');
    $organizer = $eventner?->diselenggarakan_oleh ?: get_setting('site_title', 'Berbaris App');

    // Konteks halaman: kategori juara (mode champion) atau kategori lomba.
    // data_get dipakai karena $categories bisa berupa array (Champions\Index).
    $contextName = $championCategory?->name
        ?? data_get(collect($categories)->firstWhere('id', $selectedCategoryId), 'name');

    $pageTitle = $contextName
        ? "Live Scoreboard {$contextName} — {$eventTitle}"
        : "Live Scoreboard — {$eventTitle}";

    $metaDescription = $contextName
        ? "Papan skor langsung kategori {$contextName} pada {$eventTitle}. Pantau peringkat, total nilai, dan hasil penilaian juri secara real-time."
        : "Papan skor langsung {$eventTitle}. Pantau peringkat dan total nilai peserta secara real-time.";

    $metaKeywords = implode(', ', array_filter([
        'live scoreboard',
        'papan skor langsung',
        $contextName,
        $eventTitle,
        $eventner?->lokasi,
        $eventner?->tingkat_perlombaan,
        'hasil lomba',
        'klasemen',
    ]));

    $ogImage = $eventner?->poster
        ? asset('storage/' . $eventner->poster)
        : ($eventner?->logo_event
            ? asset('storage/' . $eventner->logo_event)
            : asset('templates/assets/images/logos/favicon.png'));

    // Favicon: logo event lebih relevan daripada logo aplikasi.
    $favicon = $eventner?->logo_event
        ? asset('storage/' . $eventner->logo_event)
        : (get_setting('favicon')
            ? Storage::url(get_setting('favicon'))
            : asset('templates/assets/images/logos/favicon.png'));

    $canonical = url()->current();
@endphp
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="shortcut icon" type="image/png" href="{{ $favicon }}" />

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ Str::limit($metaDescription, 160) }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Google AdSense --}}
    <meta name="google-adsense-account" content="ca-pub-5071798385516247">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ get_setting('site_title', 'Berbaris App') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ Str::limit($metaDescription, 200) }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">
    @if($eventner?->updated_at)
        <meta property="article:modified_time" content="{{ $eventner->updated_at->toIso8601String() }}">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ Str::limit($metaDescription, 200) }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- JSON-LD Structured Data — dibangun di PHP agar aman untuk kurung kurawal JSON --}}
    @php
        $_ld = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $pageTitle,
            'description' => Str::limit($metaDescription, 400),
            'url' => $canonical,
            'inLanguage' => 'id-ID',
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => get_setting('site_title', 'Berbaris App'),
                'url' => url('/'),
            ],
            'about' => [array_filter([
                '@type' => 'Event',
                'name' => $eventner?->nama_event,
                'startDate' => $eventner?->tanggal
                    ? \Carbon\Carbon::parse($eventner->tanggal)->toIso8601String()
                    : null,
                'location' => $eventner?->lokasi
                    ? ['@type' => 'Place', 'name' => $eventner->venue ?: $eventner->lokasi, 'address' => $eventner->lokasi]
                    : null,
                'organizer' => ['@type' => 'Organization', 'name' => $organizer],
            ])],
        ];
        $_jsonLd = $eventner
            ? json_encode($_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : null;
    @endphp
    @if($_jsonLd)
        <script type="application/ld+json">{!! $_jsonLd !!}</script>
    @endif

    <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />
    @livewireStyles
</head>
<body>
    <div class="container-fluid py-4">
        {{ $slot }}
    </div>

    <script src="{{ asset('templates/assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @livewireScripts
</body>
</html>
