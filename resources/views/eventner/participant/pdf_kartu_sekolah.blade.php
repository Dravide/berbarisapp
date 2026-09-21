@php
    // Kartu akses sekolah — satu halaman per sekolah, berisi magic link + QR.
    //
    // Berbeda dari kartu juri yang sengaja memisah QR ke lembar kedua (QR juri
    // adalah kunci penilaian), di sini QR dan penjelasannya dibuat SATU halaman:
    // yang menerima kartu ini adalah sekolah, bukan petugas, dan halaman yang
    // terpisah justru membuat potongannya mudah hilang.
    //
    // QR sudah dirender controller (`DataSekolah` + `qr_data_uri`) supaya
    // kegagalan render tercatat di satu tempat, bukan tersebar di dalam blade.
    $safeLogo = null;
    if ($eventner->logo_event) {
        $p = public_path('storage/' . $eventner->logo_event);
        if (file_exists($p) && is_file($p)) $safeLogo = $p;
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Akses Sekolah - {{ $eventner->nama_event }}</title>
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
        @page { size: A4 portrait; margin: 11mm 13mm; }
        body {
            font-family: 'PJ', sans-serif;
            font-size: 9.5px;
            color: #222;
            padding: 0;
            margin: 0;
        }

        /* KOP */
        .kop { border-bottom: 3px double #222; padding-bottom: 7px; margin-bottom: 10px; }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 46px; height: 46px; border-radius: 6px; border: 1px solid #ccc; }
        .kop-title { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .kop-sub { font-size: 8px; color: #666; }

        /* IDENTITAS SEKOLAH */
        .sekolah { text-align: center; margin-bottom: 12px; page-break-inside: avoid; }
        .sekolah .nama { font-size: 17px; font-weight: bold; color: #1a1a2e; margin: 0 0 3px; line-height: 1.25; }
        .sekolah .meta { font-size: 9px; color: #666; margin: 0; }

        /* KONTAK */
        table.kontak { width: 100%; border-collapse: collapse; margin-bottom: 12px; page-break-inside: avoid; }
        table.kontak td { border: 1px solid #e3e6ea; padding: 5px 8px; vertical-align: top; }
        table.kontak td.lbl { width: 108px; background: #f6f8fa; color: #55606b; font-size: 8.5px; }
        table.kontak td.val { font-weight: bold; }

        /* KOTAK MAGIC LINK */
        .link-box { border: 2px solid #1a1a2e; border-radius: 8px; padding: 12px; page-break-inside: avoid; }
        .link-box table { width: 100%; border: none; }
        .link-box td { border: none; vertical-align: middle; padding: 0; }
        .qr-cell { width: 178px; text-align: center; }
        .qr-cell img { width: 168px; height: 168px; display: block; margin: 0 auto; }
        .qr-gagal {
            width: 168px; height: 168px; margin: 0 auto;
            border: 1px dashed #ddd; border-radius: 6px;
            color: #c0392b; font-size: 8.5px; text-align: center; padding-top: 70px;
        }
        .link-info { padding-left: 14px; }
        .link-info .t { font-size: 11px; font-weight: bold; color: #1a1a2e; margin-bottom: 4px; }
        .link-info p { margin: 0 0 7px; font-size: 8.5px; color: #555; line-height: 1.5; }
        .url {
            border: 1px solid #d6dbe1; background: #f6f8fa; border-radius: 4px;
            padding: 6px 8px; font-size: 8px; color: #2c3e50; word-break: break-all;
        }
        .url-label { font-size: 7.5px; color: #8a939c; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }

        /* DAFTAR FUNGSI */
        .section-title {
            font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px;
            color: #1a1a2e; margin: 14px 0 7px; border-bottom: 1px solid #e3e6ea; padding-bottom: 3px;
        }
        ol.fungsi { margin: 0; padding-left: 16px; page-break-inside: avoid; }
        ol.fungsi li { font-size: 8.5px; color: #333; line-height: 1.55; margin-bottom: 5px; }
        ol.fungsi li strong { color: #1a1a2e; }

        /* RINGKASAN */
        .ringkas {
            margin-top: 12px; border: 1px solid #bee5eb; background: #eef9fa;
            border-radius: 5px; padding: 9px 11px; page-break-inside: avoid;
        }
        .ringkas .t { font-size: 8.5px; font-weight: bold; color: #0c7b8a; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; }
        .ringkas table { width: 100%; border: none; }
        .ringkas td { border: none; padding: 1px 0; font-size: 8.5px; color: #0b5f6b; }
        .ringkas td.k { width: 118px; }
        .ringkas td.v { font-weight: bold; }

        .catatan { margin-top: 10px; font-size: 8px; color: #888; line-height: 1.5; page-break-inside: avoid; }

        .foot { margin-top: 12px; padding-top: 5px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@foreach($sekolah as $s)
    <div class="kop">
        <table>
            <tr>
                <td style="width: 56px;">
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
                            $ksVenues = $eventner->activeVenues()->pluck('name');
                            $ksVenueText = $ksVenues->isNotEmpty() ? $ksVenues->implode(' / ') : $eventner->venue;
                        @endphp
                        @if($ksVenueText) &middot; {{ $ksVenueText }} @endif
                    </div>
                </td>
                <td style="text-align: right; width: 96px;">
                    <div style="font-size: 8.5px; font-weight: bold; color: #1a1a2e;">KARTU AKSES SEKOLAH</div>
                    <div style="font-size: 7px; color: #999;">Halaman {{ $loop->iteration }} dari {{ $loop->count }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- IDENTITAS SEKOLAH --}}
    <div class="sekolah">
        <p class="nama">{{ $s['nama_sekolah'] }}</p>
        <p class="meta">
            NPSN: {{ $s['npsn'] ?: '—' }}
            @if($s['kabupaten']) &middot; {{ $s['kabupaten'] }} @endif
        </p>
    </div>

    <table class="kontak">
        <tr>
            <td class="lbl">Nama Pelatih / Pembina</td>
            <td class="val">{{ $s['pelatih'] ?: '—' }}</td>
            <td class="lbl">No. HP / WhatsApp</td>
            <td class="val">{{ $s['no_hp'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Email Sekolah</td>
            <td class="val" colspan="3">{{ $s['email'] ?: '—' }}</td>
        </tr>
    </table>

    {{-- MAGIC LINK --}}
    <div class="link-box">
        <table>
            <tr>
                <td class="qr-cell">
                    @if($s['qr'])
                        <img src="{{ $s['qr'] }}" alt="QR tautan portal sekolah">
                    @else
                        <div class="qr-gagal">QR gagal dibuat.<br>Gunakan alamat di samping.</div>
                    @endif
                </td>
                <td class="link-info">
                    <div class="t">Tautan Portal Sekolah</div>
                    <p>
                        Pindai QR di samping, atau ketik alamat di bawah ini di peramban (browser).
                        <strong>Satu tautan ini berlaku untuk seluruh pasukan sekolah Anda</strong> di
                        event ini &mdash; bagikan ke pelatih dan pendamping, tidak perlu satu tautan per pasukan.
                    </p>
                    @if($s['url'])
                        <div class="url-label">Alamat portal</div>
                        <div class="url">{{ $s['url'] }}</div>
                    @else
                        <div class="url-label">Alamat portal</div>
                        <div class="url" style="color:#c0392b;">
                            Belum tersedia &mdash; hubungi panitia untuk menerbitkan ulang tautan sekolah ini.
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- FUNGSI PORTAL --}}
    <div class="section-title">Apa yang bisa dilakukan lewat tautan ini</div>
    <ol class="fungsi">
        <li>
            <strong>Melengkapi data sekolah dan pelatih.</strong>
            Termasuk kolom tambahan yang diminta panitia, seperti asal kabupaten/kota.
        </li>
        <li>
            <strong>Mendaftarkan anggota pasukan.</strong>
            Nama, NISN, foto anggota, sampai data danton &mdash; bisa ditambah dan diperbaiki sendiri
            tanpa menunggu panitia.
        </li>
        <li>
            <strong>Mengunggah berkas persyaratan.</strong>
            Logo sekolah, surat tugas, dan berkas lain yang diminta panitia diunggah langsung
            dari portal, jadi tidak perlu dikirim lewat chat.
        </li>
        <li>
            <strong>Mengirim bukti pembayaran.</strong>
            Unggah bukti transfer biaya pendaftaran supaya panitia bisa memverifikasi.
        </li>
        <li>
            <strong>Memantau status verifikasi.</strong>
            Lihat berkas mana yang sudah diterima dan mana yang masih perlu diperbaiki.
        </li>
        <li>
            <strong>Melihat nilai dan mengunduh sertifikat.</strong>
            Setelah penilaian selesai, rekap nilai dan sertifikat bisa diunduh dari portal yang sama.
        </li>
    </ol>

    {{-- RINGKASAN --}}
    <div class="ringkas">
        <div class="t">Ringkasan pendaftaran sekolah ini</div>
        <table>
            <tr>
                <td class="k">Jumlah pasukan terdaftar</td>
                <td class="v">{{ $s['jumlah_pasukan'] }} pasukan</td>
            </tr>
            <tr>
                <td class="k">Kategori lomba yang diikuti</td>
                <td class="v">{{ $s['jumlah_kategori'] }} kategori</td>
            </tr>
            <tr>
                <td class="k">Jumlah anggota terdata</td>
                <td class="v">{{ $s['jumlah_anggota'] }} orang</td>
            </tr>
            <tr>
                <td class="k">Status berkas</td>
                <td class="v">{{ $s['label_status'] }}</td>
            </tr>
        </table>
    </div>

    <div class="catatan">
        Simpan kartu ini &mdash; tautan di atas adalah satu-satunya jalan masuk ke portal sekolah Anda.
        Bila tautan tidak bisa dibuka, ada data yang perlu diubah, atau kartu ini hilang,
        hubungi panitia {{ $eventner->nama_event }}. Kartu ini dicetak
        {{ now()->translatedFormat('d F Y H:i') }} WIB.
    </div>

    <div class="foot">
        {{ $eventner->nama_event }} &mdash; Kartu Akses Sekolah &mdash; Generated by {{ app_name() }}
    </div>

    @unless($loop->last)
        <div class="page-break"></div>
    @endunless
@endforeach

</body>
</html>
