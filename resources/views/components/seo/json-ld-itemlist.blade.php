<?php

/** @var \Illuminate\Pagination\LengthAwarePaginator $eventners */

// JSON-LD ItemList untuk halaman daftar penyelenggara (/eventner) — memberi
// Google struktur katalog: halaman ini berisi daftar event, masing-masing
// dengan URL dan namanya. Isinya mengikuti paginator halaman aktif.
$_items = $eventners->getCollection()->values()->map(function ($e, $i) {
    return [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'url' => $e->subdomain
            ? $e->publicUrl('detail')
            : platform_url('event/' . $e->slug),
        'name' => $e->nama_event,
    ];
})->all();

$_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => $_items,
];

?>
<script type="application/ld+json">{!! json_encode($_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
