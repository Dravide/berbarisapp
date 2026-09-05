<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran - {{ $eventner->nama_event }}</title>
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

        /* NO + STATUS */
        .meta { display: table; width: 100%; margin-bottom: 14px; }
        .meta .no { display: table-cell; vertical-align: middle; }
        .meta .no-label { font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .meta .no-val { font-size: 13px; font-weight: bold; color: #1a1a2e; }
        .meta .status { display: table-cell; text-align: right; vertical-align: middle; }
        .badge-lunas { display: inline-block; background: #16a34a; color: #fff; font-size: 11px; font-weight: bold; padding: 5px 14px; border-radius: 4px; letter-spacing: 1px; }

        /* DATA PEMBAYAR */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { padding: 4px 8px; border: 1px solid #ddd; font-size: 9px; }
        .info .lbl { background: #f5f6fa; font-weight: bold; color: #555; width: 130px; }
        .info .val { color: #1a1a2e; font-weight: bold; }

        /* TABEL BIAYA */
        table.biaya { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.biaya th { background: #f8f9fa; padding: 5px 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; color: #888; text-align: left; border: 1px solid #ddd; }
        table.biaya td { padding: 6px 8px; border: 1px solid #ddd; font-size: 10px; }
        table.biaya .amount { text-align: right; }
        table.biaya tr.total td { background: #1a1a2e; color: #fff; font-weight: bold; font-size: 11px; }
        table.biaya tr.total .amount { font-size: 12px; }

        /* TTD */
        .ttd { margin-top: 28px; }
        .ttd table { width: 100%; border: none; }
        .ttd td { border: none; }
        .ttd .role { font-weight: bold; }
        .ttd .name { font-weight: bold; font-size: 9px; margin: 0; }

        /* FOOTER */
        .foot { margin-top: 18px; padding-top: 6px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }

        /* PAGE FLOW */
        table.biaya tr { page-break-inside: avoid; }
        .ttd { page-break-inside: avoid; }
    </style>
</head>
<body>

    @php
        // Semua kwitansi sekolah ini (1 NPSN, bisa multi pasukan).
        $primary = $registrations->first();
        $totalFee = $registrations->sum(fn ($r) => (float) $r->total_fee);
        $verifiedAt = $registrations->filter(fn ($r) => $r->payment_verified_at)
            ->sortBy('payment_verified_at')->last()->payment_verified_at ?? now();
        $invoiceNo = 'INV-' . str_pad($primary->id, 4, '0', STR_PAD_LEFT)
            . '/' . \Carbon\Carbon::parse($verifiedAt)->format('mY');
        $verifiedDate = \Carbon\Carbon::parse($verifiedAt)->translatedFormat('d F Y, H:i') . ' WIB';
        $bankAccount = $registrations->filter(fn ($r) => $r->paymentBankAccount)->first()?->paymentBankAccount;
    @endphp

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

    <div class="judul">Kwitansi Pembayaran</div>
    <div class="subjudul">Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB</div>

    <div class="meta">
        <div class="no">
            <span class="no-label">Nomor Kwitansi</span><br>
            <span class="no-val">{{ $invoiceNo }}</span>
        </div>
        <div class="status">
            <span class="badge-lunas">LUNAS</span>
        </div>
    </div>

    <table class="info">
        <tr>
            <td class="lbl">Sekolah</td>
            <td class="val">{{ $primary->nama_sekolah }}</td>
            <td class="lbl">NPSN</td>
            <td>{{ $primary->npsn }}</td>
        </tr>
        <tr>
            <td class="lbl">Pelatih</td>
            <td>{{ $primary->nama_pelatih ?? '-' }}</td>
            <td class="lbl">Diverifikasi</td>
            <td>{{ $verifiedDate }}</td>
        </tr>
        @if($bankAccount)
        <tr>
            <td class="lbl">Pembayaran via</td>
            <td colspan="3">{{ $bankAccount->bank_name }} &mdash; {{ $bankAccount->account_number }} a.n. {{ $bankAccount->account_name }}</td>
        </tr>
        @endif
    </table>

    <table class="biaya">
        <thead>
            <tr>
                <th width="70%">Uraian</th>
                <th width="30%" style="text-align:right;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registrations as $reg)
                <tr>
                    <td>
                        Biaya Pendaftaran {{ $reg->competitionCategory?->full_name ?? '' }}
                        @if($reg->label_pasukan)
                            &mdash; Pasukan {{ $reg->label_pasukan }}
                        @endif
                    </td>
                    <td class="amount">Rp {{ number_format((float) $reg->total_fee, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>TOTAL ({{ $registrations->count() }} pasukan)</td>
                <td class="amount">Rp {{ number_format($totalFee, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TANDA TANGAN --}}
    <div class="ttd">
        <table>
            <tr>
                <td style="text-align:center; width:50%; vertical-align:bottom; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Penerima Pembayaran</div>
                    @php
                        $signaturePath = $eventner->activeSignature && $eventner->activeSignature->image
                            ? public_path('storage/' . $eventner->activeSignature->image) : null;
                        $signatureExists = $signaturePath && file_exists($signaturePath);
                    @endphp
                    @if($signatureExists)
                        <img src="{{ $signaturePath }}" style="height:90px; max-width:200px; object-fit:contain;">
                    @else
                        <div style="height:90px;"></div>
                    @endif
                </td>
                <td style="text-align:center; width:50%; vertical-align:bottom; padding-top:10px;">
                    <div class="role" style="margin-bottom:8px;">Ketua Pelaksana</div>
                    @php
                        $qrData = event_url($eventner, 'detail');
                        $qrImage = (new \chillerlan\QRCode\QRCode)->render($qrData);
                    @endphp
                    <img src="{{ $qrImage }}" style="width:76px; height:76px;">
                    <p style="font-size:7px; color:#888; margin:2px 0 0;">Scan untuk info &amp; verifikasi event</p>
                    <p class="name" style="margin-top:6px;">{{ $eventner->diselenggarakan_oleh }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="foot">
        Kwitansi ini sah tanpa tanda tangan basah &mdash; {{ $eventner->nama_event }} &mdash; Generated by BARIS APP
    </div>

</body>
</html>
