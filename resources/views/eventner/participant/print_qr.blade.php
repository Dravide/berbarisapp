<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QR — {{ $schoolName }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .card {
            background: #fff;
            width: 400px;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .qr-img img {
            width: 280px;
            height: 280px;
            image-rendering: pixelated;
        }
        .school-name {
            font-size: 18px;
            font-weight: 700;
            margin-top: 16px;
            color: #1a1a1a;
        }
        .category {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }
        .token {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 4px;
            color: #2563eb;
            margin-top: 12px;
            background: #eff6ff;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
        }
        .instructions {
            font-size: 12px;
            color: #999;
            margin-top: 16px;
            line-height: 1.5;
        }
        @media print {
            body { background: #fff; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="qr-img">
            <img src="{{ $qrCode }}" alt="QR Token">
        </div>
        <div class="school-name">{{ $schoolName }}</div>
        <div class="category">{{ $category }}</div>
        <div class="token">{{ $qrToken }}</div>
        <div class="instructions">
            Scan QR dengan aplikasi Berbaris untuk akses portal sekolah
        </div>
    </div>
</body>
</html>
