<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai {{ $category->name }}</title>
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
        body { font-family: 'PJ', sans-serif; font-size: 9px; color: #222; margin: 0; padding: 0; }

        /* KOP */
        .kop { border-bottom: 3px double #222; padding-bottom: 8px; margin-bottom: 10px; }
        .kop table { width: 100%; border: none; }
        .kop td { border: none; vertical-align: middle; padding: 0; }
        .kop-logo { width: 50px; height: 50px; border-radius: 6px; border: 1px solid #ccc; }
        .kop-title { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .kop-sub { font-size: 9px; color: #666; }

        /* JUDUL */
        .judul { background: #1a1a2e; color: #fff; text-align: center; padding: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .judul-sub { background: #2c3e50; color: #f1c40f; text-align: center; padding: 5px; font-size: 10px; font-weight: bold; letter-spacing: 1px; margin-bottom: 10px; }

        /* BAGIAN (babak / grup) */
        /* Sengaja TIDAK page-break-inside: avoid. Blok yang lebih tinggi dari
           satu halaman tak bisa dipatuhi dompdf, dan hasilnya seluruh tabel
           terdorong bulat ke halaman berikutnya — grup padat meninggalkan
           setengah halaman kosong lalu semua isinya pindah ke laman 2.
           Tabelnya dibiarkan terpotong; yang dijaga justru bagian-bagiannya:
           kepala babak/grup tak boleh terpisah dari tabelnya, header kolom
           diulang di halaman lanjutan, dan satu baris peserta tak dipotong
           jadi dua halaman. */
        .bagian { margin-bottom: 14px; }
        /* Judul bagian hidup di dalam thead, bukan di atas tabel, supaya
           ikut tercetak ulang di halaman lanjutan — tabel yang kini boleh
           dipotong akan kehilangan nama grupnya kalau judulnya di luar. */
        table.rekap th.judul-bagian { background: #ecf0f1; border-left: 4px solid #2c3e50; border-right: none; color: #2c3e50; font-size: 10px; letter-spacing: 0; padding: 5px 8px; text-align: left; text-transform: none; }
        table.rekap th.judul-bagian .final { color: #b8860b; }
        .bagian-note { padding: 4px 8px; font-size: 8px; color: #b8860b; background: #fef9e7; border-left: 4px solid #e67e22; page-break-after: avoid; }

        table.rekap { width: 100%; border-collapse: collapse; margin-top: 4px; page-break-inside: auto; }
        /* Header kolom dicetak ulang di tiap halaman lanjutan — tanpa ini
           halaman kedua penuh angka tanpa judul kolom. */
        table.rekap thead { display: table-header-group; }
        table.rekap tr { page-break-inside: avoid; }
        table.rekap th { background: #2c3e50; color: #fff; padding: 4px 5px; font-size: 7px; font-weight: bold; text-transform: uppercase; border: 1px solid #1a1a2e; }
        table.rekap th.num { text-align: center; }
        table.rekap td { padding: 4px 5px; border: 1px solid #ddd; font-size: 9px; }
        table.rekap td.num { text-align: center; }
        table.rekap tr:nth-child(even) td { background: #fafbfc; }
        table.rekap .nama { font-weight: bold; color: #2c3e50; }
        table.rekap .pelatih { color: #666; }
        table.rekap .sub { background: #ecf0f1; font-weight: bold; color: #2c3e50; }
        table.rekap .merah { color: #c0392b; font-weight: bold; }
        table.rekap .akhir { font-weight: bold; color: #1a1a2e; }
        /* Tiga peringkat teratas diberi penanda supaya juara bagian itu terbaca
           tanpa harus membandingkan angka di seluruh kolom. */
        table.rekap .rank1 { background: #fef3c7 !important; font-weight: bold; color: #92400e; }
        table.rekap .rank2 { background: #f1f5f9 !important; font-weight: bold; color: #475569; }
        table.rekap .rank3 { background: #fef2e8 !important; font-weight: bold; color: #9a3412; }

        .kosong { padding: 10px; font-size: 9px; color: #888; font-style: italic; }

        /* FOOTER */
        .foot { margin-top: 14px; padding-top: 5px; border-top: 1px solid #ddd; text-align: center; font-size: 7px; color: #aaa; }
    </style>
</head>
<body>

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
                </td>
                <td style="text-align: right; font-size: 8px; color: #666;">
                    Dicetak<br>{{ now()->translatedFormat('d F Y') }} &bull; {{ now()->format('H:i') }} WIB
                </td>
            </tr>
        </table>
    </div>

    <div class="judul">Rekap Nilai Keseluruhan</div>
    <div class="judul-sub">{{ $category->full_name ?? $category->name }}</div>

    @forelse($sections as $section)
        @php
            // Tingkat tanpa babak: $sections langsung daftar grup, tiap bagian
            // membawa 'data'. Tingkat berbabak: satu bagian per babak yang
            // membawa 'groups'. Kuncinya dibedakan lewat 'groups', BUKAN lewat
            // 'label' — bagian grup pun berlabel ("Grup A").
            $blocks = isset($section['groups'])
                ? [['label' => $section['label'] ?? null, 'final' => $section['final'] ?? false, 'groups' => $section['groups']]]
                : [['label' => null, 'final' => false, 'groups' => [$section]]];
        @endphp
        @foreach($blocks as $block)
            @php
                // Grup yang tak punya peserta di babak ini tak perlu tabel hampa —
                // yang perlu terbaca justru "babak ini kosong", bukan enam tabel
                // kosong berjudul grup.
                $adaPeserta = collect($block['groups'])->contains(fn ($g) => ($g['data'] ?? []) !== []);
            @endphp

            @if($block['label'] && ! $adaPeserta)
                <div class="kosong">
                    {{ $block['final']
                        ? 'Belum ada peserta yang lolos ke babak ini.'
                        : 'Belum ada peserta di babak ini.' }}
                </div>
            @endif

            @foreach($block['groups'] as $grup)
                @php
                    $rows = $grup['data'] ?? [];
                    if ($rows === []) {
                        continue;
                    }

                    // Judul babak + grup digabung di satu baris: judul babaknya
                    // dulu berdiri sendiri di atas tabel, dan begitu tabelnya
                    // berlanjut ke halaman berikutnya judulnya tertinggal di
                    // halaman pertama. Di dalam thead ia ikut tercetak ulang.
                    $judulBagian = ($block['label'] ? $block['label'] . ' — ' : '') . $grup['label'];
                    $jumlahKolom = 3 + count($grup['assessmentCategories']) + 3;
                @endphp

                <div class="bagian">
                    @if($grup['tanpa_rubrik'] ?? false)
                        <div class="bagian-note">
                            {{ $grup['label'] }} belum punya format penilaian, jadi kolom nilainya kosong.
                        </div>
                    @endif

                    <table class="rekap">
                        <thead>
                            <tr>
                                <th colspan="{{ $jumlahKolom }}" class="judul-bagian">
                                    @if($block['final'] ?? false)<span class="final">★</span>@endif
                                    {{ $judulBagian }}
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 30px;" class="num">Rank</th>
                                <th style="width: 150px;">Kontingen</th>
                                <th style="width: 110px;">Pelatih</th>
                                @foreach($grup['assessmentCategories'] as $ac)
                                    {{-- Nama seri ikut dicetak: dua seri boleh memakai nama
                                         kategori rubrik yang sama persis, dan tanpa pembeda
                                         ini dua kolom di tabel yang sama terbaca kembar. --}}
                                    <th class="num">
                                        {{ $ac->name }}@if($ac->competitionSeries) ({{ $ac->competitionSeries->name }})@endif
                                    </th>
                                @endforeach
                                <th class="num" style="width: 45px;">Total</th>
                                <th class="num" style="width: 55px;">Pengur.</th>
                                <th class="num" style="width: 55px;">Nilai Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td class="num rank{{ $row['rank'] <= 3 ? $row['rank'] : '' }}">
                                        {{ $row['rank'] }}
                                    </td>
                                    <td class="nama">
                                        {{ $row['participant']->display_name }}
                                        @if($row['participant']->competitionSeries)
                                            <span style="font-weight: normal; color: #2980b9;">&bull; {{ $row['participant']->competitionSeries->name }}</span>
                                        @endif
                                    </td>
                                    <td class="pelatih">{{ $row['participant']->nama_pelatih ?: '—' }}</td>
                                    @foreach($grup['assessmentCategories'] as $ac)
                                        @php
                                            $catScore = $row['categoryTotals'][$ac->id] ?? 0;
                                            $catDed = $row['categoryDeductions'][$ac->id] ?? 0;
                                        @endphp
                                        <td class="num">
                                            {{ \App\Support\ScoreOptions::format($catScore + $catDed) }}
                                            @if($catDed < 0)
                                                <span class="merah">({{ \App\Support\ScoreOptions::format($catDed) }})</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="num sub">{{ \App\Support\ScoreOptions::format($row['grandTotal']) }}</td>
                                    <td class="num {{ $row['totalDeduction'] < 0 ? 'merah' : '' }}">
                                        {{ $row['totalDeduction'] < 0 ? \App\Support\ScoreOptions::format($row['totalDeduction']) : '0' }}
                                    </td>
                                    <td class="num akhir">{{ \App\Support\ScoreOptions::format($row['finalScore']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endforeach
    @empty
        <div class="kosong">Belum ada peserta bernilai di tingkat lomba ini.</div>
    @endforelse

    <div class="foot">
        Rekap dihitung per babak dan per grup — peringkat hanya berlaku di dalam bagiannya.
        Dicetak dari panel {{ $eventner->nama_event }}.
    </div>

</body>
</html>
