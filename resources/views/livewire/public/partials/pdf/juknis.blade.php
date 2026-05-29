<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Juknis {{ $eventner->nama_event }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #2665fd; }
        .header img { max-height: 50px; margin-bottom: 8px; }
        .header h1 { font-size: 16px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #666; }
        .category { margin-bottom: 16px; page-break-inside: avoid; }
        .category-title { background: #2665fd; color: #fff; padding: 8px 12px; font-weight: 700; font-size: 12px; border-radius: 4px 4px 0 0; }
        .criteria-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .criteria-table th { background: #e8eaff; color: #2665fd; padding: 6px 8px; text-align: left; font-weight: 600; border: 1px solid #dde; }
        .criteria-table td { padding: 5px 8px; border: 1px solid #e0e0e0; vertical-align: top; }
        .criteria-table tr:nth-child(even) td { background: #fafbff; }
        .sub-header { background: #f0f4ff; padding: 6px 8px; font-weight: 700; color: #1a1a2e; border: 1px solid #dde; }
        .score-options { font-size: 9px; color: #555; margin-top: 2px; }
        .footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #ddd; font-size: 9px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if($eventner->logo_event)
            <img src="{{ public_path('storage/' . $eventner->logo_event) }}" alt="Logo">
        @endif
        <h1>{{ $eventner->nama_event }}</h1>
        <p>{{ $eventner->diselenggarakan_oleh }} &bull; {{ $eventner->tanggal ? \Carbon\Carbon::parse($eventner->tanggal)->format('d M Y') : '' }}</p>
    </div>

    <h2 style="font-size: 13px; font-weight: 700; color: #1a1a2e; margin-bottom: 10px;">PETUNJUK PENILAIAN (JUKNIS)</h2>

    @foreach($categories as $cat)
    <div class="category">
        <div class="category-title">{{ $cat->name }}</div>
        <table class="criteria-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Aspek Penilaian</th>
                    <th style="width: 55%;">Skor</th>
                    <th style="width: 15%;">Bobot</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cat->subCategories as $subIdx => $sub)
                    <tr>
                        <td colspan="4" class="sub-header">{{ $sub->name }}</td>
                    </tr>
                    @foreach($sub->criterias as $critIdx => $crit)
                    <tr>
                        <td style="text-align: center;">{{ $critIdx + 1 }}</td>
                        <td><strong>{{ $crit->name }}</strong></td>
                        <td>
                            {{ $crit->score_options }}
                            <div class="score-options">{{ $crit->score_options }}</div>
                        </td>
                        <td style="text-align: center;">{{ $crit->weight }}%</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        Dicetak: {{ now()->format('d/m/Y H:i') }} &bull; {{ config('app.name') }}
    </div>
</body>
</html>
