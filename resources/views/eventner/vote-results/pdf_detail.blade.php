<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Voting - {{ $registration->nama_sekolah }}</title>
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

        /* KONTINGEN CARD */
        .kontingen { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .kontingen td { border: none; padding: 4px 0; vertical-align: top; }
        .kontingen .label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #888; display: block; }
        .kontingen .value { font-size: 11px; font-weight: bold; color: #1a1a2e; }

        /* SUMMARY ROW */
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-box td { border: none; padding: 0 5px; }
        .summary-card { padding: 10px; border-radius: 6px; text-align: center; }
        .summary-card-1 { background: #e8f4fd; color: #0d6efd; }
        .summary-card-2 { background: #e8f7f0; color: #198754; }
        .summary-card-3 { background: #eef9fa; color: #0dcaf0; }
        .summary-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #666; margin-bottom: 4px; }
        .summary-val { font-size: 14px; font-weight: bold; }

        /* CATATAN TRANSPARANSI */
        .note-box { border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; margin-bottom: 20px; font-size: 8px; color: #555; background: #fdfdfd; }
        .note-box b { color: #1a1a2e; }

        /* VOTER TABLE */
        .voter-section-title { background: #2c3e50; color: #fff; padding: 6px 12px; border-radius: 4px 4px 0 0; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        table.voters { width: 100%; border-collapse: collapse; }
        table.voters th { background: #f8f9fa; padding: 5px 6px; font-size: 7px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.voters td { padding: 5px 6px; border: 1px solid #ddd; font-size: 8px; vertical-align: middle; }
        table.voters .c { text-align: center; }
        table.voters .r { text-align: right; }
        table.voters .mono { font-family: monospace; font-size: 7.5px; color: #444; }
        table.voters .vote-val { font-weight: bold; color: #198754; }
        table.voters .money-val { font-weight: bold; color: #1a1a2e; }
        table.voters tr { page-break-inside: avoid; }
        thead { display: table-header-group; }

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

    <div class="judul">Detail Transparansi Voting Kontingen</div>
    <div class="subjudul">
        Nilai Konversi: 1 Vote = Rp {{ number_format($pricePerVote, 0, ',', '.') }} &bull;
        Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
    </div>

    {{-- Info Kontingen --}}
    <table class="kontingen">
        <tr>
            <td style="width: 40%;">
                <span class="label">Nama Sekolah / Kontingen</span>
                <span class="value">{{ $registration->nama_sekolah }}</span>
            </td>
            <td style="width: 30%;">
                <span class="label">Kategori Lomba</span>
                <span class="value">{{ $registration->competitionCategory?->parent?->name ? $registration->competitionCategory->parent->name . ' — ' . $registration->competitionCategory->name : $registration->competitionCategory?->name ?? '-' }}</span>
            </td>
            <td style="width: 15%;">
                <span class="label">NPSN</span>
                <span class="value">{{ $registration->npsn ?: '-' }}</span>
            </td>
            <td style="width: 15%;">
                <span class="label">Danton</span>
                <span class="value">{{ $registration->danton_nama ?: '-' }}</span>
            </td>
        </tr>
    </table>

    {{-- Ringkasan --}}
    @if($summary)
        <table class="summary-box">
            <tr>
                <td style="width: 33%;">
                    <div class="summary-card summary-card-1">
                        <div class="summary-label">Transaksi PAID</div>
                        <div class="summary-val">{{ number_format($summary->trx_count) }}</div>
                    </div>
                </td>
                <td style="width: 34%;">
                    <div class="summary-card summary-card-2">
                        <div class="summary-label">Total Vote Sah</div>
                        <div class="summary-val">{{ number_format($summary->total_votes, 0, ',', '.') }}</div>
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

    {{-- Catatan Transparansi --}}
    <div class="note-box">
        <b>Catatan Transparansi:</b>
        Dokumen ini memuat seluruh transaksi voting berstatus PAID (terbayar &amp; terverifikasi) untuk kontingen di atas,
        termasuk identitas voter, jumlah vote, nominal, ID transaksi payment gateway, dan waktu pembayaran.
        @if($summary && $summary->first_paid_at)
            Periode vote masuk: {{ $summary->first_paid_at?->translatedFormat('d M Y H:i') }} s.d. {{ $summary->last_paid_at?->translatedFormat('d M Y H:i') }} WIB.
        @endif
        @if($invalid && $invalid->isNotEmpty())
            @foreach($invalid as $st => $cnt)
                Terdapat {{ $cnt }} transaksi berstatus {{ $st }} (tidak dihitung dalam vote).
            @endforeach
        @endif
        Setiap transaksi dapat diverifikasi ulang panitia melalui ID transaksi pada payment gateway (AutoGoPay QRIS).
    </div>

    {{-- Tabel Voter --}}
    <div class="voter-section-title">Rincian Transaksi Voting ({{ number_format($voters->count()) }} transaksi)</div>
    <table class="voters">
        <thead>
            <tr>
                <th style="text-align:center; width: 22px;">No</th>
                <th style="width: 110px;">Nama Voter</th>
                <th style="width: 120px;">Email</th>
                <th style="text-align:center; width: 40px;">Vote</th>
                <th style="text-align:right; width: 70px;">Nominal</th>
                <th style="width: 105px;">ID Transaksi</th>
                <th style="width: 85px;">Waktu Bayar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($voters as $idx => $v)
                <tr>
                    <td class="c">{{ $idx + 1 }}</td>
                    <td>{{ $v->voter_name ?: '-' }}</td>
                    <td style="font-size: 7.5px;">{{ $v->voter_email ?: '-' }}</td>
                    <td class="c vote-val">{{ number_format($v->votes_earned) }}</td>
                    <td class="r money-val">Rp {{ number_format($v->amount, 0, ',', '.') }}</td>
                    <td class="mono">{{ $v->autogopay_transaction_id }}</td>
                    <td style="font-size: 7.5px;">{{ $v->paid_at?->translatedFormat('d M H:i') ?? '-' }}</td>
                </tr>
                @if($v->comment)
                    <tr>
                        <td></td>
                        <td colspan="6" style="font-size: 7px; color: #777; font-style: italic;">Pesan: "{{ $v->comment }}"</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7" class="c" style="padding: 20px; color: #888;">Belum ada transaksi voting PAID untuk kontingen ini.</td>
                </tr>
            @endforelse
            @if($voters->isNotEmpty())
                <tr>
                    <td colspan="3" style="text-align:right; font-weight: bold; background: #f8f9fa;">TOTAL ({{ number_format($voters->count()) }} transaksi)</td>
                    <td class="c vote-val" style="background: #f8f9fa;">{{ number_format($summary->total_votes, 0, ',', '.') }}</td>
                    <td class="r money-val" style="background: #f8f9fa;">Rp {{ number_format($summary->total_amount, 0, ',', '.') }}</td>
                    <td colspan="2" style="background: #f8f9fa;"></td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Tanda Tangan --}}
    <div class="ttd">
        <table>
            <tr>
                <td style="text-align:center; width:50%; vertical-align:top; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Ketua Panitia</div>
                    <br><br><br>
                    <span class="line"></span><br>
                    <div style="margin-top:4px; font-weight:bold; font-size:9px;">{{ $eventner->diselenggarakan_oleh }}</div>
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
        {{ $eventner->nama_event }} &mdash; Detail Voting {{ $registration->nama_sekolah }} &mdash; Dicetak {{ now()->translatedFormat('d M Y H:i') }} &mdash; Generated by BARIS APP
    </div>
</body>
</html>
