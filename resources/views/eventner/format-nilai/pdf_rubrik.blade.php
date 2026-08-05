<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rubrik Penilaian - {{ $eventner->nama_event }}</title>
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

        /* LEGENDA LABEL */
        .label-legend { margin: 6px 8px 0; padding: 5px 8px; background: #f3f6f9; border: 1px solid #ddd; border-radius: 4px; font-size: 9px; }
        .legend-item { margin-right: 14px; color: #2c3e50; }

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

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* TTD */
        .ttd { margin-top: 24px; }
        .ttd table { width: 100%; border: none; }
        .ttd td { border: none; }
        .ttd .role { font-weight: bold; }
        .ttd .line { display: inline-block; width: 130px; border-top: 1px solid #333; }

        /* PAGE FLOW */
        .cat-section { margin-bottom: 14px; }
        table.krit thead, table.ded thead { display: table-header-group; }
        table.krit tr, table.ded tr { page-break-inside: avoid; }
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

    <div class="judul">Format Penilaian</div>
    <div class="subjudul">
        @if(!empty($childName))
            Tingkat: {{ $childName }} &bull;
        @endif
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    <table class="info">
        <tr>
            <td class="lbl">Kegiatan</td>
            <td class="val">{{ $eventner->nama_event }}</td>
            <td class="lbl">Penyelenggara</td>
            <td>{{ $eventner->diselenggarakan_oleh }}</td>
        </tr>
        <tr>
            <td class="lbl">Tingkat</td>
            <td>{{ !empty($childName) ? $childName : 'Semua Tingkat' }}</td>
            <td class="lbl">Tanggal Cetak</td>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    @if($categories->isEmpty())
        <p style="text-align:center; color:#888;">Belum ada format penilaian yang dibangun.</p>
    @else
        @foreach($categories as $category)
            <div class="cat-section">
            <table class="cat-head">
                <tr>
                    <td class="cat-name">{{ $category->name }}</td>
                </tr>
            </table>

            @foreach($category->subCategories as $subcat)
                <div class="sub-head">{{ $subcat->name }}</div>

                @if($subcat->criterias->isNotEmpty())
                    @php
                        // Kumpulkan label group dari semua kriteria sub-kategori.
                        $labelHeader = [];
                        foreach($subcat->criterias as $crit) {
                            foreach($crit->score_options ?? [] as $o) {
                                if (is_array($o) && !empty($o['label'])) {
                                    $labelHeader[$o['label']][] = (int) $o['score'];
                                }
                            }
                        }
                        // Urutan label unik → jadi kolom "SKOR PENILAIAN" (KURANG | CUKUP | BAIK | ...).
                        $labelCols = array_keys($labelHeader);
                        $hasLabels = count($labelCols) > 0;
                    @endphp
                    @if($hasLabels)
                        <div class="label-legend">
                            @foreach($labelHeader as $label => $scores)
                                @php $vals = array_values(array_unique($scores)); sort($vals); $range = count($vals) > 1 ? min($vals) . ' – ' . max($vals) : $vals[0]; @endphp
                                <span class="legend-item"><strong>{{ $label }}</strong>: {{ $range }}</span>
                            @endforeach
                        </div>
                    @endif
                    <table class="krit">
                        <thead>
                            <tr>
                                <th width="45%">Kriteria Penilaian</th>
                                <th width="12%" style="text-align:center;">Bobot</th>
                                @if($hasLabels)
                                    @foreach($labelCols as $label)
                                        <th style="text-align:center;">{{ $label }}</th>
                                    @endforeach
                                @else
                                    <th width="43%" style="text-align:center;">Skor Penilaian</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subcat->criterias as $crit)
                            <tr>
                                <td class="cn">{{ $crit->name }}</td>
                                <td class="sv">{{ $crit->weight ?? 1 }}x</td>
                                @if($hasLabels)
                                    @foreach($labelCols as $label)
                                        @php
                                            // Skor yang masuk grup label ini (mis. semua opsi "Kurang").
                                            $cellScores = collect($crit->score_options ?? [])
                                                ->filter(fn($o) => is_array($o) && ($o['label'] ?? null) === $label)
                                                ->map(fn($o) => $o['score'] ?? null)
                                                ->filter(fn($v) => $v !== null)
                                                ->values();
                                        @endphp
                                        <td class="sv">{{ $cellScores->isEmpty() ? '&nbsp;' : implode(' / ', $cellScores->all()) }}</td>
                                    @endforeach
                                @else
                                    <td class="sv">
                                        @foreach($crit->score_options as $score)
                                            @php $sv = is_array($score) ? ($score['score'] ?? '') : $score; @endphp
                                            <span>{{ $sv }}</span>@if(!$loop->last) &nbsp;@endif
                                        @endforeach
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach

            @if($category->deductionCategories->isNotEmpty())
                <div class="deduction-head">Pengurangan Nilai (untuk kategori ini)</div>
                @foreach($category->deductionCategories as $deductionCat)
                    <div class="sub-head">{{ $deductionCat->name }}</div>
                    @if($deductionCat->criterias->isNotEmpty())
                        <table class="ded">
                            <thead>
                                <tr>
                                    <th width="40%">Kriteria Pengurangan</th>
                                    <th width="60%" style="text-align:center;">Opsi Pengurangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deductionCat->criterias as $deductionCrit)
                                <tr>
                                    <td>{{ $deductionCrit->name }}</td>
                                    <td style="text-align:center;">
                                        0
                                        @foreach($deductionCrit->deduction_options as $opt)
                                            &nbsp;{{ $opt }}
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                @endforeach
            @endif
            </div>
        @endforeach
    @endif

    {{-- TANDA TANGAN --}}
    @php
        use chillerlan\QRCode\QRCode;
        $qrData = event_url($eventner, 'detail');
        $qrImage = (new QRCode)->render($qrData);
    @endphp

    <div class="ttd">
        <table>
            <tr>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Ketua Panitia</div>
                    <img src="{{ $qrImage }}" style="width:80px; height:80px; margin:0 auto; display:block;" alt="QR">
                    <div style="margin-top:6px; font-weight:bold; font-size:9px;">{{ $eventner->diselenggarakan_oleh }}</div>
                </td>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Koordinator Juri</div>
                    <br><br><br>
                    <span class="line"></span><br>
                    <small>___________________</small>
                </td>
            </tr>
        </table>
    </div>

    <div class="foot">
        {{ $eventner->nama_event }} &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by BARIS APP
    </div>

</body>
</html>
