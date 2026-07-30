<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR Semua Peserta</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; }
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            page-break-inside: avoid;
        }
        .card img { width: 180px; height: 180px; image-rendering: pixelated; }
        .name { font-size: 14px; font-weight: 700; margin-top: 8px; }
        .cat { font-size: 11px; color: #666; }
        .token { font-size: 12px; font-weight: 700; color: #2563eb; margin-top: 4px; letter-spacing: 2px; }
        @media print {
            body { padding: 0; }
            .grid { gap: 10px; }
            .card { border: 1px solid #ccc; }
        }
        @media print {
            @page { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="grid">
        @foreach($items as $item)
        <div class="card">
            <img src="{{ $item['qrCode'] }}" alt="QR">
            <div class="name">{{ $item['schoolName'] }}</div>
            <div class="cat">{{ $item['category'] }}</div>
            <div class="token">{{ $item['qrToken'] }}</div>
        </div>
        @endforeach
    </div>
    <script>window.print();</script>
</body>
</html>
