<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rundown Acara - {{ $eventner->nama_event }}</title>
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
        .table-rundown {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table-rundown th {
            background-color: #1a1a2e;
            color: #fff;
            padding: 10px 14px;
            font-weight: bold;
            border: 1px solid #1a1a2e;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
        }
        .table-rundown td {
            padding: 10px 14px;
            border: 1px solid #ddd;
            vertical-align: middle;
            font-size: 13px;
        }
        .table-rundown .center {
            text-align: center;
        }
        .time-badge {
            display: inline-block;
            background-color: #e8ecfb;
            color: #1a1a2e;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }
        .source-tag {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .source-tag.undian {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .desc {
            font-size: 11px;
            color: #777;
            display: block;
            margin-top: 2px;
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
            .table-rundown th {
                background-color: #1a1a2e !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table-rundown tr {
                page-break-inside: avoid;
            }
            thead {
                display: table-header-group;
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
                @if($eventner->tanggal)
                    <p class="kop-sub">
                        {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}
                        @if($eventner->venue) — {{ $eventner->venue }} @endif
                    </p>
                @endif
            </div>
        </div>

        <!-- TITLE -->
        <div class="title">Rundown Acara</div>

        <!-- TABLE -->
        <table class="table-rundown">
            <thead>
                <tr>
                    <th style="width: 8%; text-align: center;">No.</th>
                    <th style="width: 20%; text-align: center;">Waktu</th>
                    <th style="width: 52%;">Kegiatan</th>
                    <th style="width: 20%; text-align: center;">Durasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rundowns as $index => $item)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">
                            <span class="time-badge">
                                {{ $item->start_time?->format('H:i') }}
                                @if($item->end_time)
                                    &ndash; {{ $item->end_time->format('H:i') }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <strong>{{ $item->title }}</strong>
                            @if($item->source_category_id)
                                <span class="source-tag undian">Undian</span>
                            @endif
                            @if($item->description)
                                <span class="desc">{{ $item->description }}</span>
                            @endif
                        </td>
                        <td class="center">
                            @if($item->duration_minutes)
                                {{ $item->duration_minutes }} menit
                            @else
                                &mdash;
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- SIGNATURE -->
        <div class="signature-section">
            <div class="signature-box">
                <p>Panitia Pelaksana,</p>
                <div class="signature-space"></div>
                <p class="signature-name">Sekretariat Panitia</p>
                <p style="font-size: 11px; margin-top: 4px; color: #666;">{{ $eventner->diselenggarakan_oleh }}</p>
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
