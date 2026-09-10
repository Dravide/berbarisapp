<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tiket {{ $ticket->order_code }}</title>
    <style>
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-Regular.ttf') }}');
        }
        @font-face {
            font-family: 'PJ';
            src: url('{{ public_path('fonts/PlusJakartaSans-Bold.ttf') }}');
            font-weight: bold;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'PJ', sans-serif;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }
        .ticket { border: 2px solid #0062FF; border-radius: 10px; padding: 18px; }
        .head {
            background: #0062FF;
            color: #fff;
            border-radius: 6px;
            padding: 14px;
            text-align: center;
            margin-bottom: 16px;
        }
        .head h1 { margin: 0; font-size: 16px; font-weight: bold; }
        .head p { margin: 4px 0 0; font-size: 10px; opacity: .9; }
        .qr { text-align: center; margin: 0 0 14px; }
        .qr img { width: 190px; height: 190px; }
        .qr p { font-size: 9px; color: #6b7280; margin: 6px 0 0; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        td { padding: 6px 0; vertical-align: top; }
        .label { color: #6b7280; }
        .value { text-align: right; font-weight: bold; }
        .code { font-family: 'Courier New', monospace; letter-spacing: .5px; }
        .row { border-bottom: 1px solid #e5e7eb; }
        .foot {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px dashed #9ca3af;
            font-size: 8.5px;
            color: #6b7280;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="head">
            <h1>TIKET MASUK</h1>
            <p>{{ $eventner->nama_event }}</p>
        </div>

        <div class="qr">
            <img src="{{ $qrPath }}" alt="QR Tiket">
            <p>Tunjukkan QR ini kepada panitia di gerbang masuk</p>
        </div>

        <table>
            <tr class="row">
                <td class="label">Kode Order</td>
                <td class="value code">{{ $ticket->order_code }}</td>
            </tr>
            <tr class="row">
                <td class="label">Nama Pembeli</td>
                <td class="value">{{ $ticket->buyer_name }}</td>
            </tr>
            <tr class="row">
                <td class="label">Jumlah Tiket</td>
                <td class="value">{{ $ticket->quantity }} tiket</td>
            </tr>
            @if($eventner->tanggal)
                <tr class="row">
                    <td class="label">Tanggal Event</td>
                    <td class="value">{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</td>
                </tr>
            @endif
            @if($eventner->lokasi)
                <tr class="row">
                    <td class="label">Lokasi</td>
                    <td class="value">{{ $eventner->lokasi }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">Status</td>
                <td class="value">{{ $ticket->status === 'CHECKED_IN' ? 'SUDAH CHECK-IN' : 'BELUM CHECK-IN' }}</td>
            </tr>
        </table>

        <div class="foot">
            Tiket ini hanya berlaku untuk {{ $ticket->quantity }} orang. Satu QR hanya bisa di-scan sekali.
            Simpan file ini di ponsel Anda sebagai cadangan bila QR tidak sempat dimuat.
        </div>
    </div>
</body>
</html>
