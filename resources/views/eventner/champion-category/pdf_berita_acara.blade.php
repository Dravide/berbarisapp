<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Dewan Juri — Penetapan Kategori Juara</title>
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
            font-size: 9px;
            color: #222;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        /* KOP SURAT */
        .kop {
            border-bottom: 3px double #222;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 50px; height: 50px; border-radius: 6px; border: 1px solid #ccc; }
        .kop-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #1a1a2e; }
        .kop-sub { font-size: 9px; color: #666; }

        /* JUDUL BANNER */
        .judul {
            background: #1a1a2e;
            color: #fff;
            text-align: center;
            padding: 6px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .judul-sub {
            background: #2c3e50;
            color: #f1c40f;
            text-align: center;
            padding: 5px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        /* BAGIAN / SECTION */
        .judul-bagian {
            background: #ecf0f1;
            border-left: 4px solid #2c3e50;
            color: #2c3e50;
            font-size: 9.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            padding: 5px 8px;
            margin: 12px 0 4px;
            text-transform: uppercase;
        }
        .level-badge {
            background: #1a1a2e;
            color: #fff;
            padding: 4px 8px;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 8px 0 4px;
            border-radius: 3px;
        }
        .sub-label {
            font-size: 9px;
            font-weight: bold;
            color: #2c3e50;
            margin: 8px 0 3px;
            background: #f1f5f9;
            padding: 3px 6px;
            border-left: 3px solid #f1c40f;
        }
        .sub-desc {
            font-size: 8px;
            color: #64748b;
            font-weight: normal;
            margin-left: 4px;
        }

        /* PREAMBLE & TEKS HUKUM */
        .legal-box {
            background: #f8fafc;
            border-left: 3px solid #2c3e50;
            padding: 6px 10px;
            margin: 8px 0 10px;
            font-size: 8.5px;
            line-height: 1.5;
            color: #334155;
            text-align: justify;
        }
        .legal-closing {
            font-size: 8px;
            font-style: italic;
            color: #475569;
            margin: 10px 0 8px;
            text-align: justify;
            line-height: 1.45;
        }

        /* TABEL REKAP RESMI */
        table.rekap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            margin-bottom: 8px;
            page-break-inside: auto;
        }
        table.rekap thead { display: table-header-group; }
        table.rekap tr { page-break-inside: avoid; }
        table.rekap th {
            background: #2c3e50;
            color: #fff;
            padding: 4px 6px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #1a1a2e;
        }
        table.rekap th.num { text-align: center; }
        table.rekap td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 8.5px;
            vertical-align: middle;
        }
        table.rekap td.num { text-align: center; }
        table.rekap tr:nth-child(even) td { background: #fafbfc; }

        table.rekap .nama { font-weight: bold; color: #2c3e50; }
        table.rekap .gelar { font-weight: bold; color: #1e40af; }
        table.rekap .pelatih { color: #666; font-size: 8px; }
        table.rekap .merah { color: #c0392b; font-weight: bold; text-align: right; }
        table.rekap .akhir { font-weight: bold; color: #1a1a2e; text-align: right; }

        /* RANK PODIUM HIGHLIGHTS */
        table.rekap .rank1 { background: #fef3c7 !important; font-weight: bold; color: #92400e; }
        table.rekap .rank2 { background: #f1f5f9 !important; font-weight: bold; color: #475569; }
        table.rekap .rank3 { background: #fef2e8 !important; font-weight: bold; color: #9a3412; }

        /* TANDA TANGAN */
        .ttd-wrapper {
            margin-top: 18px;
            page-break-inside: avoid;
            width: 100%;
        }
        .ttd-table { width: 100%; border: none; }
        .ttd-table td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 8.5px;
            padding: 0 10px;
        }
        .ttd-role {
            font-size: 8px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .ttd-space { height: 45px; }
        .ttd-name {
            font-weight: bold;
            color: #1a1a2e;
            text-decoration: underline;
            font-size: 9px;
        }
        .ttd-sub { font-size: 7.5px; color: #666; margin-top: 2px; }
        .ttd-img { max-height: 45px; max-width: 120px; }

        /* LAMPIRAN HEADER */
        .page-break { page-break-before: always; }
        .lampiran-banner {
            font-size: 7.5px;
            text-align: right;
            color: #64748b;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

    @php
        $baCode = strtoupper(substr(md5(($competitionCategory?->id ?? 'ALL') . $eventner->id), 0, 6));
    @endphp

    {{-- HALAMAN 1: BERITA ACARA RESMI PENETAPAN KATEGORI JUARA --}}
    <div class="kop">
        <table>
            <tr>
                <td style="width: 60px;">
                    @if($eventner->logo_event)
                        <img src="{{ public_path('storage/' . $eventner->logo_event) }}" class="kop-logo">
                    @endif
                </td>
                <td style="padding-left: 12px;">
                    <div class="kop-title">{{ $eventner->nama_event }}</div>
                    <div class="kop-sub">{{ $eventner->diselenggarakan_oleh }}</div>
                    @if($eventner->lokasi || $eventner->venue)
                        <div class="kop-sub">{{ $eventner->venue ? $eventner->venue . ', ' : '' }}{{ $eventner->lokasi }}</div>
                    @endif
                </td>
                <td style="text-align: right; font-size: 8px; color: #666; width: 150px;">
                    Lingkup: <b>{{ $competitionCategory ? $competitionCategory->full_name : 'Semua Kategori Lomba' }}</b><br>
                    Dicetak<br>{{ now()->translatedFormat('d F Y') }} &bull; {{ now()->format('H:i') }} WIB
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">BERITA ACARA DEWAN JURI</div>
    <div class="judul-sub">
        PENETAPAN HASIL DAN KATEGORI JUARA &bull; NOMOR: BA-JURI/{{ date('Y') }}/{{ $baCode }}
    </div>

    <div class="legal-box">
        Pada hari ini, <b>{{ now()->translatedFormat('l') }}</b> tanggal <b>{{ now()->translatedFormat('d F Y') }}</b>, bertempat di <b>{{ $eventner->venue ?: ($eventner->lokasi ?: 'Lokasi Kegiatan') }}</b>, Dewan Juri yang bertugas dalam kejuaraan <b>{{ $eventner->nama_event }}</b> @if($competitionCategory) untuk tingkat <b>{{ $competitionCategory->full_name }}</b>@if($competitionGroup) &mdash; {{ $competitionGroup->name }}@endif@if($competitionRound) &mdash; Babak {{ $competitionRound->name }}@endif @endif, setelah memeriksa, mengamati, dan merekapitulasi seluruh perolehan nilai berdasarkan rubrik penilaian dan ketentuan perlombaan yang berlaku secara teliti, independen, dan objektif, dengan ini menetapkan peraih gelar dan kategori juara sebagai berikut:
    </div>

    <div class="judul-bagian">I. DEWAN JURI PENILAI</div>
    <table class="rekap">
        <thead>
            <tr>
                <th class="num" style="width: 28px;">No</th>
                <th style="text-align: left;">Nama Juri</th>
                <th style="text-align: left;">Kategori / Penugasan</th>
                <th class="num" style="width: 110px;">Paraf / Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($judges as $idx => $judge)
                <tr>
                    <td class="num">{{ $idx + 1 }}</td>
                    <td class="nama">{{ $judge->name }}</td>
                    <td>
                        {{ $judge->assignment_display ?? ($judge->assessmentCategories->pluck('name')->join(', ') ?: 'Dewan Juri Penilai') }}
                    </td>
                    <td class="num" style="height: 24px; color: #94a3b8;">
                        ( .......................... )
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="num" style="color: #888; font-style: italic; padding: 8px;">Dewan Juri bertugas secara terpadu.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="judul-bagian">II. HASIL PENETAPAN PEMENANG KATEGORI JUARA</div>

    @foreach($sections as $section)
        @if($sections->count() > 1)
            <div class="level-badge">
                {{ $section['level'] ? $section['level']->full_name : 'Semua Tingkat (Rubrik Global)' }}
            </div>
        @endif

        @foreach($section['champions'] as $champion)
            @php
                $rankingData = $rankings[$champion->id] ?? [];
            @endphp
            <div class="sub-label">
                &#9813; {{ $champion->name }}
                @if($champion->description)
                    <span class="sub-desc">({{ $champion->description }})</span>
                @endif
            </div>

            <table class="rekap">
                <thead>
                    <tr>
                        <th class="num" style="width: 40px;">Rank</th>
                        <th style="text-align: left; width: 120px;">Gelar / Kategori Juara</th>
                        <th class="num" style="width: 50px;">No. Undian</th>
                        <th style="text-align: left;">Nama Kontingen / Sekolah</th>
                        <th style="text-align: left;">Pelatih / Pembina</th>
                        <th style="width: 75px; text-align: right;">Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingData as $ps)
                        @php
                            $rankCls = match($ps['rank'] ?? 0) {
                                1 => 'rank1',
                                2 => 'rank2',
                                3 => 'rank3',
                                default => '',
                            };
                            $gelarTitle = $ps['title'] ?: ($champion->name . ' #' . $ps['rank']);
                        @endphp
                        <tr class="{{ $rankCls }}">
                            <td class="num"><b>{{ $ps['rank'] }}</b></td>
                            <td class="gelar">{{ $gelarTitle }}</td>
                            <td class="num">{{ $ps['participant']->urutan_tampil ?: '-' }}</td>
                            <td class="nama">{{ $ps['participant']->display_name }}</td>
                            <td class="pelatih">{{ $ps['participant']->nama_pelatih ?? '-' }}</td>
                            <td class="akhir">{{ number_format((float) $ps['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="num" style="color: #888; font-style: italic; padding: 6px;">
                                Belum ada data nilai peserta untuk kategori juara ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    @endforeach

    <div class="legal-closing">
        Keputusan Dewan Juri ini bersifat mutlak, sah, dan tidak dapat diganggu gugat sejak ditetapkan. Berita Acara ini dibuat dalam keadaan sadar dan sebenar-benarnya untuk dipergunakan sebagaimana mestinya.
    </div>

    <div class="ttd-wrapper">
        <table class="ttd-table">
            <tr>
                <td>
                    <div class="ttd-role">Ketua Dewan Juri</div>
                    <div class="ttd-space"></div>
                    <div class="ttd-name">{{ $judges->first()?->name ?? 'Ketua Dewan Juri' }}</div>
                    <div class="ttd-sub">Koordinator Adjudikasi</div>
                </td>
                <td>
                    <div class="ttd-role">Mengetahui,<br>Ketua Pelaksana / Panitia</div>
                    <div class="ttd-space">
                        @if($signature && $signature->image)
                            <img src="{{ public_path('storage/' . $signature->image) }}" class="ttd-img">
                        @endif
                    </div>
                    <div class="ttd-name">{{ $signature?->name ?? ($eventner->diselenggarakan_oleh ?: 'Ketua Panitia') }}</div>
                    <div class="ttd-sub">{{ $eventner->nama_event }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- HALAMAN 2 DST: LAMPIRAN RINCIAN PEROLEHAN NILAI KATEGORI JUARA --}}
    <div class="page-break"></div>

    <div class="lampiran-banner">
        LAMPIRAN BERITA ACARA NOMOR: BA-JURI/{{ date('Y') }}/{{ $baCode }} &bull; REKAPITULASI DETAIL KATEGORI JUARA
    </div>

    <div class="judul">LAMPIRAN: REKAPITULASI NILAI KATEGORI JUARA</div>
    <div class="judul-sub">
        {{ $eventner->nama_event }} &bull; {{ $competitionCategory ? $competitionCategory->full_name : 'Semua Kategori Lomba' }}
    </div>

    @foreach($sections as $section)
        @if($sections->count() > 1)
            <div class="level-badge">
                {{ $section['level'] ? $section['level']->full_name : 'Semua Tingkat (Rubrik Global)' }}
            </div>
        @endif

        @foreach($section['champions'] as $champion)
            @php
                $rankingData = $rankings[$champion->id] ?? [];
            @endphp
            <div class="judul-bagian">
                {{ $champion->name }}
            </div>
            <table class="rekap">
                <thead>
                    <tr>
                        <th class="num" style="width: 32px;">Rank</th>
                        <th style="text-align: left; width: 110px;">Gelar Juara</th>
                        <th class="num" style="width: 45px;">No. Undian</th>
                        <th style="text-align: left;">Nama Kontingen / Sekolah</th>
                        <th style="width: 65px; text-align: right;">Nilai Kotor</th>
                        <th style="width: 65px; text-align: right;">Pengurangan</th>
                        <th style="width: 75px; text-align: right;">Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankingData as $ps)
                        @php
                            $rankCls = match($ps['rank'] ?? 0) {
                                1 => 'rank1',
                                2 => 'rank2',
                                3 => 'rank3',
                                default => '',
                            };
                            $gelarTitle = $ps['title'] ?: ($champion->name . ' #' . $ps['rank']);
                        @endphp
                        <tr class="{{ $rankCls }}">
                            <td class="num"><b>{{ $ps['rank'] }}</b></td>
                            <td class="gelar">{{ $gelarTitle }}</td>
                            <td class="num">{{ $ps['participant']->urutan_tampil ?: '-' }}</td>
                            <td class="nama">{{ $ps['participant']->display_name }}</td>
                            <td style="text-align: right;">{{ number_format((float) ($ps['gross_total'] ?? $ps['total']), 2, ',', '.') }}</td>
                            <td class="merah" style="color: {{ ($ps['deduction'] ?? 0) > 0 ? '#c0392b' : '#666' }};">
                                {{ number_format((float) ($ps['deduction'] ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="akhir">{{ number_format((float) $ps['total'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="num" style="color: #888; font-style: italic; padding: 6px;">
                                Belum ada data nilai peserta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    @endforeach

    <div class="ttd-wrapper" style="margin-top: 18px;">
        <table class="ttd-table">
            <tr>
                <td>
                    <div class="ttd-role">Disahkan oleh Dewan Juri</div>
                    <div style="height: 35px;"></div>
                    <div class="ttd-name">{{ $judges->first()?->name ?? 'Dewan Juri' }}</div>
                    <div class="ttd-sub">Koordinator Adjudikasi</div>
                </td>
                <td>
                    <div class="ttd-role">Panitia Pelaksana</div>
                    <div style="height: 35px;"></div>
                    <div class="ttd-name">{{ $signature?->name ?? ($eventner->diselenggarakan_oleh ?: 'Panitia') }}</div>
                    <div class="ttd-sub">{{ $eventner->nama_event }}</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
