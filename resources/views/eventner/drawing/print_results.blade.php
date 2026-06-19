<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urutan Tampil - {{ $category->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #333;
            line-height: 1.5;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }

        /* KOP */
        .kop {
            border-bottom: 3px double #333;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .kop-logo {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            margin-right: 15px;
            object-fit: cover;
        }
        .kop-text {
            flex-grow: 1;
        }
        .kop-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1a1a2e;
            margin: 0;
        }
        .kop-sub {
            font-size: 12px;
            color: #555;
            margin: 4px 0 0 0;
        }

        /* TITLE */
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f8f9fa;
            padding: 8px;
            margin-bottom: 25px;
            border: 1px solid #ddd;
            letter-spacing: 1px;
        }

        /* TABLE */
        .table-results {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table-results th {
            background-color: #1a1a2e;
            color: #fff;
            padding: 10px 14px;
            font-weight: bold;
            border: 1px solid #1a1a2e;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
        }
        .table-results td {
            padding: 10px 14px;
            border: 1px solid #ddd;
            vertical-align: middle;
            font-size: 13px;
        }
        .table-results .center {
            text-align: center;
        }
        .table-results .number-badge {
            display: inline-block;
            background-color: #1a1a2e;
            color: #fff;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 14px;
        }

        /* SIGNATURE */
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-space {
            height: 75px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* PRINT CONFIG */
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100%;
                max-width: none;
                margin: 0;
            }
            .table-results th {
                background-color: #1a1a2e !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table-results .number-badge {
                background-color: #1a1a2e !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* ACTION BAR */
        .action-bar {
            background-color: #f8f9fa;
            border: 1px solid #e3e6f0;
            padding: 12px 20px;
            margin-bottom: 25px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-print {
            background-color: #2e59d9;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover {
            background-color: #224abe;
        }
        .btn-back {
            color: #5a5c69;
            text-decoration: none;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- ACTION BAR -->
    <div class="action-bar no-print">
        <a href="javascript:window.close();" class="btn-back">&larr; Tutup Halaman</a>
        <button onclick="window.print();" class="btn-print">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak / Simpan PDF
        </button>
    </div>

    <div class="container">
        <!-- KOP -->
        <div class="kop">
            @if($eventner->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="kop-logo">
            @endif
            <div class="kop-text">
                <h1 class="kop-title">{{ $eventner->nama_event }}</h1>
                <p class="kop-sub">Diselenggarakan oleh: {{ $eventner->diselenggarakan_oleh }}</p>
            </div>
        </div>

        <!-- TITLE -->
        <div class="title">Daftar Urutan Tampil Peserta</div>

        <div style="margin-bottom: 10px;">
            <strong>Kategori Lomba:</strong> {{ $category->name }}
        </div>

        <!-- TABLE -->
        <table class="table-results">
            <thead>
                <tr>
                    <th style="width: 15%; text-align: center;">No. Urut</th>
                    <th style="width: 60%;">Nama Sekolah / Kontingen</th>
                    <th style="width: 25%; text-align: center;">NPSN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $reg)
                    <tr>
                        <td class="center">
                            <span class="number-badge">{{ $reg->urutan_tampil }}</span>
                        </td>
                        <td><strong>{{ $reg->nama_sekolah }}</strong></td>
                        <td class="center">{{ $reg->npsn }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center" style="padding: 30px; color: #888;">Belum ada urutan tampil yang diundi pada kategori ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- SIGNATURE -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Panitia Pelaksana,</p>
                <div class="signature-space"></div>
                <p class="signature-name">Koordinator Drawing</p>
                <p style="font-size: 11px; margin-top: 4px; color: #666;">BARIS APP Drawing System</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
