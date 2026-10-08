<div>
    @php
        // FQCN, bukan use-import: itu konvensi blade lain di repo ini
        // (score-recap, scoring/index) dan tak bergantung pada direktif @use.
        $ringkas = $hasil['ringkas'];
        $kriteriaJuri = $hasil['juri'];
        $kriteriaKriteria = $hasil['kriteria'];
        $selSemua = $hasil['sel'];

        // Warna chip per kelas. Dipusatkan di sini supaya tabel juri dan
        // temuan tak bisa berbeda warna untuk kelas yang sama.
        $warnaKelas = [
            \App\Services\JudgeScoreComparison::KELAS_TINGGI => 'danger',
            \App\Services\JudgeScoreComparison::KELAS_RENDAH => 'info',
            \App\Services\JudgeScoreComparison::KELAS_TAK_KONSISTEN => 'warning',
            \App\Services\JudgeScoreComparison::KELAS_SEIMBANG => 'success',
            \App\Services\JudgeScoreComparison::KELAS_KURANG => 'secondary',
            \App\Services\JudgeScoreComparison::KELAS_TAK_BISA => 'light',
        ];

        $labelKeadaan = [
            'tanpa_peserta' => ['Belum ada peserta', 'Tingkat ini belum punya peserta terdaftar.', 'ti-users-off'],
            'tanpa_juri' => ['Belum ada juri', 'Tingkat ini belum punya juri yang ditugaskan, jadi tak ada yang bisa dibandingkan.', 'ti-user-off'],
            'tanpa_rubrik' => ['Belum ada rubrik', 'Juri sudah ditugaskan, tapi belum ada rubrik yang boleh mereka nilai untuk peserta di tingkat ini.', 'ti-file-off'],
            'tanpa_nilai' => ['Belum ada nilai', 'Juri sudah ditugaskan, tapi belum ada satu pun nilai tersimpan untuk tingkat ini.', 'ti-clipboard-x'],
            'tanpa_pembanding' => ['Tidak ada sel pembanding', 'Nilai sudah ada, tapi tiap juri memegang kriteria yang berbeda — tak ada penilaian yang bisa dibandingkan. Itu pembagian rubrik, bukan selisih nilai.', 'ti-git-compare'],
        ];
    @endphp

    {{-- Page Header (Template Standard) --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Perbandingan Nilai Juri</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">{{ $eventner->nama_event }}</li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('eventner.scoring.index', ['selectedCategoryId' => $selectedCategoryId]) }}">Input Nilai</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Perbandingan</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Penyaring --}}
    <div class="card w-100">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="card-title fw-semibold mb-1">Analisis Keseimbangan Penilaian</h5>
                    <p class="text-muted small mb-0">
                        Halaman ini hanya membaca. Angka yang ditampilkan berasal dari nilai tersimpan, bukan dari input yang belum disimpan.
                    </p>
                </div>
                <a href="{{ route('eventner.scoring.index', array_filter(['selectedCategoryId' => $selectedCategoryId, 'selectedRoundId' => $selectedRoundId])) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Kembali ke Input Nilai
                </a>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <div class="input-group" style="max-width: 360px;">
                    <span class="input-group-text bg-primary text-white"><i class="ti ti-category"></i></span>
                    <select class="form-select" wire:model.live="selectedCategoryId" aria-label="Tingkat lomba">
                        <option value="">Pilih tingkat lomba</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->full_name }} ({{ $cat->registrations_count }})</option>
                        @endforeach
                    </select>
                </div>

                @if($rounds->isNotEmpty())
                    <div class="input-group" style="max-width: 240px;">
                        <span class="input-group-text bg-success-subtle text-success"><i class="ti ti-flag"></i></span>
                        <select class="form-select" wire:model.live="selectedRoundId" aria-label="Babak">
                            <option value="">Semua Babak</option>
                            @foreach($rounds as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($groups->isNotEmpty())
                    <div class="input-group" style="max-width: 240px;">
                        <span class="input-group-text bg-warning-subtle text-warning"><i class="ti ti-users-group"></i></span>
                        <select class="form-select" wire:model.live="selectedGroupId" aria-label="Grup">
                            <option value="">Seluruh Tingkat</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(! $hasil['punya_data'])
        @php $k = $labelKeadaan[$hasil['keadaan']] ?? ['Belum ada data', 'Tidak ada yang bisa dianalisis untuk pilihan ini.', 'ti-database-off']; @endphp
        <div class="card w-100">
            <div class="card-body p-5 text-center">
                <i class="ti {{ $k[2] }} text-muted" style="font-size: 3rem;"></i>
                <h5 class="fw-semibold mt-3 mb-2">{{ $k[0] }}</h5>
                <p class="text-muted mb-0" style="max-width: 520px; margin-inline: auto;">{{ $k[1] }}</p>
            </div>
        </div>
    @else
        @if($hasil['keadaan'] === 'tanpa_pembanding')
            <div class="alert alert-secondary d-flex align-items-start gap-2">
                <i class="ti ti-git-compare fs-5 mt-1"></i>
                <div class="small">
                    <strong>Tidak ada sel pembanding.</strong>
                    Nilai sudah tersimpan, tapi tidak ada satu pun penilaian yang diisi lebih dari satu juri pada tingkat/babak/grup ini —
                    tiap juri memegang kriteria sendiri. Pembagian rubrik seperti itu memang sah; yang tidak bisa dilakukan hanyalah
                    membandingkan nilai antar juri.
                </div>
            </div>
        @endif

        {{-- Kartu ringkasan --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-none border h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                            <i class="ti ti-users"></i> Juri dinilai
                        </div>
                        <div class="fs-3 fw-bold">{{ $ringkas['juri_total'] }}</div>
                        <div class="small text-muted">
                            {{ $ringkas['juri_dibandingkan'] }} cukup pembanding ·
                            {{ $ringkas['juri_tak_dibandingkan'] }} tidak
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-none border h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                            <i class="ti ti-grid-dots"></i> Sel dibandingkan
                        </div>
                        <div class="fs-3 fw-bold">{{ $ringkas['sel_dibandingkan'] }}</div>
                        <div class="small text-muted">dari {{ $ringkas['sel_terisi'] }} sel terisi</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-none border h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                            <i class="ti ti-flag-2"></i> Sel berselisih
                        </div>
                        <div class="fs-3 fw-bold {{ $ringkas['sel_ditandai'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $ringkas['sel_ditandai'] }}
                        </div>
                        <div class="small text-muted">
                            selisih antar juri di atas {{ (int) round(\App\Services\JudgeScoreComparison::AMBANG_RENTANG_SEL * 100) }}% skala
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-none border h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                            <i class="ti ti-list-check"></i> Kriteria dinilai
                        </div>
                        <div class="fs-3 fw-bold">{{ $ringkas['kriteria_total'] }}</div>
                        <div class="small text-muted">punya nilai di tingkat ini</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Temuan --}}
        @if($hasil['temuan'] !== [])
            <div class="card w-100 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3 d-flex align-items-center gap-2">
                        <i class="ti ti-alert-circle"></i> Yang perlu ditinjau
                    </h6>
                    <ul class="list-unstyled mb-0">
                        @foreach($hasil['temuan'] as $t)
                            @php $perhatian = $t['tingkat'] === 'perhatian'; @endphp
                            <li class="d-flex align-items-start gap-2 mb-2">
                                <span class="badge {{ $perhatian ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary' }} mt-1">
                                    {{ $perhatian ? 'Perhatian' : 'Info' }}
                                </span>
                                <span class="small">{{ $t['teks'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Per juri --}}
        <div class="card w-100 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-1">Per Juri</h6>
                <p class="text-muted small mb-3">
                    Bias = rata-rata selisih nilai juri ini terhadap rekannya, dihitung hanya pada sel yang mereka berdua isi.
                    Nilai positif berarti cenderung lebih tinggi. Angka di bawah {{ \App\Services\JudgeScoreComparison::MIN_SEL }} sel pembanding tidak
                    diberi putusan — terlalu sedikit untuk disimpulkan.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Juri</th>
                                <th>Penugasan</th>
                                <th class="text-center">Sel dinilai</th>
                                <th class="text-center">Pembanding</th>
                                <th class="text-end">Bias</th>
                                <th class="text-end">Sebaran</th>
                                <th>Putusan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kriteriaJuri as $j)
                                @php $warna = $warnaKelas[$j['kelas']] ?? 'secondary'; @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $j['judge']->name }}</td>
                                    <td class="text-muted small">{{ $j['penugasan'] }}</td>
                                    <td class="text-center">
                                        {{ $j['dinilai'] }}
                                        <span class="text-muted small">/ {{ $j['tugas'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $j['pembanding'] }}</td>
                                    <td class="text-end">
                                        @if($j['bias'] !== null)
                                            <span class="fw-semibold {{ $j['bias'] > 0 ? 'text-danger' : ($j['bias'] < 0 ? 'text-info' : '') }}">
                                                {{ $j['bias'] > 0 ? '+' : '' }}{{ number_format($j['bias'] * 100, 1, ',', '.') }}%
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($j['sigma'] !== null)
                                            {{ number_format($j['sigma'] * 100, 1, ',', '.') }}%
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $warna }}{{ in_array($warna, ['light'], true) ? ' text-dark' : '' }}">
                                            {{ $j['kelas'] }}
                                        </span>
                                        @if($j['konsisten'] === true)
                                            <span class="badge bg-success-subtle text-success ms-1">konsisten</span>
                                        @elseif($j['konsisten'] === false)
                                            <span class="badge bg-warning-subtle text-warning ms-1">sebaran lebar</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sel berselisih --}}
        @php
            $selBerselisih = collect($selSemua)->where('flag', true)->sortByDesc('rentang')->values();
            $namaJuri = collect($kriteriaJuri)->mapWithKeys(fn ($j) => [$j['judge']->id => $j['judge']->name]);
        @endphp

        <div class="card w-100 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-semibold mb-1">Sel Berselisih</h6>
                        <p class="text-muted small mb-0">
                            Peserta × kriteria yang selisih nilai antar jurinya lewat {{ (int) round(\App\Services\JudgeScoreComparison::AMBANG_RENTANG_SEL * 100) }}% skala.
                            Klik satu baris untuk melihat nilai tiap jurinya.
                        </p>
                    </div>
                    <span class="badge bg-danger-subtle text-danger">{{ $selBerselisih->count() }} sel</span>
                </div>

                @if($selBerselisih->isEmpty())
                    <p class="text-muted small mb-0">
                        <i class="ti ti-circle-check text-success me-1"></i>
                        Tidak ada sel yang selisihnya melewati ambang. Penilaian antar juri seragam pada tingkat/babak/grup ini.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Peserta</th>
                                    <th>Kriteria</th>
                                    <th class="text-center">Juri</th>
                                    <th class="text-end">Terendah</th>
                                    <th class="text-end">Tertinggi</th>
                                    <th class="text-end">Selisih</th>
                                    <th>Paling jauh</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selBerselisih as $sel)
                                    @php
                                        $kunci = $kunciSel($sel);
                                        $mentah = collect($sel['nilai'])->pluck('raw');
                                        $rendah = $mentah->min();
                                        $tinggi = $mentah->max();
                                        $selisihPoin = $tinggi - $rendah;
                                    @endphp
                                    <tr role="button" wire:click="lihatSel('{{ $kunci }}')">
                                        <td class="fw-semibold">{{ $sel['peserta'] }}</td>
                                        <td>
                                            <div class="small fw-semibold">{{ $sel['criteria']->name }}</div>
                                            <div class="text-muted" style="font-size: .75rem;">
                                                {{ $sel['kategori'] }} · skala 0–{{ $sel['skala'] }}
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $sel['juri_terisi'] }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format((float) $rendah) }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format((float) $tinggi) }}</td>
                                        <td class="text-end fw-semibold text-danger">
                                            {{ \App\Support\ScoreOptions::format((float) $selisihPoin) }}
                                            <span class="text-muted small fw-normal">({{ number_format($sel['rentang'] * 100, 0, ',', '.') }}%)</span>
                                        </td>
                                        <td class="small">{{ $namaJuri[$sel['menyimpang']] ?? '—' }}</td>
                                        <td class="text-end">
                                            <i class="ti ti-chevron-right text-muted"></i>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Per kriteria --}}
        @if($kriteriaKriteria !== [])
            <div class="card w-100 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-1">Per Kriteria</h6>
                    <p class="text-muted small mb-3">
                        Diurutkan dari rentang terlebar. Rentang diukur dalam persen skala, bukan poin — supaya kriteria berskala 0–100
                        tidak selalu terlihat lebih bermasalah daripada yang berskala 0–25.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kriteria</th>
                                    <th class="text-center">Skala</th>
                                    <th class="text-center">n</th>
                                    <th class="text-end">Rerata</th>
                                    <th class="text-end">Median</th>
                                    <th class="text-end">Terendah</th>
                                    <th class="text-end">Tertinggi</th>
                                    <th class="text-end">Rentang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kriteriaKriteria as $k)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold small">{{ $k['criteria']->name }}</div>
                                            <div class="text-muted" style="font-size: .75rem;">{{ $k['kategori'] }}</div>
                                        </td>
                                        <td class="text-center small">0–{{ $k['skala'] }}</td>
                                        <td class="text-center">{{ $k['n'] }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format($k['rerata'] * $k['skala']) }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format($k['median'] * $k['skala']) }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format($k['min'] * $k['skala']) }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format($k['maks'] * $k['skala']) }}</td>
                                        <td class="text-end">
                                            <span class="{{ $k['flag'] ? 'fw-semibold text-danger' : 'text-muted' }}">
                                                {{ number_format($k['rentang'] * 100, 0, ',', '.') }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Catatan metode. Ditulis apa adanya supaya panitia tak menghitung
             sendiri lalu salah — terutama soal bobot dan total per juri. --}}
        <div class="card w-100 mb-4 shadow-none border-0 bg-light-subtle">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2 d-flex align-items-center gap-2">
                    <i class="ti ti-info-circle"></i> Cara angka ini dihitung
                </h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1">
                        Satuan analisisnya <strong>sel</strong> (peserta × kriteria), bukan total per juri. Total per juri tidak setara
                        sejak rubrik boleh dibagi antar juri — juri A bisa memegang kriteria berskala 0–25 sementara juri B 0–100.
                    </li>
                    <li class="mb-1">
                        Juri hanya dibandingkan dengan juri lain pada sel yang <strong>mereka berdua isi</strong>. Dua juri tanpa satu pun
                        sel beririsan tidak dipaksa dibandingkan.
                    </li>
                    <li class="mb-1">
                        Nilai disetarakan sebagai <strong>persentase skala</strong> kriteria sebelum dibandingkan, lalu ditampilkan kembali
                        dalam poin.
                    </li>
                    <li class="mb-1">
                        <strong>Bobot kriteria sengaja tidak dipakai</strong> di sini. Bobot adalah sifat kriteria, bukan sifat juri;
                        memasukkannya membuat juri pemegang kriteria berbobot besar tampak lebih tinggi padahal ia menilai sama.
                        Bobot tetap dipakai di Rekap Nilai dan peringkat juara.
                    </li>
                    <li>
                        Kelas <em>Seimbang</em> berarti |bias| ≤ {{ (int) round(\App\Services\JudgeScoreComparison::AMBANG_BIAS * 100) }}% skala
                        <strong>dan</strong> sebaran ≤ {{ (int) round(\App\Services\JudgeScoreComparison::AMBANG_SIGMA * 100) }}%. Rata-rata seimbang
                        tapi sebaran lebar masuk <em>Tak konsisten</em> — itu dua hal berbeda.
                    </li>
                </ul>
            </div>
        </div>
    @endif

    {{-- Modal rincian satu sel. WAJIB di dalam root komponen ini — modal yang
         diletakkan setelah </div> penutup tidak pernah tampil (lihat
         catatan livewire-modal-di-luar-root). --}}
    <div class="modal fade" id="selModal" tabindex="-1" aria-labelledby="selModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                @if($selTerbuka)
                    @php
                        $mentah = collect($selTerbuka['nilai']);
                        $skala = $selTerbuka['skala'];
                    @endphp
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="selModalLabel">{{ $selTerbuka['peserta'] }}</h5>
                            <div class="small text-muted">
                                {{ $selTerbuka['criteria']->name }} · {{ $selTerbuka['kategori'] }} · skala 0–{{ $skala }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="tutupSel"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Juri</th>
                                    <th class="text-end">Nilai</th>
                                    <th class="text-end">% skala</th>
                                    <th class="text-end">Selisih dari rerata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mentah as $judgeId => $v)
                                    @php $d = $v['porsi'] - $selTerbuka['rerata']; @endphp
                                    <tr class="{{ $judgeId === $selTerbuka['menyimpang'] ? 'table-warning' : '' }}">
                                        <td class="fw-semibold">{{ $namaJuri[$judgeId] ?? 'Juri #' . $judgeId }}</td>
                                        <td class="text-end">{{ \App\Support\ScoreOptions::format($v['raw']) }}</td>
                                        <td class="text-end text-muted">{{ number_format($v['porsi'] * 100, 0, ',', '.') }}%</td>
                                        <td class="text-end">
                                            <span class="fw-semibold {{ $d > 0 ? 'text-danger' : ($d < 0 ? 'text-info' : '') }}">
                                                {{ $d > 0 ? '+' : '' }}{{ number_format($d * 100, 1, ',', '.') }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td class="fw-semibold">Rerata</td>
                                    <td class="text-end fw-semibold">{{ \App\Support\ScoreOptions::format($selTerbuka['rerata'] * $skala) }}</td>
                                    <td class="text-end" colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="small text-muted">
                            Rentang {{ number_format($selTerbuka['rentang'] * 100, 0, ',', '.') }}% skala.
                            Yang paling jauh dari rerata: <strong>{{ $namaJuri[$selTerbuka['menyimpang']] ?? '—' }}</strong>.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" wire:click="tutupSel">Tutup</button>
                    </div>
                @else
                    <div class="modal-body p-4 text-center text-muted small">Memuat…</div>
                @endif
            </div>
        </div>
    </div>

    @script
    <script>
        // Modal Bootstrap tidak bisa dibuka dari Blade saja: Livewire menukar
        // DOM-nya, jadi instance Bootstrap lama harus dibuang tiap kali isi sel
        // berganti. Karena itu show/hide dipicu lewat event, bukan atribut.
        $wire.on('buka-sel', () => {
            const el = document.getElementById('selModal');
            if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
        });
        $wire.on('tutup-sel', () => {
            const el = document.getElementById('selModal');
            if (el && window.bootstrap) bootstrap.Modal.getInstance(el)?.hide();
        });
    </script>
    @endscript
</div>
