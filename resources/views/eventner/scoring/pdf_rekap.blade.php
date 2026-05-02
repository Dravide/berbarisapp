<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai - {{ $eventner->nama_event }}</title>
    <style>
        @font-face {
            font-family: 'PlusJakartaSans';
            src: url('{{ public_path('fonts/PlusJakartaSans-Regular.ttf') }}');
            font-weight: normal;
        }
        @font-face {
            font-family: 'PlusJakartaSans';
            src: url('{{ public_path('fonts/PlusJakartaSans-SemiBold.ttf') }}');
            font-weight: bold;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'PlusJakartaSans', sans-serif; font-size: 9px; color: #1a1a2e; }

        /* ── KOP SURAT ── */
        .kop-surat { width: 100%; border-bottom: 3px double #1a1a2e; padding-bottom: 10px; margin-bottom: 12px; }
        .kop-table { width: 100%; border: none; }
        .kop-table td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 55px; height: 55px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
        .kop-title { font-size: 14px; font-weight: bold; color: #1a1a2e; text-transform: uppercase; letter-spacing: 1px; }
        .kop-subtitle { font-size: 10px; color: #555; font-weight: normal; margin-top: 2px; }

        /* ── JUDUL DOKUMEN ── */
        .doc-title-bar { background: #1a1a2e; color: #fff; text-align: center; padding: 6px 16px; margin-bottom: 12px; }
        .doc-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }

        .meta-row { margin-bottom: 8px; font-size: 8px; color: #666; }
        .meta-row strong { color: #1a1a2e; }

        /* ── TABLE ── */
        table.rekap { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 5px; text-align: center; font-size: 8px; }
        th { background: #f5f6fa; font-weight: bold; color: #555; text-transform: uppercase; letter-spacing: 0.3px; font-size: 7px; }

        .th-cat { background: #2c3e50; color: #fff; font-size: 8px; padding: 5px 8px; }
        .th-sub { background: #ecf0f1; font-size: 7px; font-weight: bold; color: #2c3e50; }
        td.p-name { text-align: left; font-weight: bold; white-space: nowrap; }
        td.p-coach { text-align: left; font-size: 7px; color: #666; white-space: nowrap; }
        .col-rank { font-weight: bold; font-size: 10px; color: #1a1a2e; }
        .col-total { background: #1a1a2e; color: #fff; font-weight: bold; font-size: 10px; }
        .col-sub { background: #ecf0f1; font-weight: bold; color: #2c3e50; }
        .empty-score { color: #ccc; }

        .rank-1 td { background: #fef9e7; }
        .rank-1 .col-total { background: #1a1a2e; }
        .rank-2 td { background: #eafaf1; }
        .rank-2 .col-total { background: #1a1a2e; }
        .rank-3 td { background: #eaf2f8; }
        .rank-3 .col-total { background: #1a1a2e; }

        .page-break { page-break-after: always; }
        .footer { margin-top: 12px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <table class="kop-surat kop-table">
        <tr>
            <td style="width: 65px;">
                @if($eventner->logo_event)
                    <img src="{{ public_path('storage/' . $eventner->logo_event) }}" class="kop-logo" alt="">
                @endif
            </td>
            <td style="padding-left: 10px;">
                <div class="kop-title">{{ $eventner->nama_event }}</div>
                <div class="kop-subtitle">{{ $eventner->diselenggarakan_oleh }}</div>
            </td>
        </tr>
    </table>

    {{-- JUDUL DOKUMEN --}}
    <div class="doc-title-bar">
        <div class="doc-title">Rekap Penilaian</div>
    </div>

    <div class="meta-row">
        Kategori: <strong>{{ $competitionCategory ? $competitionCategory->name : 'Semua Kategori Lomba' }}</strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Dicetak: <strong>{{ now()->translatedFormat('d F Y H:i') }}</strong>
    </div>

    @if(count($scoringData) === 0)
        <p style="text-align: center; padding: 30px 0; color: #aaa; font-size: 11px;">Belum ada data peserta atau penilaian.</p>
    @else
        <table class="rekap">
            <thead>
                <tr>
                    <th rowspan="3" style="width: 22px;">No</th>
                    <th rowspan="3" style="min-width: 90px; text-align: left;">Peserta</th>
                    <th rowspan="3" style="min-width: 75px; text-align: left;">Pelatih</th>
                    @foreach($assessmentCategories as $cat)
                        @php $criteriaCount = $cat->subCategories->sum(fn($s) => $s->criterias->count()); @endphp
                        <th class="th-cat" colspan="{{ $criteriaCount + 1 }}">{{ $cat->name }}</th>
                    @endforeach
                    <th rowspan="3" class="col-total" style="width: 40px;">Total</th>
                    <th rowspan="3" style="width: 28px;">Rank</th>
                </tr>
                <tr>
                    @foreach($assessmentCategories as $cat)
                        @foreach($cat->subCategories as $sub)
                            <th class="th-sub" colspan="{{ $sub->criterias->count() }}">{{ $sub->name }}</th>
                        @endforeach
                        <th class="col-sub" rowspan="2" style="width: 28px;">Sub</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($assessmentCategories as $cat)
                        @foreach($cat->subCategories as $sub)
                            @foreach($sub->criterias as $crit)
                                <th style="font-size: 6px; max-width: 45px; word-wrap: break-word;">{{ $crit->name }}</th>
                            @endforeach
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($scoringData as $index => $data)
                    @php
                        $rankClass = match($index) {
                            0 => 'rank-1',
                            1 => 'rank-2',
                            2 => 'rank-3',
                            default => '',
                        };
                    @endphp
                    <tr class="{{ $rankClass }}">
                        <td style="font-weight: bold;">{{ $index + 1 }}</td>
                        <td class="p-name">{{ $data['participant']->nama_sekolah }}</td>
                        <td class="p-coach">{{ $data['participant']->nama_pelatih }}</td>
                        @foreach($assessmentCategories as $cat)
                            @foreach($cat->subCategories as $sub)
                                @foreach($sub->criterias as $crit)
                                    @php
                                        $score = $data['scoreMap']->get($crit->id);
                                        $val = $score ? $score->score : null;
                                    @endphp
                                    <td class="{{ $val === null ? 'empty-score' : '' }}" style="font-weight: {{ $val ? 'bold' : 'normal' }}">{{ $val ?? '-' }}</td>
                                @endforeach
                            @endforeach
                            <td class="col-sub">{{ $data['categoryTotals'][$cat->id] ?? 0 }}</td>
                        @endforeach
                        <td class="col-total">{{ $data['grandTotal'] }}</td>
                        <td class="col-rank">{{ $index + 1 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $eventner->nama_event }} &mdash; Generated by BARIS APP
    </div>

</body>
</html>
