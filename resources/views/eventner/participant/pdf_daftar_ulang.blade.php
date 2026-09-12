@php
    // Daftar ulang: satu halaman per kategori lomba (tingkat). Kolom tanda
    // tangan diisi tangan saat sekolah hadir di meja panitia.
    $safeLogo = null;
    if ($eventner->logo_event) {
        $p = public_path('storage/' . $eventner->logo_event);
        if (file_exists($p) && is_file($p)) $safeLogo = $p;
    }
    $totalPeserta = $registrations->flatten()->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Ulang Peserta - {{ $eventner->nama_event }}</title>
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

        /* BLOK KATEGORI */
        .cat-block { margin-bottom: 18px; }
        .level-head { background: #1a1a2e; color: #fff; padding: 7px 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; border-radius: 4px 4px 0 0; page-break-after: avoid; }
        .level-meta { padding: 5px 12px; background: #ecf0f1; border-left: 2px solid #3498db; border-right: 2px solid #3498db; font-size: 8px; color: #555; page-break-after: avoid; }

        table.daftar { width: 100%; border-collapse: collapse; }
        table.daftar thead { display: table-header-group; }
        table.daftar tr { page-break-inside: avoid; }
        table.daftar th { background: #f8f9fa; padding: 5px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.daftar th.center-col { text-align: center; }
        table.daftar td { padding: 8px; border: 1px solid #ddd; font-size: 9px; vertical-align: middle; }
        table.daftar td.center { text-align: center; }
        table.daftar td.name-col { font-weight: bold; color: #1a1a2e; }
        table.daftar .sub { color: #888; font-size: 8px; font-weight: normal; }
        .col-no { width: 26px; }
        .col-urutan { width: 52px; }
        .col-anggota { width: 48px; }
        .col-status { width: 88px; }
        .col-ttd { width: 130px; }

        table.daftar .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-ok { background: #e8f7f0; color: #198754; }
        .badge-wait { background: #fff8e6; color: #b8860b; }
        .badge-no { background: #fdecea; color: #c0392b; }

        .kosong { font-size: 8px; color: #999; font-style: italic; padding: 8px 12px; border: 1px dashed #ddd; border-top: none; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* TTD */
        .ttd { margin-top: 24px; page-break-inside: avoid; }
        .ttd table { width: 100%; border: none; }
        .ttd td { text-align: center; padding-top: 20px; width: 50%; vertical-align: top; border: none; }
        .ttd .role { font-weight: bold; margin-bottom: 8px; }
        .ttd .line { display: inline-block; width: 130px; border-top: 1px solid #333; }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

    {{-- KOP --}}
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

    <div class="judul">Daftar Ulang Peserta</div>
    <div class="subjudul">
        @if($categories->count() === 1)
            Kategori Lomba: {{ $categories->first()->full_name }} &bull;
        @else
            {{ $categories->count() }} Kategori Lomba &bull;
        @endif
        {{ $totalPeserta }} Peserta &bull;
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    @foreach($categories as $cat)
        @php $rows = $registrations->get($cat->id, collect()); @endphp

        <div class="cat-block">
            <div class="level-head">
                {{ $cat->full_name }}
            </div>
            <div class="level-meta">
                {{ $rows->count() }} peserta terdaftar &mdash;
                kolom tanda tangan diisi sekolah saat daftar ulang di meja panitia.
            </div>

            <table class="daftar">
                <thead>
                    <tr>
                        <th class="col-no center-col">No</th>
                        <th class="col-urutan center-col">Urut<br>Tampil</th>
                        <th>Nama Sekolah / Kontingen</th>
                        <th class="col-anggota center-col">Anggota</th>
                        <th class="col-status center-col">Status Berkas</th>
                        <th class="col-ttd center-col">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $reg)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="center">{{ $reg->urutan_tampil ?: '-' }}</td>
                            <td class="name-col">
                                {{ $reg->display_name }}
                                @if($reg->nama_pelatih)
                                    <div class="sub">Pelatih: {{ $reg->nama_pelatih }}</div>
                                @endif
                                @if($reg->no_hp)
                                    <div class="sub">{{ $reg->no_hp }}</div>
                                @endif
                            </td>
                            <td class="center">{{ $reg->participants->count() }}</td>
                            <td class="center">
                                @if($reg->status_berkas === 'Terverifikasi')
                                    <span class="badge badge-ok">Terverifikasi</span>
                                @elseif($reg->status_berkas === 'Menunggu Verifikasi')
                                    <span class="badge badge-wait">Menunggu</span>
                                @elseif($reg->status_berkas)
                                    <span class="badge badge-no">{{ $reg->status_berkas }}</span>
                                @else
                                    <span style="color:#999;">-</span>
                                @endif
                            </td>
                            <td class="col-ttd"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($rows->isEmpty())
                <div class="kosong">Belum ada peserta pada kategori ini.</div>
            @endif
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    {{-- Tanda Tangan --}}
    @php
        $qrData = event_url($eventner, 'detail');
        // PNG wajib — dompdf membuang SVG diam-diam (lihat helper qr_data_uri).
        $qrImage = qr_data_uri($qrData);
    @endphp

    <div class="ttd">
        <table>
            <tr>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Ketua Panitia</div>
                    <img src="{{ $qrImage }}" style="width:70px; height:70px; margin:0 auto; display:block;" alt="QR">
                    <div style="margin-top:6px; font-weight:bold; font-size:9px;">{{ $eventner->diselenggarakan_oleh }}</div>
                </td>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Sekretaris Panitia</div>
                    <br><br><br>
                    <span class="line"></span><br>
                    <small>___________________</small>
                </td>
            </tr>
        </table>
    </div>

    <div class="foot">
        {{ $eventner->nama_event }} &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by {{ app_name() }}
    </div>

</body>
</html>
