<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan - {{ $eventner->nama_event }}</title>
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
        .summary-card-4 { background: #fff8e6; color: #b8860b; }
        .summary-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #666; margin-bottom: 4px; }
        .summary-val { font-size: 14px; font-weight: bold; }

        /* SECTION */
        .section-title { background: #2c3e50; color: #fff; padding: 6px 12px; border-radius: 4px 4px 0 0; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0; page-break-after: avoid; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th { background: #f8f9fa; padding: 5px 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.data th.num-col { text-align: right; }
        table.data th.center-col { text-align: center; }
        table.data td { padding: 5px 8px; border: 1px solid #ddd; font-size: 8px; vertical-align: middle; }
        table.data td.center { text-align: center; }
        table.data td.num { text-align: right; font-weight: bold; }
        table.data td.name-col { font-weight: bold; color: #1a1a2e; }
        table.data .money-in { color: #198754; }
        table.data .money-muted { color: #888; }
        table.data .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        table.data .badge-paid { background: #e8f7f0; color: #198754; }
        table.data .badge-pending { background: #fff8e6; color: #b8860b; }
        table.data .badge-unpaid { background: #fdecea; color: #c0392b; }

        .grand-row td { background: #f8f9fa; font-weight: bold; font-size: 9px; }

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

    <div class="judul">Laporan Keuangan</div>
    <div class="subjudul">
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    {{-- Ringkasan Pendapatan --}}
    <table class="summary-box">
        <tr>
            <td style="width: 25%;">
                <div class="summary-card summary-card-1">
                    <div class="summary-label">Total Pendapatan</div>
                    <div class="summary-val">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card summary-card-2">
                    <div class="summary-label">Biaya Pendaftaran</div>
                    <div class="summary-val">Rp {{ number_format($feeRevenue, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card summary-card-3">
                    <div class="summary-label">Voting</div>
                    <div class="summary-val">Rp {{ number_format($voteRevenue, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card summary-card-4">
                    <div class="summary-label">Tiket</div>
                    <div class="summary-val">Rp {{ number_format($ticketRevenue, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Pendapatan per Kategori --}}
    <div class="section-title">Pendapatan per Kategori Lomba</div>
    <table class="data">
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="center-col" style="width: 60px;">Biaya/Pasukan</th>
                <th class="center-col" style="width: 50px;">Daftar</th>
                <th class="center-col" style="width: 45px;">Lunas</th>
                <th class="center-col" style="width: 55px;">Menunggu</th>
                <th class="center-col" style="width: 50px;">Belum</th>
                <th class="num-col" style="width: 95px;">Terkumpul</th>
                <th class="num-col" style="width: 85px;">Potensi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categoryBreakdown as $cat)
                <tr>
                    <td class="name-col">{{ $cat['name'] }}</td>
                    <td class="center">{{ $cat['fee'] ? 'Rp ' . number_format($cat['fee'], 0, ',', '.') : 'Gratis' }}</td>
                    <td class="center">{{ $cat['total_registrations'] }}</td>
                    <td class="center">{{ $cat['paid_count'] }}</td>
                    <td class="center">{{ $cat['pending_count'] }}</td>
                    <td class="center">{{ $cat['unpaid_count'] }}</td>
                    <td class="num money-in">Rp {{ number_format($cat['paid_revenue'], 0, ',', '.') }}</td>
                    <td class="num money-muted">Rp {{ number_format($cat['potential_revenue'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center" style="padding: 10px 0; color: #888;">Belum ada kategori lomba.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Detail Pembayaran --}}
    <div class="section-title">Detail Pembayaran Peserta</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th>Nama Sekolah / Kontingen</th>
                <th>Kategori</th>
                <th class="center-col" style="width: 75px;">Status</th>
                <th class="num-col" style="width: 85px;">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paymentDetails as $i => $reg)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="name-col">{{ $reg->display_name }}</td>
                    <td>{{ $reg->competitionCategory?->full_name }}</td>
                    <td class="center">
                        @if($reg->payment_status === 'paid')
                            <span class="badge badge-paid">Lunas</span>
                        @elseif($reg->payment_status === 'pending_verification')
                            <span class="badge badge-pending">Menunggu</span>
                        @else
                            <span class="badge badge-unpaid">Belum Bayar</span>
                        @endif
                    </td>
                    <td class="num">Rp {{ number_format($reg->total_fee, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center" style="padding: 10px 0; color: #888;">Belum ada data pembayaran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

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
