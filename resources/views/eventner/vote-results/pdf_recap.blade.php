<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Hasil Voting - {{ $eventner->nama_event }}</title>
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

        /* SUMMARY ROW */
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-box td { border: none; padding: 0 5px; }
        .summary-card { padding: 10px; border-radius: 6px; text-align: center; }
        .summary-card-1 { background: #e8f4fd; color: #0d6efd; }
        .summary-card-2 { background: #e8f7f0; color: #198754; }
        .summary-card-3 { background: #eef9fa; color: #0dcaf0; }
        .summary-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #666; margin-bottom: 4px; }
        .summary-val { font-size: 14px; font-weight: bold; }

        /* CATEGORY SECTION */
        .category-section { margin-bottom: 20px; page-break-inside: avoid; }
        .category-header { background: #2c3e50; color: #fff; padding: 6px 12px; border-radius: 4px 4px 0 0; }
        .category-header h3 { font-size: 10px; margin: 0; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }

        /* RANKING TABLE */
        table.rank { width: 100%; border-collapse: collapse; }
        table.rank th { background: #f8f9fa; padding: 5px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.rank th.num-col { text-align: right; }
        table.rank td { padding: 5px 8px; border: 1px solid #ddd; font-size: 8px; vertical-align: middle; }
        table.rank .rank-col { text-align: center; width: 35px; font-weight: bold; }
        table.rank .name-col { font-weight: bold; color: #1a1a2e; }
        table.rank .val-col { text-align: right; font-weight: bold; }
        table.rank .num-val { color: #0d6efd; font-size: 9px; }
        table.rank .money-val { color: #198754; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* TTD */
        .ttd { margin-top: 24px; page-break-inside: avoid; }
        .ttd table { width: 100%; border: none; }
        .ttd td { text-align: center; padding-top: 20px; width: 50%; vertical-align: top; border: none; }
        .ttd .role { font-weight: bold; margin-bottom: 8px; }
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

    <div class="judul">Rekap Hasil Voting Digital</div>
    <div class="subjudul">
        Nilai Konversi: 1 Vote = Rp {{ number_format($pricePerVote, 0, ',', '.') }} &bull;
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    {{-- Ringkasan Global --}}
    @if($summary)
        <table class="summary-box">
            <tr>
                <td style="width: 33%;">
                    <div class="summary-card summary-card-1">
                        <div class="summary-label">Total Transaksi (PAID)</div>
                        <div class="summary-val">{{ number_format($summary->trx_count) }}</div>
                    </div>
                </td>
                <td style="width: 34%;">
                    <div class="summary-card summary-card-2">
                        <div class="summary-label">Total Vote Masuk</div>
                        <div class="summary-val">{{ number_format($summary->total_votes) }}</div>
                    </div>
                </td>
                <td style="width: 33%;">
                    <div class="summary-card summary-card-3">
                        <div class="summary-label">Total Pendapatan</div>
                        <div class="summary-val">Rp {{ number_format($summary->total_amount, 0, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif

    {{-- Tabel Klasemen per Kategori --}}
    @forelse($categories as $category)
        @php
            $rankingData = $rankings[$category->id] ?? collect();
        @endphp
        <div class="category-section">
            <div class="category-header">
                <h3>Kategori Lomba: {{ $category->name }}</h3>
            </div>
            <table class="rank">
                <thead>
                    <tr>
                        <th style="text-align:center; width: 35px;">Rank</th>
                        <th>Nama Sekolah / Kontingen</th>
                        <th>Danton</th>
                        <th class="num-col" style="width: 100px;">Total Vote</th>
                        <th class="num-col" style="width: 120px;">Estimasi Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingData as $idx => $reg)
                        <tr>
                            <td class="rank-col">{{ $idx + 1 }}</td>
                            <td class="name-col">{{ $reg->display_name }}</td>
                            <td>{{ $reg->danton_nama ?: '-' }}</td>
                            <td class="val-col num-val">{{ number_format($reg->total_votes ?: 0, 0, ',', '.') }}</td>
                            <td class="val-col money-val">Rp {{ number_format(($reg->total_votes ?: 0) * $pricePerVote, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 10px 0;">Belum ada data voting masuk untuk kategori ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <div class="text-center text-muted" style="padding: 20px 0; border: 1px dashed #ccc; border-radius: 4px;">
            Belum ada Kategori Lomba terdaftar.
        </div>
    @endforelse

    {{-- Tanda Tangan --}}
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
        {{ $eventner->nama_event }} &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by BARIS APP
    </div>

</body>
</html>
