@php
    // Kartu akses juri — dicetak dua lembar:
    //   Lembar 1 : identitas juri + instruksi. Ditaruh DI ATAS lembar 2
    //              supaya QR tidak terlihat selama tablet tidak dipakai.
    //   Lembar 2 : QR akses tablet — kunci penilaian juri.
    //
    // QR dirender sebagai data-URI di dalam view (pola yang sama dengan
    // pdf_rubrik/pdf_recap) supaya dompdf tidak perlu membaca file dari disk.
    $safeLogo = null;
    if ($eventner->logo_event) {
        $p = public_path('storage/' . $eventner->logo_event);
        if (file_exists($p) && is_file($p)) $safeLogo = $p;
    }

    // Susun dulu data tiap juri — QR dirender sekali per juri, bukan di
    // tengah loop tampilan, supaya kegagalan render mudah ditangani.
    $cards = $judges->map(function ($judge) {
        $levelNames = $judge->assessmentCategories
            ->map(fn ($c) => $c->competitionCategory?->full_name)
            ->filter()
            ->unique()
            ->values();

        $url = judge_entry_url($judge->access_token);

        // QR harus PNG — dompdf membuang SVG diam-diam. Helper qr_data_uri
        // memaksa outputInterface (nama properti yang benar di v6).
        $qrImage = qr_data_uri($url, 12);

        return compact('judge', 'levelNames', 'url', 'qrImage');
    });
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Akses Juri - {{ $eventner->nama_event }}</title>
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
        .kop { border-bottom: 3px double #222; padding-bottom: 10px; margin-bottom: 14px; }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 60px; height: 60px; border-radius: 6px; border: 1px solid #ccc; }
        .kop-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .kop-sub { font-size: 10px; color: #666; }

        /* JUDUL */
        .judul { background: #1a1a2e; color: #fff; text-align: center; padding: 8px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; }
        .subjudul { text-align: center; font-size: 9px; color: #888; margin-bottom: 14px; }

        /* KARTU IDENTITAS */
        .identitas { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .identitas td { border: 1px solid #ddd; padding: 7px 10px; vertical-align: top; }
        .identitas .lbl { width: 130px; background: #f8f9fa; font-size: 8px; font-weight: bold; text-transform: uppercase; color: #888; letter-spacing: 0.5px; }
        .identitas .val { font-size: 11px; font-weight: bold; color: #1a1a2e; }
        .identitas .val .kecil { font-size: 9px; font-weight: normal; color: #666; }

        /* BLOK JUDUL BAGIAN */
        .section-title { background: #2c3e50; color: #fff; padding: 6px 12px; border-radius: 4px 4px 0 0; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; page-break-after: avoid; }

        /* LANGKAH PEMAKAIAN */
        ol.langkah { margin: 0; padding: 10px 12px 10px 28px; border: 1px solid #ddd; border-top: none; }
        ol.langkah li { margin-bottom: 7px; font-size: 10px; line-height: 1.5; }
        ol.langkah li:last-child { margin-bottom: 0; }
        ol.langkah strong { color: #1a1a2e; }

        /* PERINGATAN */
        .awas { margin-top: 14px; border: 1px solid #f5c6cb; background: #fdecea; border-radius: 4px; padding: 10px 12px; page-break-inside: avoid; }
        .awas .t { font-weight: bold; color: #c0392b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .awas p { margin: 0; font-size: 9px; color: #7b241c; line-height: 1.5; }

        /* CATATAN TENANG */
        .tenang { margin-top: 10px; border: 1px solid #bee5eb; background: #eef9fa; border-radius: 4px; padding: 10px 12px; page-break-inside: avoid; }
        .tenang .t { font-weight: bold; color: #0c7b8a; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .tenang p { margin: 0; font-size: 9px; color: #0b5f6b; line-height: 1.5; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* ===== HALAMAN 2 — QR ===== */
        .qr-page { text-align: center; padding-top: 10mm; }
        .qr-judge { font-size: 20px; font-weight: bold; color: #1a1a2e; margin: 0 0 2px; }
        .qr-event { font-size: 11px; color: #666; margin: 0 0 22px; }
        .qr-frame { display: inline-block; border: 3px solid #1a1a2e; border-radius: 10px; padding: 18px; background: #fff; }
        .qr-frame img { width: 340px; height: 340px; display: block; }
        .qr-scan { margin: 20px 0 0; font-size: 12px; font-weight: bold; color: #1a1a2e; }
        .qr-help { margin: 6px 0 0; font-size: 9px; color: #888; }
        .qr-url { margin-top: 26px; font-size: 8px; color: #aaa; word-break: break-all; }
        .qr-gagal { border: 1px dashed #ddd; border-radius: 8px; padding: 40px 20px; color: #c0392b; font-size: 10px; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@foreach($cards as $card)
    @php extract($card); @endphp

    {{-- ============ LEMBAR 1 — IDENTITAS & INSTRUKSI ============ --}}
    <div class="kop">
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
                        @if($eventner->venue) &middot; {{ $eventner->venue }} @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">Kartu Akses Penilaian Juri</div>
    <div class="subjudul">
        Lembar 1 dari 2 &mdash; pegangan juri &bull;
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    <table class="identitas">
        <tr>
            <td class="lbl">Nama Juri</td>
            <td class="val">{{ $judge->name }}</td>
        </tr>
        <tr>
            <td class="lbl">Event</td>
            <td class="val">
                {{ $eventner->nama_event }}
                <div class="kecil">{{ $eventner->diselenggarakan_oleh }}</div>
            </td>
        </tr>
        <tr>
            <td class="lbl">Tugas Penilaian</td>
            <td class="val">
                @forelse($judge->assessmentCategories as $cat)
                    {{ $cat->name }}@if(!$loop->last), @endif
                @empty
                    <span style="color:#c0392b;">Belum ada tugas — hubungi panitia.</span>
                @endforelse
            </td>
        </tr>
        <tr>
            <td class="lbl">Tingkat Lomba</td>
            <td class="val">
                @forelse($levelNames as $level)
                    {{ $level }}@if(!$loop->last), @endif
                @empty
                    <span style="color:#c0392b;">—</span>
                @endforelse
            </td>
        </tr>
    </table>

    <div class="section-title">Cara Memakai Kartu Ini</div>
    <ol class="langkah">
        <li>
            <strong>Simpan lembar ini di atas lembar QR.</strong>
            Lembar 2 halaman berikutnya memuat QR akses. Selama tablet tidak dipakai,
            taruh lembar ini menutupi QR supaya tidak difoto atau dipindai orang lain.
        </li>
        <li>
            <strong>Saat mau menilai, baru buka QR-nya.</strong>
            Pindahkan lembar ini, lalu pindai QR di lembar 2 memakai kamera tablet juri.
            Halaman penilaian akan terbuka.
        </li>
        <li>
            <strong>Simpan halaman itu sebagai bookmark di tablet.</strong>
            Satu tablet untuk satu juri. Setelah tersimpan, QR tidak perlu dipindai lagi —
            cukup buka bookmark, dan lembar ini bisa kembali menutupi QR.
        </li>
        <li>
            <strong>Isi nilai setiap peserta sesuai urutan tampil.</strong>
            Ketuk tombol nilai; nilai langsung tersimpan ke server. Setelah semua kriteria
            terisi, tekan <em>Finalisasi &amp; Lanjut</em> untuk mengunci nilai juri Anda.
        </li>
        <li>
            <strong>Kalau tablet mati, koneksi putus, atau ada yang tidak jalan</strong>
            hubungi panitia di meja juri. Nilai yang sudah tersimpan tidak hilang.
        </li>
    </ol>

    <div class="awas">
        <div class="t">Jangan bagikan QR ini</div>
        <p>
            QR di lembar 2 adalah kunci masuk. Siapa pun yang memindainya bisa mengisi nilai
            atas nama Anda, dan nilainya tercatat sebagai nilai juri Anda.
            Jangan difoto, jangan dikirim lewat chat, jangan ditinggal terbuka di meja.
            Bila QR ini terlanjur dilihat orang lain, segera lapor panitia &mdash;
            panitia bisa mencetak token baru dan QR lama otomatis tidak berlaku.
        </p>
    </div>

    <div class="tenang">
        <div class="t">Catatan</div>
        <p>
            Kartu ini milik juri yang namanya tercantum di atas dan tidak bisa dipindahkan
            ke orang lain. Bila nama pada lembar 2 bukan nama Anda, jangan dipakai &mdash;
            tukar dengan panitia.
        </p>
    </div>

    <div class="foot">
        {{ $eventner->nama_event }} &mdash; Kartu Akses Juri &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by {{ app_name() }}
    </div>

    <div class="page-break"></div>

    {{-- ============ LEMBAR 2 — QR ============ --}}
    <div class="qr-page">
        <p class="qr-judge">{{ $judge->name }}</p>
        <p class="qr-event">{{ $eventner->nama_event }}</p>

        @if($qrImage)
            <div class="qr-frame">
                <img src="{{ $qrImage }}" alt="QR akses juri">
            </div>
            <p class="qr-scan">Pindai QR ini untuk membuka halaman penilaian</p>
            <p class="qr-help">
                Kamera tablet &rarr; arahkan ke QR &rarr; simpan halaman yang terbuka sebagai bookmark
            </p>
        @else
            <div class="qr-gagal">
                QR gagal dibuat. Hubungi panitia untuk mencetak ulang kartu ini.
            </div>
        @endif

        <p class="qr-url">{{ $url }}</p>
    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
