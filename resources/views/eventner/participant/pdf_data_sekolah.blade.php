@php
    // Rekap sekolah: satu baris per SEKOLAH, bukan per pasukan — halaman
    // peserta menyimpan satu baris per pasukan, dan di sini yang dibutuhkan
    // sudut pandang sekolah. Pengelompokannya ada di App\Support\DataSekolah.
    $safeLogo = null;
    if ($eventner->logo_event) {
        $p = public_path('storage/' . $eventner->logo_event);
        if (file_exists($p) && is_file($p)) $safeLogo = $p;
    }
    $totalPasukan = $sekolah->sum('jumlah_pasukan');
    $totalAnggota = $sekolah->sum('jumlah_anggota');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Sekolah - {{ $eventner->nama_event }}</title>
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
        @page { size: A4 landscape; margin: 12mm 10mm; }
        body {
            font-family: 'PJ', sans-serif;
            font-size: 9px;
            color: #222;
            padding: 0;
            margin: 0;
        }

        /* KOP */
        .kop { border-bottom: 3px double #222; padding-bottom: 8px; margin-bottom: 12px; }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 52px; height: 52px; border-radius: 6px; border: 1px solid #ccc; }
        .kop-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .kop-sub { font-size: 9px; color: #666; }

        .judul { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .subjudul { font-size: 8.5px; color: #666; margin-bottom: 12px; }

        /* TABEL */
        table.rekap { width: 100%; border-collapse: collapse; }
        table.rekap th {
            background: #2c3e50;
            color: #fff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 6px 5px;
            text-align: left;
            border: 1px solid #2c3e50;
        }
        table.rekap td {
            padding: 5px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        table.rekap tbody tr:nth-child(even) td { background: #f8f9fa; }
        table.rekap tfoot td {
            background: #eef1f4;
            font-weight: bold;
            border: 1px solid #ccd3da;
            padding: 6px 5px;
        }

        .center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .muted { color: #999; }
        .nama { font-weight: bold; }
        .sub { font-size: 7.5px; color: #777; }

        .col-no { width: 26px; }
        .col-npsn { width: 62px; }
        .col-kab { width: 92px; }
        .col-angka { width: 46px; }
        .col-status { width: 84px; }
        .col-hp { width: 84px; }
        .col-email { width: 110px; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-ok { background: #e8f7f0; color: #198754; }
        .badge-wait { background: #fff8e6; color: #b8860b; }
        .badge-no { background: #fdecea; color: #c0392b; }
        .badge-off { background: #eef1f4; color: #6c757d; }

        .kosong { font-size: 9px; color: #999; font-style: italic; padding: 14px; border: 1px dashed #ddd; text-align: center; }

        .foot { margin-top: 14px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }
    </style>
</head>
<body>

    {{-- KOP --}}
    <div class="kop">
        <table>
            <tr>
                <td style="width: 62px;">
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
                            // Konteks event: sebutkan semua tempat, bukan hanya satu.
                            $dsVenues = $eventner->activeVenues()->pluck('name');
                            $dsVenueText = $dsVenues->isNotEmpty() ? $dsVenues->implode(' / ') : $eventner->venue;
                        @endphp
                        @if($dsVenueText) &middot; {{ $dsVenueText }} @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">Data Sekolah Pendaftar</div>
    <div class="subjudul">
        {{ $sekolah->count() }} sekolah &bull; {{ $totalPasukan }} pasukan &bull; {{ $totalAnggota }} anggota &bull;
        Seluruh kategori lomba &bull; Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    @if($sekolah->isEmpty())
        <div class="kosong">Belum ada sekolah yang mendaftar di event ini.</div>
    @else
        <table class="rekap">
            <thead>
                <tr>
                    <th class="col-no center">No</th>
                    <th class="col-npsn">NPSN</th>
                    <th>Nama Sekolah</th>
                    <th class="col-kab">Kabupaten / Kota</th>
                    <th class="col-angka center">Pasukan</th>
                    <th class="col-angka center">Kategori</th>
                    <th class="col-angka center">Anggota</th>
                    <th class="col-status center">Status</th>
                    <th>Pelatih</th>
                    <th class="col-hp">No. HP</th>
                    <th class="col-email">Email Sekolah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sekolah as $i => $s)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td class="nowrap">{{ $s['npsn'] ?: '—' }}</td>
                        <td class="nama">{{ $s['nama_sekolah'] }}</td>
                        {{-- Field Kabupaten/Kota buatan panitia dan bisa dihapus:
                             kolomnya tetap ada supaya lebar tabel tidak bergeser
                             antar-event, isinya "—" bila tidak ada. --}}
                        <td>{{ $s['kabupaten'] ?: '—' }}</td>
                        <td class="center">{{ $s['jumlah_pasukan'] }}</td>
                        <td class="center">{{ $s['jumlah_kategori'] }}</td>
                        <td class="center">{{ $s['jumlah_anggota'] }}</td>
                        <td class="center">
                            @if($s['status'] === 'Terverifikasi')
                                <span class="badge badge-ok">Terverifikasi</span>
                            @elseif(in_array($s['status'], ['Menunggu', 'confirmed'], true))
                                <span class="badge badge-wait">Menunggu</span>
                            @elseif($s['status'] === 'Ditolak')
                                <span class="badge badge-no">Ditolak</span>
                            @elseif($s['status'] !== '')
                                <span class="badge badge-off">{{ $s['label_status'] }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $s['pelatih'] ?: '—' }}</td>
                        <td class="nowrap">{{ $s['no_hp'] ?: '—' }}</td>
                        <td>{{ $s['email'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="center">TOTAL</td>
                    <td class="center">{{ $totalPasukan }}</td>
                    <td class="center">{{ $sekolah->sum('jumlah_kategori') }}</td>
                    <td class="center">{{ $totalAnggota }}</td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>

        <div class="foot">
            Satu baris = satu sekolah. Sekolah dengan beberapa pasukan digabung di sini &mdash;
            rincian per pasukan ada di halaman Daftar Peserta.
            <br>
            {{ $eventner->nama_event }} &mdash; Generated by {{ app_name() }}
        </div>
    @endif

</body>
</html>
