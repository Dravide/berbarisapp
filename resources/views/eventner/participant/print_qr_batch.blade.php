<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Peserta</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #222;
            line-height: 1.2;
        }
        .head {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .head h2 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .head p {
            margin: 2px 0 0 0;
            color: #555;
            font-size: 10px;
        }
        table.grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.grid td {
            width: 33.33%;
            height: 62mm;
            vertical-align: middle;
            text-align: center;
            border: 1px solid #ddd;
            padding: 6px;
        }
        .qr-img {
            width: 150px;
            height: 150px;
        }
        .nm {
            font-weight: bold;
            font-size: 11px;
            margin-top: 2px;
        }
        .cat {
            font-size: 9px;
            color: #666;
        }
        .tk {
            font-size: 10px;
            font-weight: bold;
            color: #1d4ed8;
            letter-spacing: 2px;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <div class="head">
        <h2>QR Code Peserta</h2>
        <p>Barcode verifikasi data - {{ $items[0]['category'] ?? '' }}</p>
    </div>

    @php
        $rows = collect($items)->chunk(3);
    @endphp
    @foreach($rows as $row)
        <table class="grid">
            <tr>
                @foreach($row as $item)
                    <td>
                        <img src="{{ $item['qrCode'] }}" class="qr-img">
                        <div class="nm">{{ $item['schoolName'] }}</div>
                        <div class="cat">{{ $item['category'] }}</div>
                        <div class="tk">{{ $item['qrToken'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endforeach

</body>
</html>