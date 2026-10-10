<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ app_name() }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f6f8;
            color: #1f2430;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .kartu {
            background: #ffffff;
            border: 1px solid #e3e5ea;
            border-radius: 16px;
            max-width: 460px;
            width: 100%;
            padding: 48px 40px;
            text-align: center;
        }
        .kode-utama { font-size: 64px; font-weight: 800; letter-spacing: -2px; line-height: 1; margin-bottom: 12px; }
        .judul { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .teks { font-size: 15px; color: #6b7280; line-height: 1.55; }
        .tombol-wrap { margin-top: 28px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .tombol {
            display: inline-block; padding: 11px 24px; border-radius: 10px;
            font-size: 14px; font-weight: 600; text-decoration: none;
            border: 1px solid transparent; cursor: pointer;
        }
        .tombol-utama { background: #0f6bde; color: #ffffff; }
        .tombol-utama:hover { background: #0d5cba; color: #ffffff; }
        .tombol-netral { background: #ffffff; color: #374151; border-color: #d5d8de; }
        .tombol-netral:hover { background: #f3f4f6; color: #1f2430; }
        .kotak-kode {
            margin: 24px auto 10px;
            padding: 18px 20px;
            border: 1.5px dashed #e2a1a1;
            background: #fdf3f3;
            border-radius: 12px;
            max-width: 300px;
        }
        .kotak-kode .label { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; color: #b04a4a; text-transform: uppercase; }
        .kotak-kode .nilai {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
            font-size: 30px; font-weight: 700; color: #8f2d2d; margin-top: 6px;
            letter-spacing: 1px;
        }
        .serius .kode-utama { color: #c0392b; }
        .serius .kotak-aksen { border-top: 4px solid #c0392b; }
    </style>
</head>
<body>
    <main class="kartu @yield('kelas')" role="main">
        @yield('isi')
    </main>
</body>
</html>
