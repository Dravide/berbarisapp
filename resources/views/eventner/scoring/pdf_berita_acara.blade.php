<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Dewan Juri — {{ $category->name }}</title>
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
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        /* KOP SURAT */
        .kop {
            border-bottom: 3px double #1a1a1a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 55px; height: 55px; border-radius: 4px; }
        .kop-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .kop-sub { font-size: 9px; color: #444; margin-top: 2px; }

        /* JUDUL DOKUMEN */
        .doc-title { text-align: center; margin: 12px 0 16px; }
        .doc-title h2 { font-size: 13px; font-weight: bold; text-transform: uppercase; margin: 0; letter-spacing: 1px; }
        .doc-title .nomor { font-size: 10px; color: #333; margin-top: 3px; font-weight: bold; }

        .lead-text { font-size: 10px; margin-bottom: 10px; text-align: justify; }

        /* TABEL RESMI */
        .section-label { font-size: 10px; font-weight: bold; text-transform: uppercase; margin: 12px 0 4px; }
        table.tbl-formal { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9.5px; }
        table.tbl-formal th {
            background: #f1f5f9;
            border: 1px solid #334155;
            padding: 5px 6px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            font-size: 8.5px;
        }
        table.tbl-formal td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: middle;
        }
        table.tbl-formal tr.highlight { background: #fefce8; font-weight: bold; }

        /* TANDA TANGAN */
        .ttd-wrapper {
            margin-top: 24px;
            page-break-inside: avoid;
            width: 100%;
        }
        .ttd-table { width: 100%; border: none; }
        .ttd-table td { border: none; text-align: center; vertical-align: top; width: 50%; font-size: 9.5px; }
        .ttd-space { height: 55px; }
        .ttd-name { font-weight: bold; text-decoration: underline; }
        .ttd-role { font-size: 8.5px; color: #555; }
        .ttd-img { max-height: 50px; max-width: 120px; }

        /* LAMPIRAN */
        .page-break { page-break-before: always; }
        .lampiran-head {
            font-size: 8.5px;
            text-align: right;
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 4px;
            margin-bottom: 12px;
            color: #475569;
        }
    </style>
</head>
<body>

    {{-- HALAMAN 1: BERITA ACARA RESMI --}}
    <div class="kop">
        <table>
            <tr>
                <td style="width: 65px;">
                    @if($eventner->logo_event)
                        <img src="{{ public_path('storage/' . $eventner->logo_event) }}" class="kop-logo">
                    @endif
                </td>
                <td style="padding-left: 10px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">{{ $eventner->diselenggarakan_oleh }}</div>
                    @if($eventner->lokasi || $eventner->venue)
                        <div class="kop-sub">{{ $eventner->venue ? $eventner->venue . ', ' : '' }}{{ $eventner->lokasi }}</div>
                    @endif
                </td>
                <td style="text-align: right; font-size: 8px; color: #666; width: 140px;">
                    Tingkat: <b>{{ $category->full_name ?? $category->name }}</b><br>
                    Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB
                </td>
            </tr>
        </table>
    </div>

    <div class="doc-title">
        <h2>BERITA ACARA DEWAN JURI</h2>
        <h2>PENETAPAN HASIL DAN JUARA PERLOMBAAN</h2>
        <div class="nomor">NOMOR: BA/{{ date('Y') }}/{{ strtoupper(substr(md5($category->id . $eventner->id), 0, 6)) }}</div>
    </div>

    <p class="lead-text">
        Pada hari ini, <b>{{ now()->translatedFormat('l') }}</b> tanggal <b>{{ now()->translatedFormat('d F Y') }}</b>, bertempat di <b>{{ $eventner->venue ?: ($eventner->lokasi ?: 'Lokasi Kegiatan') }}</b>, Dewan Juri yang bertugas dalam kejuaraan <b>{{ $eventner->nama_event }}</b> untuk kategori lomba <b>{{ $category->full_name ?? $category->name }}</b>, setelah memeriksa, mengamati, dan merekapitulasi seluruh nilai penampilan peserta secara teliti, independen, dan objektif, dengan ini menetapkan:
    </p>

    <div class="section-label">I. DEWAN JURI PENILAI</div>
    <table class="tbl-formal">
        <thead>
            <tr>
                <th style="width: 28px; text-align: center;">No</th>
                <th>Nama Juri</th>
                <th>Kategori / Penugasan</th>
                <th style="width: 100px; text-align: center;">Paraf / Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($judges as $idx => $judge)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td><b>{{ $judge->name }}</b></td>
                    <td>
                        {{ $judge->assessmentCategories->pluck('name')->join(', ') ?: 'Dewan Juri Umum' }}
                    </td>
                    <td style="text-align: center; height: 26px;">
                        ( .......................... )
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #888; font-style: italic;">Dewan Juri bertugas secara terpadu.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-label">II. HASIL PENETAPAN JUARA</div>
    @foreach($podiums as $podium)
        @if(count($podium['rows']) > 0)
            <div style="font-size: 9px; font-weight: bold; margin: 4px 0 2px; color: #334155;">
                {{ $podium['title'] }}
            </div>
            <table class="tbl-formal">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">Peringkat</th>
                        <th>Nama Kontingen / Sekolah</th>
                        <th>Pelatih / Pembina</th>
                        <th style="width: 75px; text-align: right;">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($podium['rows'] as $r)
                        <tr class="{{ $r['rank'] === 1 ? 'highlight' : '' }}">
                            <td style="text-align: center;"><b>JUARA {{ $r['rank'] }}</b></td>
                            <td><b>{{ $r['participant']->display_name }}</b></td>
                            <td>{{ $r['participant']->nama_pelatih ?? '-' }}</td>
                            <td style="text-align: right;"><b>{{ number_format((float) $r['finalScore'], 2, ',', '.') }}</b></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <p class="lead-text" style="margin-top: 10px;">
        Keputusan Dewan Juri ini bersifat mutlak, sah, dan tidak dapat diganggu gugat. Berita Acara ini dibuat dalam keadaan sadar dan sebenar-benarnya untuk dipergunakan sebagaimana mestinya.
    </p>

    <div class="ttd-wrapper">
        <table class="ttd-table">
            <tr>
                <td>
                    <div class="ttd-role">Ketua Dewan Juri</div>
                    <div class="ttd-space"></div>
                    <div class="ttd-name">{{ $judges->first()?->name ?? 'Ketua Dewan Juri' }}</div>
                    <div class="ttd-role">Koordinator Adjudikasi</div>
                </td>
                <td>
                    <div class="ttd-role">Mengetahui,<br>Ketua Pelaksana / Panitia</div>
                    <div class="ttd-space">
                        @if($signature && $signature->image)
                            <img src="{{ public_path('storage/' . $signature->image) }}" class="ttd-img">
                        @endif
                    </div>
                    <div class="ttd-name">{{ $signature?->name ?? ($eventner->diselenggarakan_oleh ?: 'Ketua Panitia') }}</div>
                    <div class="ttd-role">{{ $eventner->nama_event }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- HALAMAN 2 DST: LAMPIRAN REKAPITULASI RESMI --}}
    <div class="page-break"></div>

    <div class="lampiran-head">
        LAMPIRAN BERITA ACARA NOMOR: BA/{{ date('Y') }}/{{ strtoupper(substr(md5($category->id . $eventner->id), 0, 6)) }} &bull; KATEGORI: {{ strtoupper($category->name) }}
    </div>

    <div class="doc-title" style="margin-bottom: 12px;">
        <h2>LAMPIRAN: REKAPITULASI NILAI AKHIR</h2>
        <div class="nomor">{{ $eventner->nama_event }} &bull; {{ $category->full_name ?? $category->name }}</div>
    </div>

    @foreach($sections as $section)
        <div style="font-size: 10px; font-weight: bold; background: #e2e8f0; padding: 4px 6px; margin: 8px 0 4px; border-left: 3px solid #0f172a;">
            {{ $section['title'] }}
        </div>
        <table class="tbl-formal">
            <thead>
                <tr>
                    <th style="width: 32px; text-align: center;">Rank</th>
                    <th>Nama Kontingen / Sekolah</th>
                    <th>Pelatih</th>
                    <th style="width: 65px; text-align: right;">Total Nilai</th>
                    <th style="width: 65px; text-align: right;">Pengurangan</th>
                    <th style="width: 75px; text-align: right;">Nilai Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] as $row)
                    <tr class="{{ ($row['rank'] ?? 0) <= 3 ? 'highlight' : '' }}">
                        <td style="text-align: center;"><b>{{ $row['rank'] }}</b></td>
                        <td><b>{{ $row['participant']->display_name }}</b></td>
                        <td>{{ $row['participant']->nama_pelatih ?? '-' }}</td>
                        <td style="text-align: right;">{{ number_format((float) ($row['grandTotal'] ?? 0), 2, ',', '.') }}</td>
                        <td style="text-align: right; color: {{ ($row['totalDeduction'] ?? 0) < 0 ? '#b91c1c' : 'inherit' }};">
                            {{ number_format((float) ($row['totalDeduction'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td style="text-align: right;"><b>{{ number_format((float) ($row['finalScore'] ?? 0), 2, ',', '.') }}</b></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #888; font-style: italic;">Belum ada data nilai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="ttd-wrapper" style="margin-top: 20px;">
        <table class="ttd-table">
            <tr>
                <td>
                    <div class="ttd-role">Disahkan oleh Dewan Juri,</div>
                    <div style="height: 35px;"></div>
                    <div class="ttd-name">{{ $judges->first()?->name ?? 'Dewan Juri' }}</div>
                </td>
                <td>
                    <div class="ttd-role">Panitia Pelaksana,</div>
                    <div style="height: 35px;"></div>
                    <div class="ttd-name">{{ $signature?->name ?? ($eventner->diselenggarakan_oleh ?: 'Panitia') }}</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
