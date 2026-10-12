<?php

/** @var \App\Models\Eventner $eventner */

// JSON-LD Event schema untuk halaman detail event. Dibangun sebagai array PHP
// lalu json_encode sekali. Dipanggil hanya dari layouts/frontend.blade.php
// dengan gerbang route (event.detail & subdomain.detail), supaya halaman
// ber-token (juri, panitia, scan, scoreboard, magic link) yang ikut memakai
// layout ini tidak ikut meng-emit schema Event yang keliru.
// Hati-hati: tulis komentar tanpa kata berawalan @ — Blade meng-compile
// directive di dalam komentar PHP sebelum file dieksekusi.
$_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $eventner->nama_event,
    'description' => \Illuminate\Support\Str::limit(strip_tags($eventner->deskripsi ?? ''), 400),
];

if ($eventner->poster || $eventner->logo_event) {
    $_ld['image'] = asset('storage/' . ($eventner->poster ?: $eventner->logo_event));
}

// tanggal/tanggal_akhir string Y-m-d (tidak di-cast) — parse manual.
if ($eventner->tanggal) {
    $_ld['startDate'] = \Carbon\Carbon::parse($eventner->tanggal)->toIso8601String();
    $_ld['endDate'] = $eventner->tanggal_akhir
        ? \Carbon\Carbon::parse($eventner->tanggal_akhir)->toIso8601String()
        : $_ld['startDate'];
}

if ($eventner->lokasi) {
    $_loc = [
        '@type' => 'Place',
        'name' => $eventner->venue ?: $eventner->lokasi,
        'address' => $eventner->lokasi,
    ];
    if ($eventner->latitude && $eventner->longitude) {
        $_loc['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $eventner->latitude,
            'longitude' => $eventner->longitude,
        ];
    }
    $_ld['location'] = $_loc;
}

$_ld['organizer'] = [
    '@type' => 'Organization',
    'name' => $eventner->diselenggarakan_oleh ?: \App\Models\Setting::get('site_title', 'Berbaris App'),
];

$_ld['eventStatus'] = 'https://schema.org/EventScheduled';
$_ld['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

// Penjualan tiket → offers. eventStatus tetap EventScheduled selama event
// approved; pendaftaran yang tutup hanya mengubah availability (bukan
// status event), sesuai spesifikasi schema.org.
if ($eventner->ticket_active && $eventner->hasTicketPrice()) {
    $_ld['offers'] = [
        '@type' => 'Offer',
        'url' => event_url($eventner, 'ticket'),
        'price' => $eventner->startingTicketPrice() ?? $eventner->ticket_price,
        'priceCurrency' => 'IDR',
        'availability' => $eventner->registration_status === 'open'
            ? 'https://schema.org/InStock'
            : 'https://schema.org/SoldOut',
    ];
}

?>
<script type="application/ld+json">{!! json_encode($_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
