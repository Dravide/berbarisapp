@php
    // Daftar ulang: satu halaman per kategori lomba (tingkat). Kolom tanda
    // tangan diisi tangan saat sekolah hadir di meja panitia.
    $safeLogo = null;
    if ($eventner->logo_event) {
        $p = public_path('storage/' . $eventner->logo_event);
        if (file_exists($p) && is_file($p)) $safeLogo = $p;
    }
    $tanggalEvent = $eventner->tanggal
        ? \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y')
        : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Ulang Peserta</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        /* KOP */
        .kop {
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .kop table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .kop td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }
        .kop-title {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a2e;
            margin: 0;
        }
        .kop-sub {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        /* JUDUL */
        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f2f2f2;
            padding: 6px;
            margin-bottom: 4px;
            border: 1px solid #ddd;
            letter-spacing: 1px;
        }
        .kategori {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .kategori span {
            font-weight: normal;
            color: #666;
        }
        /* TABEL PESERTA */
        .daftar {
            width: 100%;
            border-collapse: collapse;
        }
        .daftar th,
        .daftar td {
            border: 1px solid #999;
            padding: 5px 6px;
            vertical-align: middle;
        }
        .daftar thead th {
            background-color: #eaeaea;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #1a1a2e;
            text-align: center;
        }
        .col-no { width: 26px; text-align: center; }
        .col-urutan { width: 46px; text-align: center; }
        .col-sekolah { width: auto; }
        .col-anggota { width: 52px; text-align: center; }
        .col-status { width: 88px; font-size: 9px; text-align: center; }
        .col-ttd { width: 118px; }
        .nowrap { white-space: nowrap; }
        .muted { color: #777; }
        .empty {
            border: 1px solid #999;
            border-top: none;
            padding: 16px;
            text-align: center;
            color: #888;
        }
        .catatan {
            margin-top: 10px;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

@foreach($categories as $cat)
    @php $rows = $registrations->get($cat->id, collect()); @endphp

    <!-- KOP -->
    <div class="kop">
        <table>
            <tr>
                <td style="width: 62px;">
                    @if($safeLogo)
                        <img src="{{ $safeLogo }}" class="kop-logo">
                    @endif
                </td>
                <td style="padding-left: 10px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">
                        Diselenggarakan oleh: {{ $eventner->diselenggarakan_oleh }}
                        @if($tanggalEvent) &middot; {{ $tanggalEvent }} @endif
                        @if($eventner->venue) &middot; {{ $eventner->venue }} @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- JUDUL -->
    <div class="title">Daftar Ulang Peserta</div>
    <div class="kategori">
        {{ $cat->full_name }}
        <span>&middot; {{ $rows->count() }} peserta</span>
    </div>

    <table class="daftar">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-urutan">Urut Tampil</th>
                <th class="col-sekolah">Nama Sekolah / Kontingen</th>
                <th class="col-anggota">Anggota</th>
                <th class="col-status">Status Berkas</th>
                <th class="col-ttd">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $reg)
                <tr>
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-urutan">{{ $reg->urutan_tampil ?? '—' }}</td>
                    <td class="col-sekolah">
                        <strong>{{ $reg->display_name }}</strong>
                        @if($reg->nama_pelatih)
                            <div class="muted">Pelatih: {{ $reg->nama_pelatih }}</div>
                        @endif
                        @if($reg->no_hp)
                            <div class="muted">{{ $reg->no_hp }}</div>
                        @endif
                    </td>
                    <td class="col-anggota">{{ $reg->participants->count() }}</td>
                    <td class="col-status">{{ $reg->status_berkas ?: '—' }}</td>
                    <td class="col-ttd"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">Belum ada peserta pada kategori ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

<div class="catatan">
    Kolom tanda tangan diisi oleh sekolah saat melakukan daftar ulang di meja panitia.
    Total {{ $registrations->flatten()->count() }} peserta pada {{ $categories->count() }} kategori.
</div>

</body>
</html>
