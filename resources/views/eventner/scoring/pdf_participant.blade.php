<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nilai {{ $registration->nama_sekolah }}</title>
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
        .judul { background: #1a1a2e; color: #fff; text-align: center; padding: 7px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 14px; }

        /* INFO */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { padding: 4px 8px; border: 1px solid #ddd; font-size: 9px; }
        .info .lbl { background: #f5f6fa; font-weight: bold; color: #555; width: 130px; }
        .info .val { color: #1a1a2e; font-weight: bold; }

        /* KATEGORI */
        .cat-head { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .cat-head td { padding: 6px 10px; color: #fff; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .cat-head .cat-name { background: #2c3e50; }
        .cat-head .cat-score { background: #2c3e50; text-align: right; color: #f1c40f; }

        .sub-head { background: #ecf0f1; padding: 4px 10px; font-size: 8px; font-weight: bold; color: #2c3e50; text-transform: uppercase; letter-spacing: 0.5px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }

        /* TABEL KRITERIA */
        table.krit { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.krit th { background: #f8f9fa; padding: 4px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.krit th:last-child { text-align: center; width: 40px; }
        table.krit td { padding: 4px 8px; border: 1px solid #ddd; font-size: 10px; }
        table.krit .cn { font-weight: bold; color: #2c3e50; }
        table.krit .sv { text-align: center; font-weight: bold; font-size: 11px; }
        table.krit .ok { color: #27ae60; background: #eafaf1; }
        table.krit .no { color: #e67e22; background: #fef9e7; }

        /* JUDGE HEADER */
        .judge-label { background: #f0f4f8; padding: 4px 10px; font-size: 8px; font-weight: bold; color: #2c3e50; text-transform: uppercase; letter-spacing: 0.5px; border-left: 1px solid #ddd; border-right: 1px solid #ddd; border-top: 1px solid #ddd; }

        /* PER-JUDGE TABLE (compact side by side) */
        table.judge-scores { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.judge-scores th { background: #f8f9fa; padding: 4px 6px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; border: 1px solid #ddd; }
        table.judge-scores td { padding: 3px 6px; border: 1px solid #ddd; font-size: 9px; }
        table.judge-scores .crit-name { font-weight: bold; color: #2c3e50; text-align: left; }
        table.judge-scores .j-score { text-align: center; font-weight: bold; font-size: 10px; }
        table.judge-scores .total-col { background: #ecf0f1; font-weight: bold; text-align: center; }
        table.judge-scores .j-name { text-align: center; font-weight: bold; background: #eef2f7; color: #2c3e50; }

        /* GRAND TOTAL */
        .gt { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .gt td { padding: 8px; border: 1px solid #1a1a2e; }
        .gt-l { background: #1a1a2e; color: #fff; text-align: center; width: 50%; }
        .gt-lb { font-size: 8px; text-transform: uppercase; letter-spacing: 2px; color: #aaa; }
        .gt-v { font-size: 28px; font-weight: bold; }
        .gt-r { background: #f8f9fa; vertical-align: top; }
        .gt-r table { width: 100%; border-collapse: collapse; }
        .gt-r td { border: none; border-bottom: 1px solid #eee; padding: 3px 8px; font-size: 9px; }
        .gt-r .gn { color: #666; }
        .gt-r .gv { font-weight: bold; text-align: right; }

        /* TTD */
        .ttd { margin-top: 36px; }
        .ttd table { width: 100%; border: none; }
        .ttd td { text-align: center; padding-top: 45px; width: 33%; vertical-align: top; border: none; }
        .ttd .role { font-weight: bold; }
        .ttd .line { display: inline-block; width: 130px; border-top: 1px solid #333; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }
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

    <div class="judul">Lembar Penilaian</div>

    <table class="info">
        <tr>
            <td class="lbl">Nama Kontingen</td>
            <td class="val">{{ $registration->nama_sekolah }}</td>
            <td class="lbl">NPSN</td>
            <td>{{ $registration->npsn }}</td>
        </tr>
        <tr>
            <td class="lbl">Pelatih</td>
            <td>{{ $registration->nama_pelatih }}</td>
            <td class="lbl">Kategori Lomba</td>
            <td>{{ $registration->competitionCategory->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Tanggal Cetak</td>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
            <td class="lbl">Waktu</td>
            <td>{{ now()->format('H:i') }} WIB</td>
        </tr>
    </table>

    @foreach($assessmentCategories as $cat)
        <table class="cat-head">
            <tr>
                <td class="cat-name">{{ $cat->name }}</td>
                <td class="cat-score">{{ $categoryTotals[$cat->id] ?? 0 }} poin</td>
            </tr>
        </table>

        {{-- Per-Judge Score Table --}}
        @if($judges->count() > 0)
            <table class="judge-scores">
                <thead>
                    <tr>
                        <th style="text-align:left;">Kriteria</th>
                        @foreach($judges as $judge)
                            <th class="j-name">{{ $judge->name }}</th>
                        @endforeach
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cat->subCategories as $sub)
                        @if($sub->criterias->count() > 0)
                            @foreach($sub->criterias as $crit)
                                <tr>
                                    <td class="crit-name">{{ $crit->name }}</td>
                                    @foreach($judges as $judge)
                                        @php
                                            $jVal = $judgeScores[$judge->id][$crit->id] ?? null;
                                        @endphp
                                        <td class="j-score {{ $jVal !== null ? 'ok' : 'no' }}" style="{{ $jVal !== null ? 'color:#27ae60;' : 'color:#e67e22;' }}">
                                            {{ $jVal ?? '-' }}
                                        </td>
                                    @endforeach
                                    <td class="total-col" style="font-weight:bold;">{{ $criteriaTotals[$crit->id] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                    {{-- Subtotal row --}}
                    <tr>
                        <td style="font-weight:bold; background:#ecf0f1;">Subtotal</td>
                        @foreach($judges as $judge)
                            <td class="total-col">{{ $judgeCategoryTotals[$judge->id]['totals'][$cat->id] ?? 0 }}</td>
                        @endforeach
                        <td class="total-col" style="background:#2c3e50; color:#fff;">{{ $categoryTotals[$cat->id] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            {{-- Fallback: no judges, show simple table --}}
            @foreach($cat->subCategories as $sub)
                <div class="sub-head">{{ $sub->name }}</div>
                <table class="krit">
                    <thead>
                        <tr>
                            <th>Kriteria Penilaian</th>
                            <th>Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sub->criterias as $crit)
                            @php
                                $val = $criteriaTotals[$crit->id] ?? null;
                            @endphp
                            <tr>
                                <td class="cn">{{ $crit->name }}</td>
                                <td class="sv {{ $val !== null ? 'ok' : 'no' }}">{{ $val ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endif
    @endforeach

    {{-- Grand Total --}}
    <table class="gt">
        <tr>
            <td class="gt-l">
                <div class="gt-lb">Total Keseluruhan</div>
                <div class="gt-v">{{ $grandTotal }}</div>
            </td>
            <td class="gt-r">
                <table>
                    @foreach($assessmentCategories as $cat)
                        <tr>
                            <td class="gn">{{ $cat->name }}</td>
                            <td class="gv">{{ $categoryTotals[$cat->id] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    @php
        use chillerlan\QRCode\QRCode;
        $qrData = route('magic.link', $registration->magic_token);
        $qrImage = (new QRCode)->render($qrData);
    @endphp

    {{-- Pernyataan Persetujuan --}}
    <div style="margin-top:20px; border:1px solid #ccc; border-radius:4px; padding:12px 16px; background:#fafafa;">
        <div style="font-weight:bold; font-size:9px; text-transform:uppercase; color:#1a1a2e; margin-bottom:6px; letter-spacing:0.5px;">Pernyataan Persetujuan</div>
        <div style="font-size:8px; color:#444; line-height:1.7;">
            Dengan menandatangani lembar penilaian ini, kami menyatakan bahwa:
            <ol style="margin:4px 0 0 0; padding-left:16px;">
                <li>Seluruh nilai yang tercantum di atas telah diperiksa dan diverifikasi kebenarannya.</li>
                <li>Penilaian dilakukan secara objektif, adil, dan sesuai dengan rubrik penilaian yang telah ditetapkan.</li>
                <li>Hasil penilaian ini bersifat final dan dapat dipertanggungjawabkan.</li>
                <li>Kami menyetujui hasil penilaian sebagai nilai resmi peserta dalam {{ $eventner->nama_event }}.</li>
            </ol>
        </div>
    </div>

    <div class="ttd">
        <table style="width:100%;">
            <tr>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Ketua Panitia</div>
                    <img src="{{ $qrImage }}" style="width:90px; height:90px; margin:0 auto; display:block;" alt="QR">
                    <div style="margin-top:6px; font-weight:bold; font-size:10px;">{{ $eventner->diselenggarakan_oleh }}</div>
                </td>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Pelatih</div>
                    <br><br><br>
                    <span class="line"></span><br>
                    <small>{{ $registration->nama_pelatih }}</small>
                </td>
            </tr>
        </table>
    </div>

    <div class="foot">
        {{ $eventner->nama_event }} &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by BARIS APP
    </div>

</body>
</html>
