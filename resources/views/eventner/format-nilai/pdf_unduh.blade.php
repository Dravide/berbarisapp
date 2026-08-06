<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Format Penilaian - {{ $eventner->nama_event }}</title>
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

        /* INFO */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { padding: 4px 8px; border: 1px solid #ddd; font-size: 9px; }
        .info .lbl { background: #f5f6fa; font-weight: bold; color: #555; width: 130px; }
        .info .val { color: #1a1a2e; font-weight: bold; }

        /* KATEGORI */
        .cat-head { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .cat-head td { padding: 6px 10px; color: #fff; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .cat-head .cat-name { background: #2c3e50; }

        .sub-head { background: #ecf0f1; padding: 4px 10px; font-size: 8px; font-weight: bold; color: #2c3e50; text-transform: uppercase; letter-spacing: 0.5px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }

        /* TABEL KRITERIA */
        table.krit { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.krit th { background: #f8f9fa; padding: 4px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.krit td { padding: 4px 8px; border: 1px solid #ddd; font-size: 10px; }
        table.krit .cn { font-weight: bold; color: #2c3e50; }
        table.krit .sv { text-align: center; font-weight: bold; font-size: 11px; }

        /* PENGURANGAN */
        .deduction-head { background: #fde8e8; padding: 4px 10px; font-size: 8px; font-weight: bold; color: #b00020; text-transform: uppercase; letter-spacing: 0.5px; border-left: 1px solid #f5c6c6; border-right: 1px solid #f5c6c6; }
        table.ded { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.ded th { background: #fdf2f2; padding: 4px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #b00020; text-align: left; border: 1px solid #f5c6c6; }
        table.ded td { padding: 4px 8px; border: 1px solid #f5c6c6; font-size: 10px; }

        /* TABEL DAFTAR PESERTA */
        table.daftar { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.daftar th { background: #2c3e50; color: #fff; padding: 6px 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #2c3e50; }
        table.daftar td { padding: 6px 8px; border: 1px solid #ddd; font-size: 10px; }
        table.daftar .no { text-align: center; width: 45px; font-weight: bold; }
        table.daftar .undian { text-align: center; width: 60px; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* TTD */
        .ttd { margin-top: 24px; }
        .ttd table { width: 100%; border: none; }
        .ttd td { border: none; }
        .ttd .role { font-weight: bold; }
        .ttd .line { display: inline-block; width: 130px; border-top: 1px solid #333; }

        /* CATATAN JURI */
        .catatan-box { width: 90%; height: 80px; border: 1px solid #bbb; border-radius: 4px; margin-top: 6px; }

        /* PAGE FLOW */
        .cat-section { margin-bottom: 14px; }
        table.krit thead, table.ded thead, table.daftar thead { display: table-header-group; }
        table.krit tr, table.ded tr, table.daftar tr { page-break-inside: avoid; }
    </style>
</head>
<body>

    <div class="kop">
        <table>
            <tr>
                <td style="width: 70px;">
                    @if($eventner->logo_event)
                        <img src="{{ public_path('storage/' . $eventner->logo_event) }}" class="kop-logo">
                    @endif
                </td>
                <td style="padding-left: 12px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">{{ $eventner->diselenggarakan_oleh }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">
        @if($mode === 'daftar')
            Daftar Peserta Lomba
        @elseif($mode === 'peserta')
            Lembar Penilaian Peserta
        @else
            Lembar Penilaian
        @endif
    </div>
    <div class="subjudul">
        @if(!empty($judgeName))
            Juri: {{ $judgeName }} &bull;
        @endif
        @if(!empty($childName))
            Tingkat: {{ $childName }} &bull;
        @endif
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    @if($mode === 'peserta' && $registration)
        <table class="info">
            <tr>
                <td class="lbl">No. Urut</td>
                <td class="val">{{ $registration->urutan_tampil ? '#' . $registration->urutan_tampil : '-' }}</td>
                <td class="lbl">Nama Kontingen</td>
                <td class="val">{{ $registration->nama_sekolah }}</td>
            </tr>
            <tr>
                <td class="lbl">Pelatih</td>
                <td>{{ $registration->nama_pelatih }}</td>
                <td class="lbl">Tingkat Lomba</td>
                <td>{{ $registration->competitionCategory->full_name ?? '-' }}</td>
            </tr>
        </table>
    @endif

    @if($mode === 'daftar')
        @if($registrations->isEmpty())
            <p style="text-align:center; color:#888;">Belum ada peserta terdaftar.</p>
        @else
            <table class="daftar">
                <thead>
                    <tr>
                        <th class="no">No</th>
                        <th class="undian">No. Urut</th>
                        <th>Nama Kontingen / Sekolah</th>
                        <th>Pelatih</th>
                        <th>Tingkat Lomba</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($registrations as $i => $reg)
                        <tr>
                            <td class="no">{{ $i + 1 }}</td>
                            <td class="undian">{{ $reg->urutan_tampil ?: '-' }}</td>
                            <td>{{ $reg->nama_sekolah }}</td>
                            <td>{{ $reg->nama_pelatih }}</td>
                            <td>{{ $reg->competitionCategory->full_name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        @include('eventner.format-nilai._rubrik_body', ['categories' => $categories])
    @endif

    @php
        use chillerlan\QRCode\QRCode;
        $qrData = event_url($eventner, 'detail');
        $qrImage = (new QRCode)->render($qrData);
    @endphp

    <div class="ttd">
        @if($mode === 'peserta' && $registration)
            {{-- Lembar juri: tanda tangan juri + catatan juri saja --}}
            <table>
                <tr>
                    <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                        <div class="role" style="margin-bottom:8px;">Tanda Tangan Juri</div>
                        <br><br><br>
                        <span class="line"></span><br>
                        <small>{{ $judgeName ?: '___________________' }}</small>
                    </td>
                    <td style="text-align:left; width:50%; vertical-align:top; padding-top:10px;">
                        <div class="role" style="margin-bottom:4px;">Catatan Juri</div>
                        <div class="catatan-box"></div>
                    </td>
                </tr>
            </table>
        @else
            <table>
                <tr>
                    <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                        <div class="role" style="margin-bottom:8px;">Ketua Panitia</div>
                        <img src="{{ $qrImage }}" style="width:80px; height:80px; margin:0 auto; display:block;" alt="QR">
                        <div style="margin-top:6px; font-weight:bold; font-size:9px;">{{ $eventner->diselenggarakan_oleh }}</div>
                    </td>
                    <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                        <div class="role" style="margin-bottom:8px;">
                            @if($mode === 'peserta' && $registration)
                                Pelatih
                            @else
                                Koordinator Juri
                            @endif
                        </div>
                        <br><br><br>
                        <span class="line"></span><br>
                        @if($mode === 'peserta' && $registration)
                            <small>{{ $registration->nama_pelatih }}</small>
                        @else
                            <small>___________________</small>
                        @endif
                    </td>
                </tr>
            </table>
        @endif
    </div>

    <div class="foot">
        {{ $eventner->nama_event }} &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by BARIS APP
    </div>

</body>
</html>
