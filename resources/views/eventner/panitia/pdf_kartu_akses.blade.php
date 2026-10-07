@php
    // Kartu akses entry panitia — SATU lembar: link, QR, dan PIN.
    //
    // QR dirender sebagai data-URI di dalam view (pola sama dengan
    // pdf_kartu_akses juri) supaya dompdf tidak perlu membaca berkas dari disk.
    //
    // Masa berlaku ikut dicetak: halaman /panitia/{token} 404 setelah
    // judgeAccessExpiresAt() lewat, dan kartu yang beredar tanpa tanggal itu
    // membuat panitia mengira dirinya salah PIN padahal link-nya yang mati.
    $safeLogo = null;
    if ($eventner->logo_event) {
        $p = public_path('storage/' . $eventner->logo_event);
        if (file_exists($p) && is_file($p)) $safeLogo = $p;
    }

    $batas = $eventner->judgeAccessExpiresAt();

    // Nama event & penyelenggara panjang membuat kop tumbuh sampai 4 baris dan
    // kartu meluber ke lembar kedua — sisa ruang di lembar pertama cuma ~30pt.
    // Teksnya tidak dipotong (kartu resmi tidak boleh memotong nama event);
    // kopnya saja yang dirapatkan saat memang panjang.
    $kopPadat = mb_strlen((string) $eventner->nama_event) > 60
        || mb_strlen((string) $eventner->diselenggarakan_oleh) > 70;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Akses Panitia - {{ $eventner->nama_event }}</title>
    <style>
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-Regular.ttf') }}');
        }
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-SemiBold.ttf') }}');
            font-weight: bold;
        }
        @page { margin: 12mm 14mm; }
        body {
            font-family: 'PJ', sans-serif;
            font-size: 10px;
            color: #222;
            padding: 0;
            margin: 0;
        }

        /* KOP */
        .kop { border-bottom: 3px double #222; padding-bottom: 8px; margin-bottom: 10px; }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 60px; height: 60px; border-radius: 6px; border: 1px solid #ccc; }
        .kop-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .kop-sub { font-size: 10px; color: #666; }
        /* Nama panjang: kop dirapatkan supaya kartu tetap satu lembar. */
        .kop.padat { padding-bottom: 5px; margin-bottom: 7px; }
        .kop.padat .kop-logo { width: 44px; height: 44px; }
        .kop.padat .kop-title { font-size: 11px; line-height: 1.25; }
        .kop.padat .kop-sub { font-size: 8px; line-height: 1.3; }

        /* JUDUL */
        .judul { background: #1a1a2e; color: #fff; text-align: center; padding: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; }
        .subjudul { text-align: center; font-size: 9px; color: #888; margin-bottom: 8px; }

        /* QR */
        .qr-wrap { text-align: center; margin-bottom: 4px; }
        .qr-frame { display: inline-block; border: 3px solid #1a1a2e; border-radius: 10px; padding: 10px; background: #fff; }
        .qr-frame img { width: 160px; height: 160px; display: block; }
        .qr-scan { text-align: center; font-size: 10px; font-weight: bold; color: #1a1a2e; margin: 5px 0 0; }
        .qr-help { text-align: center; font-size: 9px; color: #888; margin: 3px 0 0; }
        .qr-gagal { border: 1px dashed #ddd; border-radius: 8px; padding: 30px 20px; color: #c0392b; font-size: 10px; text-align: center; }

        /* LINK */
        .section-title { background: #2c3e50; color: #fff; padding: 5px 12px; border-radius: 4px 4px 0 0; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; page-break-after: avoid; margin-top: 9px; }
        .box { border: 1px solid #ddd; border-top: none; padding: 7px 12px; }
        .lbl { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 3px; }
        .link { font-size: 9px; color: #1a1a2e; word-break: break-all; line-height: 1.4; }

        /* PIN */
        .pin-row { width: 100%; border-collapse: collapse; }
        .pin-row td { border: 1px solid #ddd; padding: 6px 12px; vertical-align: middle; }
        .pin-box { text-align: center; }
        .pin-angka { font-size: 26px; font-weight: bold; color: #1a1a2e; letter-spacing: 8px; margin: 0; }
        .pin-catatan { font-size: 8px; color: #888; margin: 4px 0 0; }

        /* LANGKAH */
        ol.langkah { margin: 0; padding: 6px 12px 6px 26px; border: 1px solid #ddd; border-top: none; }
        ol.langkah li { margin-bottom: 3px; font-size: 9px; line-height: 1.4; }
        ol.langkah li:last-child { margin-bottom: 0; }
        ol.langkah strong { color: #1a1a2e; }

        /* PERINGATAN */
        .awas { margin-top: 6px; border: 1px solid #f5c6cb; background: #fdecea; border-radius: 4px; padding: 6px 12px; page-break-inside: avoid; }
        .awas .t { font-weight: bold; color: #c0392b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .awas p { margin: 0; font-size: 9px; color: #7b241c; line-height: 1.5; }

        /* FOOTER */
        .foot { margin-top: 7px; padding-top: 5px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }
    </style>
</head>
<body>

<div class="kop{{ $kopPadat ? ' padat' : '' }}">
    <table>
        <tr>
            <td style="width: 70px;">
                @if($safeLogo)
                    <img src="{{ $safeLogo }}" class="kop-logo">
                @endif
            </td>
            <td style="padding-left: 12px;">
                <div class="kop-title">{{ $eventner->nama_event }}</div>
                <div class="kop-sub">
                    {{ $eventner->diselenggarakan_oleh }}
                    @if($eventner->tanggal)
                        &middot; {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}
                    @endif
                    @php
                        $kartuVenues = $eventner->activeVenues()->pluck('name');
                        $kartuVenueText = $kartuVenues->isNotEmpty() ? $kartuVenues->implode(' / ') : $eventner->venue;
                    @endphp
                    @if($kartuVenueText) &middot; {{ $kartuVenueText }} @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="judul">Kartu Akses Entry Nilai Panitia</div>
<div class="subjudul">
    Untuk petugas meja sekretariat &bull; <strong>bukan kartu juri</strong> &bull;
    Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
</div>

<div class="qr-wrap" style="margin-top: 12px;">
    @if($qrImage)
        <div class="qr-frame">
            <img src="{{ $qrImage }}" alt="QR entry panitia">
        </div>
    @else
        <div class="qr-gagal">
            QR gagal dibuat. Hubungi pemilik event untuk mencetak ulang kartu ini.
        </div>
    @endif
</div>
@if($qrImage)
    <p class="qr-scan">Pindai QR ini untuk membuka lembar input nilai</p>
    <p class="qr-help">Kamera HP/laptop &rarr; arahkan ke QR &rarr; layar PIN akan muncul</p>
@endif

<div class="section-title">Link Entry</div>
<div class="box">
    <div class="lbl">Alamat halaman</div>
    <div class="link">{{ $url }}</div>
</div>

<div class="section-title">PIN Entry</div>
<table class="pin-row">
    <tr>
        <td class="pin-box">
            <p class="pin-angka">{{ $eventner->panitia_pin }}</p>
            <p class="pin-catatan">Diminta sekali, lalu diingat selama sesi browser</p>
        </td>
        <td style="width: 210px; font-size: 9px; color: #666; line-height: 1.5;">
            PIN ini milik <strong>event</strong>, bukan milik satu orang &mdash; satu PIN untuk semua
            petugas input. Tidak ikut tercetak di QR, jadi QR saja tidak cukup
            untuk masuk.
        </td>
    </tr>
</table>

<div class="section-title">Cara Membuka</div>
<ol class="langkah">
    <li>
        <strong>Pindai QR di atas</strong> atau buka link-nya di laptop petugas.
        Halaman <em>Entry Nilai Panitia</em> akan terbuka.
    </li>
    <li>
        <strong>Ketik PIN {{ $eventner->panitia_pin }}.</strong>
        Diminta sekali per sesi browser; sesudah itu halaman langsung terbuka.
    </li>
    <li>
        <strong>Pilih tingkat lomba &rarr; peserta &rarr; nama juri</strong> yang lembarnya sedang
        Anda ketik. Nilai dicatat atas nama juri itu &mdash; pastikan namanya cocok dengan
        lembar di tangan Anda.
    </li>
    <li>
        <strong>Ketuk tombol nilai; nilai langsung tersimpan.</strong>
        Koneksi putus di tengah pengisian? Buka lagi halamannya &mdash; nilai yang sudah
        masuk tidak hilang.
    </li>
    @if($batas)
        <li>
            <strong>Halaman menolak dibuka?</strong> Periksa PIN dulu. Link ini juga berhenti
            berlaku <strong>{{ $batas->translatedFormat('d F Y') }}</strong> (akhir tanggal event) &mdash;
            sesudah itu minta pemilik event memperbarui tanggal event atau membuat akses baru.
        </li>
    @endif
</ol>

<div class="awas">
    <div class="t">Jangan ditempel di tempat umum</div>
    <p>
        Kartu ini memuat link ber-token dan PIN sekaligus &mdash; siapa pun yang memotretnya bisa
        mengetik nilai atas nama juri mana pun. Bagikan hanya ke petugas yang mengetik. Bila
        terlanjur beredar, minta pemilik event menekan <em>Ganti Link &amp; PIN</em> di halaman
        Daftar Juri; kartu lama langsung tidak berlaku.
    </p>
</div>

<div class="foot">
    {{ $eventner->nama_event }} &mdash; Kartu Akses Entry Panitia &mdash;
    Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by {{ app_name() }}
</div>

</body>
</html>
