<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Juara {{ $eventner->nama_event }}</title>
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

        /* CHAMPION SECTION */
        .champ-section { margin-bottom: 16px; page-break-inside: avoid; }
        .champ-header { background: #2c3e50; color: #fff; padding: 8px 12px; border-radius: 4px 4px 0 0; }
        .champ-header h3 { font-size: 12px; margin: 0; font-weight: bold; }
        .champ-badges { padding: 6px 12px; background: #ecf0f1; border-left: 2px solid #3498db; border-right: 2px solid #3498db; }
        .champ-badge { display: inline-block; background: #3498db; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 7px; font-weight: bold; margin-right: 3px; }

        /* RANKING TABLE */
        table.rank { width: 100%; border-collapse: collapse; }
        table.rank th { background: #f8f9fa; padding: 5px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.rank th:last-child { text-align: right; }
        table.rank td { padding: 6px 8px; border: 1px solid #ddd; font-size: 9px; vertical-align: middle; }
        table.rank .rank-col { text-align: center; width: 45px; font-weight: bold; }
        table.rank .name-col { font-weight: bold; color: #1a1a2e; }
        table.rank .score-col { text-align: right; font-weight: bold; font-size: 12px; }

        /* MEDAL ROWS */
        table.rank .gold { background: #fff8e1; }
        table.rank .silver { background: #f5f5f5; }
        table.rank .bronze { background: #fbe9e7; }

        .medal { font-size: 14px; }

        /* PODIUM (Top 3 Highlight) */
        .podium { margin-bottom: 16px; }
        .podium table { width: 100%; border-collapse: collapse; }
        .podium td { text-align: center; vertical-align: bottom; padding: 0 4px; border: none; }
        .podium-box { border-radius: 6px 6px 0 0; padding: 10px 6px; color: #fff; }
        .podium-1 { background: #f39c12; min-height: 90px; }
        .podium-2 { background: #95a5a6; min-height: 70px; }
        .podium-3 { background: #e67e22; min-height: 55px; }
        .podium-rank { font-size: 20px; font-weight: bold; }
        .podium-name { font-size: 8px; font-weight: bold; margin-top: 4px; }
        .podium-score { font-size: 10px; font-weight: bold; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* TTD */
        .ttd { margin-top: 24px; }
        .ttd table { width: 100%; border: none; }
        .ttd td { text-align: center; padding-top: 40px; width: 50%; vertical-align: top; border: none; }
        .ttd .role { font-weight: bold; }
        .ttd .line { display: inline-block; width: 130px; border-top: 1px solid #333; }
    </style>
</head>
<body>

    {{-- KOP --}}
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

    <div class="judul">Rekap Hasil Juara</div>
    <div class="subjudul">
        @if($competitionCategory)
            Kategori Lomba: {{ $competitionCategory->name }} &bull;
        @endif
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    @foreach($championCategories as $champion)
        @php
            $rankingData = $rankings[$champion->id] ?? [];
        @endphp
        <div class="champ-section">
            {{-- Champion Header --}}
            <div class="champ-header">
                <h3><span style="color:#f1c40f;">&#9813;</span> {{ $champion->name }}</h3>
            </div>
            <div class="champ-badges">
                @foreach($champion->assessmentCategories as $ac)
                    <span class="champ-badge">{{ $ac->name }}</span>
                @endforeach
            </div>

            {{-- Podium for Top 3 --}}
            @if(count($rankingData) >= 3)
                <div class="podium">
                    <table>
                        <tr>
                            {{-- 2nd Place --}}
                            <td style="width:33%;">
                                <div class="podium-box podium-2">
                                    <div class="podium-rank">2</div>
                                    <div class="podium-name">{{ $rankingData[1]['participant']->nama_sekolah }}</div>
                                    <div class="podium-score">{{ $rankingData[1]['total'] }}</div>
                                </div>
                            </td>
                            {{-- 1st Place --}}
                            <td style="width:34%;">
                                <div class="podium-box podium-1">
                                    <div class="podium-rank">1</div>
                                    <div class="podium-name">{{ $rankingData[0]['participant']->nama_sekolah }}</div>
                                    <div class="podium-score">{{ $rankingData[0]['total'] }}</div>
                                </div>
                            </td>
                            {{-- 3rd Place --}}
                            <td style="width:33%;">
                                <div class="podium-box podium-3">
                                    <div class="podium-rank">3</div>
                                    <div class="podium-name">{{ $rankingData[2]['participant']->nama_sekolah }}</div>
                                    <div class="podium-score">{{ $rankingData[2]['total'] }}</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

            {{-- Full Ranking Table --}}
            <table class="rank">
                <thead>
                    <tr>
                        <th style="text-align:center;">Rank</th>
                        <th>Peserta</th>
                        <th>Pelatih</th>
                        <th style="text-align:right;">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rankingData as $ps)
                        @php
                            $rowClass = '';
                            if ($ps['rank'] == 1) $rowClass = 'gold';
                            elseif ($ps['rank'] == 2) $rowClass = 'silver';
                            elseif ($ps['rank'] == 3) $rowClass = 'bronze';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="rank-col">
                                @if($ps['rank'] == 1)
                                    <span class="medal">&#x1F947;</span>
                                @elseif($ps['rank'] == 2)
                                    <span class="medal">&#x1F948;</span>
                                @elseif($ps['rank'] == 3)
                                    <span class="medal">&#x1F949;</span>
                                @else
                                    {{ $ps['rank'] }}
                                @endif
                            </td>
                            <td class="name-col">{{ $ps['participant']->nama_sekolah }}</td>
                            <td>{{ $ps['participant']->nama_pelatih }}</td>
                            <td class="score-col">{{ $ps['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- Tanda Tangan --}}
    @php
        use chillerlan\QRCode\QRCode;
        $qrData = url('/event/' . $eventner->slug);
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
                    <div class="role" style="margin-bottom:8px;">Sekretaris Panitia</div>
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
