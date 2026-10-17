<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Denah {{ $denah->name }}</title>
    <style>
        /* Margin ditulis eksplisit: tinggi kanvas dihitung dari lebar sisanya. */
        @page { margin: 12mm 10mm; }

        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-Regular.ttf') }}');
        }
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-SemiBold.ttf') }}');
            font-weight: bold;
        }
        body {
            font-family: 'PJ', sans-serif;
            font-size: 10px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .kop {
            border-bottom: 3px double #222;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .kop-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #1a1a2e; }
        .kop-sub { font-size: 9px; color: #666; }
        .kop-kanan { text-align: right; font-size: 9px; color: #666; }

        .judul {
            background: #1a1a2e;
            color: #fff;
            text-align: center;
            padding: 5px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        /* Dompdf tidak mengenal aspect-ratio maupun clamp(), jadi kanvasnya
           diberi tinggi tetap. Posisi zona tetap persen; itulah yang membuat
           denah kertas ini sama isinya dengan denah di layar. */
        .denah-cetak .denah-kanvas {
            height: {{ $tinggiKanvas }};
            border-radius: 0;
        }
        .denah-cetak .denah-kanvas .zone {
            font-size: {{ $ukuranLabel }};
        }

        .keterangan {
            margin-top: 10px;
            font-size: 9px;
            color: #555;
        }
        .keterangan table { width: 100%; border-collapse: collapse; }
        .keterangan th {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 3px 5px;
            text-align: left;
            font-size: 9px;
        }
        .keterangan td { border: 1px solid #cbd5e1; padding: 3px 5px; }

        .ttd {
            margin-top: 18px;
            width: 100%;
            font-size: 9px;
        }
        .ttd td { width: 50%; text-align: center; padding-top: 34px; }
        .ttd .garis { border-top: 1px solid #222; margin: 0 28px; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="kop">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">
                        {{ $eventner->diselenggarakan_oleh ?: '' }}
                        @if($eventner->venue) &middot; {{ $eventner->venue }} @endif
                        @if($eventner->lokasi) &middot; {{ $eventner->lokasi }} @endif
                    </div>
                </td>
                <td class="kop-kanan" style="border: none;">
                    Dicetak {{ now()->translatedFormat('d F Y, H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">Denah Lapangan: {{ $denah->name }}</div>

    <div class="denah-cetak">
        @include('partials.denah-kanvas', [
            'layout' => $denah,
            'zones' => $denah->zones,
            'interaktif' => false,
            'kisi' => false,
        ])
    </div>

    <div class="keterangan">
        <table>
            <tr>
                <th style="width: 34%;">Tingkat Lomba</th>
                <td>{{ $denah->competitionCategory?->full_name ?? 'Denah umum (lintas tingkat lomba)' }}</td>
            </tr>
            <tr>
                <th>Tempat Lomba</th>
                <td>{{ $denah->venue?->name ?? $eventner->venue ?? '-' }}</td>
            </tr>
            <tr>
                <th>Ukuran Lapangan</th>
                <td>
                    {{ rtrim(rtrim(number_format((float) $denah->ground_width, 1, ',', '.'), '0'), ',') }}
                    &times;
                    {{ rtrim(rtrim(number_format((float) $denah->ground_height, 1, ',', '.'), '0'), ',') }} meter
                </td>
            </tr>
        </table>
    </div>

    @php
        // Daftar DP ditaruh terpisah dari gambar: mencari "DP 3" di antara lima
        // kotak biru lebih lambat daripada membacanya di tabel.
        $daftarDp = $denah->zones->where('type', \App\Models\LayoutZone::TYPE_DP)->sortBy('dp_number');
    @endphp

    @if($daftarDp->isNotEmpty())
        <div class="keterangan">
            <table>
                <tr>
                    <th style="width: 34%;">Daerah Persiapan</th>
                    <td>
                        @foreach($daftarDp as $dp)
                            <strong>{{ $dp->name }}</strong>@if($dp->capacity) ({{ $dp->capacity }})@endif@if(!$loop->last), @endif
                        @endforeach
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @php
        // Alur disembunyikan dari publik berarti juga disembunyikan dari kertas:
        // lembar ini beredar di luar panitia, jadi ia ikut aturan yang sama.
        // Nomornya ditulis manual, bukan lewat <ol>: penomoran list-item dompdf
        // tidak selalu ikut tercetak.
        $langkahAlur = $denah->alur_tampil ? $denah->alurLangkah() : [];
    @endphp

    @if($langkahAlur !== [])
        <div class="keterangan">
            <table>
                <tr>
                    <th style="width: 34%;">Alur Gerak Pasukan</th>
                    <td>
                        @foreach($langkahAlur as $langkah)
                            <div>
                                <strong>{{ $langkah['nomor'] }}. {{ $langkah['judul'] }}</strong>
                                @if($langkah['hadap'] !== null)
                                    &mdash; {{ $langkah['jenis'] }}, hadap {{ $langkah['hadap'] }}&deg;
                                @endif
                            </div>
                        @endforeach
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <table class="ttd">
        <tr>
            <td>
                <div class="garis">Ketua Panitia</div>
            </td>
            <td>
                <div class="garis">Koordinator Lapangan</div>
            </td>
        </tr>
    </table>
</body>
</html>
