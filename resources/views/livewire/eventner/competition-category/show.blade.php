<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">{{ $category->name }}</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('eventner.competition-categories.index') }}">Kategori Lomba</a></li>
                            <li class="breadcrumb-item" aria-current="page">{{ $category->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- ── Header info tingkat ─────────────────────────────────────────── --}}
    <div class="card w-100 mb-4">
        <div class="card-body pb-2">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <h5 class="fw-semibold mb-0 me-2">{{ $category->name }}</h5>
                @if($category->parent)
                    <span class="badge bg-light-primary text-primary">{{ $category->parent->name }}</span>
                @endif
                <span class="badge bg-light-info text-info">{{ $pesertaCount }} peserta</span>
                @if($category->tanggal_pelaksanaan)
                    <span class="badge bg-light-secondary text-secondary">
                        <i class="ti ti-calendar"></i> {{ \Carbon\Carbon::parse($category->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
                    </span>
                @endif
                @if($category->venue)
                    <span class="badge bg-light-warning text-warning"><i class="ti ti-map-pin"></i> {{ $category->venue->name }}</span>
                @endif
                @if($category->punyaBiaya())
                    <span class="badge bg-success-subtle text-success">
                        @php $biayaKini = $category->biayaPada(); @endphp
                        Rp {{ number_format($biayaKini, 0, ',', '.') }}
                        @if($category->gelombang->isNotEmpty())
                            · {{ $category->gelombang->count() }} gelombang
                        @endif
                    </span>
                @else
                    <span class="badge bg-light text-muted border">Gratis</span>
                @endif
                @if($category->kuota)
                    <span class="badge bg-light-info text-info">{{ $pesertaCount }} / {{ $category->kuota }} kuota</span>
                @endif
                <span class="badge bg-light-info text-info">Max {{ $category->max_registrations_per_school ?? 1 }} pasukan/sekolah</span>
                <span class="badge {{ $category->judges->isNotEmpty() ? 'bg-light-primary text-primary' : 'bg-warning-subtle text-warning' }}">
                    <i class="ti ti-users"></i>
                    {{ $category->judges->isNotEmpty() ? $category->judges->pluck('name')->implode(', ') : 'Belum ada juri' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Tab navigasi ────────────────────────────────────────────────── --}}
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <button type="button" class="nav-link {{ $tab === 'grup' ? 'active' : '' }}" wire:click="pilihTab('grup')">
                <i class="ti ti-users-group me-1"></i> Grup &amp; Juri
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link {{ $tab === 'seri' ? 'active' : '' }}" wire:click="pilihTab('seri')">
                <i class="ti ti-list-numbers me-1"></i> Seri
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link {{ $tab === 'babak' ? 'active' : '' }}" wire:click="pilihTab('babak')">
                <i class="ti ti-flag me-1"></i> Babak
            </button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link {{ $tab === 'gelombang' ? 'active' : '' }}" wire:click="pilihTab('gelombang')">
                <i class="ti ti-clock-dollar me-1"></i> Gelombang
            </button>
        </li>
    </ul>

    {{-- ── Tab Grup & Juri ─────────────────────────────────────────────── --}}
    @if($tab === 'grup')
        <div class="card w-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2"><i class="ti ti-users-group me-1"></i>Grup</h6>
                <p class="text-muted fs-2">
                    Buat grup untuk membelah peserta jadi beberapa pool peringkat. Peserta yang belum
                    bergrup tetap dihitung pada peringkat umum. Grup <strong>tidak</strong> muncul di
                    formulir pendaftaran publik.
                </p>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Grup</label>
                        <input type="text" class="form-control" wire:model="groupName" placeholder="Grup A">
                        @error('groupName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Urutan</label>
                        <input type="number" class="form-control" wire:model="groupSortOrder" min="0" placeholder="0">
                        @error('groupSortOrder') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" wire:click="saveGroup">
                            <i class="ti ti-{{ $editingGroupId ? 'device-floppy' : 'plus' }}"></i>
                            {{ $editingGroupId ? 'Simpan' : 'Tambah Grup' }}
                        </button>
                    </div>
                </div>

                @if($editingGroupId)
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-light" wire:click="resetGroupForm">Batal edit</button>
                    </div>
                @endif

                @if($this->groupsByCategory->isEmpty() && $this->assignmentRows === [])
                    <p class="text-muted fs-2"><i>Belum ada grup. Tingkat ini dihitung sebagai satu peringkat.</i></p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Grup</th>
                                    <th>Peserta</th>
                                    <th>Juri yang menilai</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->assignmentRows as $row)
                                    @php
                                        $juriBaris = $this->groupJudgeIds->get($row['key'], collect())->all();
                                        $adalahGrup = $row['scope'] === \App\Models\CompetitionGroup::SCOPE_GROUP;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $row['label'] }}
                                            @unless($adalahGrup)
                                                <span class="badge bg-light text-muted border ms-1">{{ $row['scope'] }}</span>
                                            @endunless
                                        </td>
                                        <td>{{ $row['count'] }}</td>
                                        <td>
                                            @if($this->availableJudges->isEmpty())
                                                <span class="text-muted fs-2">Belum ada juri di event ini</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($this->availableJudges as $juri)
                                                        <div class="form-check form-check-inline me-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="juri-{{ $row['key'] }}-{{ $juri->id }}"
                                                                @checked(in_array($juri->id, $juriBaris, true))
                                                                wire:change="toggleGroupJudge('{{ $row['scope'] }}', {{ $adalahGrup ? $row['group_id'] : 'null' }}, {{ $juri->id }}, $event.target.checked)">
                                                            <label class="form-check-label fs-2" for="juri-{{ $row['key'] }}-{{ $juri->id }}">{{ $juri->name }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if($juriBaris === [])
                                                    <span class="badge bg-warning-subtle text-warning mt-1">
                                                        <i class="ti ti-alert-triangle me-1"></i>Belum ada juri
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($adalahGrup)
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editGroup({{ $row['group_id'] }})" title="Edit grup">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteGroup({{ $row['group_id'] }})"
                                                    wire:confirm="Hapus grup {{ $row['label'] }}? Pesertanya tidak ikut terhapus, hanya kembali ke peringkat umum. Penugasan jurinya ikut dilepas." title="Hapus grup">
                                                    <i class="ti ti-trash fs-4"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2 mb-0">
                    <i class="ti ti-info-circle"></i>
                    Centang juri yang menilai tiap baris di atas. <strong>Lembar nilainya tidak dipilih di
                    sini</strong>: setiap peserta memakai lembar sesuai <strong>serinya</strong>, yang
                    ditetapkan panitia saat <strong>Daftar Ulang</strong>. Satu juri grup karena itu bisa
                    menilai lebih dari satu seri.
                </div>
            </div>
        </div>
    @elseif($tab === 'seri')

        {{-- ── Tab Seri ────────────────────────────────────────────────── --}}
        <div class="card w-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2"><i class="ti ti-list-numbers me-1"></i>Seri</h6>
                <p class="text-muted fs-2">
                    Seri adalah urutan perlombaan (mis. Seri A &amp; Seri B pada LOBB) dan
                    <strong>penentu lembar nilai</strong>: rubriknya mengikuti seri. Juri <strong>tidak</strong>
                    lagi diikat ke seri — penugasannya ada di tab <strong>Grup &amp; Juri</strong>. Dua pasukan
                    di grup yang sama boleh memakai seri berbeda. Seri ditetapkan panitia saat daftar ulang.
                </p>

                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Seri</label>
                        <input type="text" class="form-control" wire:model="seriesName" placeholder="Seri A">
                        @error('seriesName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Urutan</label>
                        <input type="number" class="form-control" wire:model="seriesSortOrder" min="0" placeholder="0">
                        @error('seriesSortOrder') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" wire:click="saveSeries">
                            <i class="ti ti-{{ $editingSeriesId ? 'device-floppy' : 'plus' }}"></i>
                            {{ $editingSeriesId ? 'Simpan' : 'Tambah Seri' }}
                        </button>
                    </div>
                </div>

                @if($editingSeriesId)
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-light" wire:click="resetSeriesForm">Batal edit</button>
                    </div>
                @endif

                @if($this->seriesByCategory->isEmpty())
                    <p class="text-muted fs-2"><i>Belum ada seri. Seluruh peserta tingkat ini memakai lembar nilai yang sama.</i></p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Seri</th>
                                    <th>Peserta</th>
                                    <th>Rubrik</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->seriesByCategory as $series)
                                    @php $seriesInfo = $this->seriesRubrics->get($series->id); @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $series->name }}</td>
                                        <td>{{ $series->registrations_count }}</td>
                                        <td>
                                            @forelse($seriesInfo['rubrics'] ?? collect() as $nama)
                                                <span class="badge bg-light-primary text-primary">{{ $nama }}</span>
                                            @empty
                                                <span class="text-muted fs-2">Belum ada — atur lewat Format Nilai</span>
                                            @endforelse
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editSeries({{ $series->id }})" title="Edit seri">
                                                <i class="ti ti-edit fs-4"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteSeries({{ $series->id }})"
                                                wire:confirm="Hapus seri {{ $series->name }}? Pesertanya tidak ikut terhapus, hanya kembali memakai lembar nilai tanpa seri." title="Hapus seri">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2 mb-0">
                    <i class="ti ti-info-circle"></i>
                    Rubrik ditandai serinya di <strong>Format Nilai</strong>. Rubrik tanpa tanda seri
                    berlaku untuk semua seri.
                </div>
            </div>
        </div>
    @elseif($tab === 'babak')

        {{-- ── Tab Babak ───────────────────────────────────────────────── --}}
        <div class="card w-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2"><i class="ti ti-flag me-1"></i>Babak</h6>
                <p class="text-muted fs-2">
                    Babak memisahkan rubrik penilaian. Babak bertipe <strong>Final</strong> hanya menilai
                    peserta yang diloloskan; juara final dihitung dari nilai babak final saja, sedangkan
                    nilai penyisihan hanya menentukan siapa yang lolos.
                </p>

                <div class="row g-2 align-items-end mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Nama Babak</label>
                        <input type="text" class="form-control" wire:model="roundName" placeholder="Final">
                        @error('roundName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis</label>
                        <select class="form-select" wire:model="roundType">
                            <option value="preliminary">Penyisihan</option>
                            <option value="final">Final</option>
                        </select>
                        @error('roundType') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Urutan</label>
                        <input type="number" class="form-control" wire:model="roundSortOrder" min="0" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" wire:click="saveRound">
                            <i class="ti ti-{{ $editingRoundId ? 'device-floppy' : 'plus' }}"></i>
                            {{ $editingRoundId ? 'Simpan' : 'Tambah' }}
                        </button>
                    </div>
                </div>
                @if($editingRoundId)
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-light" wire:click="resetRoundForm">Batal edit</button>
                    </div>
                @endif

                <h6 class="fw-semibold mb-2">Daftar Babak</h6>
                @if($this->roundsByCategory->isEmpty())
                    <p class="text-muted fs-2"><i>Belum ada babak. Seluruh rubrik tingkat ini dianggap satu babak.</i></p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Babak</th>
                                    <th>Jenis</th>
                                    <th>Rubrik</th>
                                    <th>Finalis</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->roundsByCategory as $round)
                                    @php
                                        $roundRubrics = $this->roundRubricNames->get($round->id, collect());
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $round->name }}</td>
                                        <td>
                                            <span class="badge {{ $round->isFinal() ? 'bg-warning-subtle text-warning' : 'bg-light-secondary text-secondary' }}">
                                                {{ $round->isFinal() ? 'Final' : 'Penyisihan' }}
                                            </span>
                                        </td>
                                        <td>
                                            @forelse($roundRubrics as $nama)
                                                <span class="badge bg-light-primary text-primary">{{ $nama }}</span>
                                            @empty
                                                <span class="text-muted fs-2">Belum ditandai</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @if($round->isFinal())
                                                {{ $round->round_registrations_count }} finalis
                                            @else
                                                <span class="text-muted fs-2">semua peserta</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($round->isFinal())
                                                <button class="btn btn-sm btn-outline-warning py-0 px-2 fs-2 me-1" wire:click="openQualifyPanel({{ $round->id }})">
                                                    <i class="ti ti-arrow-up"></i> Loloskan Top-N
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editRound({{ $round->id }})" title="Edit babak">
                                                <i class="ti ti-edit fs-4"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteRound({{ $round->id }})"
                                                wire:confirm="Hapus babak {{ $round->name }}? Nilai yang sudah tersimpan tidak ikut terhapus." title="Hapus babak">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="alert alert-info bg-primary-subtle text-primary border-0 fs-2 py-2 mb-0">
                    <i class="ti ti-info-circle"></i>
                    Rubrik ditandai babaknya di <strong>Format Nilai</strong>. Tanpa tanda babak, rubrik
                    dianggap berlaku untuk semua babak.
                </div>
            </div>
        </div>
    @elseif($tab === 'gelombang')
        <div class="card w-100">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2"><i class="ti ti-clock-dollar me-1"></i>Gelombang Harga</h6>
                <p class="text-muted fs-2">
                    Bagi biaya pendaftaran jadi beberapa rentang tanggal. Harga yang ditagih adalah harga
                    gelombang yang <strong>mencakup tanggal pendaftaran</strong>; di luar semua gelombang,
                    harga biasa yang dipakai. Gelombang tidak boleh tumpang tindih tanggalnya.
                </p>

                <div class="alert alert-light border fs-2 py-2 mb-3">
                    Harga biasa (di luar gelombang):
                    <strong>
                        @php $biayaBiasa = $category->registration_fee; @endphp
                        @if($biayaBiasa !== null)
                            Rp {{ number_format($biayaBiasa, 0, ',', '.') }}
                        @else
                            Gratis
                        @endif
                    </strong>
                    @if($category->gelombang->isEmpty())
                        &mdash; belum ada gelombang, jadi pendaftar selalu dikenai angka ini.
                    @endif
                </div>

                @if($this->feeWavesByCategory->isEmpty())
                    <p class="text-muted fs-2"><i>Belum ada gelombang. Semua pendaftar dikenai harga biasa.</i></p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Gelombang</th>
                                    <th>Mulai</th>
                                    <th>Sampai</th>
                                    <th>Harga</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->feeWavesByCategory as $wave)
                                    @php $berlaku = $wave->mencakup(now()); @endphp
                                    <tr class="{{ $berlaku ? 'table-success' : '' }}">
                                        <td class="fw-semibold">
                                            {{ $wave->name }}
                                            @if($berlaku)
                                                {{-- Penanda status sungguhan: harga inilah yang
                                                     ditagih hari ini (R-31). --}}
                                                <span class="badge bg-success-subtle text-success ms-1">Berlaku sekarang</span>
                                            @endif
                                        </td>
                                        <td>{{ $wave->starts_on->translatedFormat('d M Y') }}</td>
                                        <td>{{ $wave->ends_on->translatedFormat('d M Y') }}</td>
                                        <td class="fw-bold">Rp {{ number_format($wave->amount, 0, ',', '.') }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="editWave({{ $wave->id }})" title="Edit gelombang">
                                                <i class="ti ti-edit fs-4"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteWave({{ $wave->id }})"
                                                wire:confirm="Hapus gelombang {{ $wave->name }}? Tagihan pendaftar yang sudah masuk tidak ikut berubah." title="Hapus gelombang">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="row g-2 align-items-end mt-2">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nama Gelombang</label>
                        <input type="text" class="form-control" wire:model="waveName" placeholder="Gelombang 1">
                        @error('waveName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Mulai</label>
                        <input type="date" class="form-control" wire:model="waveStartsOn">
                        @error('waveStartsOn') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Sampai</label>
                        <input type="date" class="form-control" wire:model="waveEndsOn">
                        @error('waveEndsOn') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Harga</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" wire:model="waveAmount" placeholder="0" min="0" step="5000">
                        </div>
                        @error('waveAmount') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" wire:click="saveWave">
                            <i class="ti ti-{{ $editingWaveId ? 'device-floppy' : 'plus' }}"></i>
                            {{ $editingWaveId ? 'Simpan' : 'Tambah' }}
                        </button>
                    </div>
                </div>

                <small class="form-text text-muted fs-2 d-block mt-2">
                    Kedua tanggal ikut dihitung: gelombang yang berakhir 31 Okt masih berlaku pada 31 Okt.
                    Gelombang berikutnya boleh mulai 1 Nov.
                </small>

                @if($editingWaveId)
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-light" wire:click="resetWaveForm">Batal edit</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Uang Muka (DP) ──────────────────────────────────────────── --}}
        <div class="card w-100 mt-3">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-2"><i class="ti ti-wallet me-1"></i>Uang Muka (DP)</h6>
                <p class="text-muted fs-2">
                    Bila dinyalakan, portal pendaftar menawarkan pilihan <strong>bayar DP dulu</strong> atau
                    bayar penuh. DP yang diverifikasi panitia membuka pengisian data lebih awal, tetapi
                    <strong>tidak</strong> membuka tombol "Konfirmasi &amp; Kirim" — finalisasi tetap menunggu
                    pelunasan atau Technical Meeting.
                </p>

                @if($category->punyaDp())
                    @php
                        $tagihanKini = $category->biayaPada();
                        $contohDp = $tagihanKini ? $category->nominalDp((float) $tagihanKini) : null;
                    @endphp
                    <div class="alert alert-success border fs-2 py-2 mb-3">
                        <i class="ti ti-check me-1"></i> DP <strong>aktif</strong>:
                        @if($contohDp !== null && $tagihanKini)
                            Rp {{ number_format($contohDp, 0, ',', '.') }} dari tagihan
                            Rp {{ number_format($tagihanKini, 0, ',', '.') }}.
                        @else
                            nominal mengikuti tagihan tiap pendaftar.
                        @endif
                        @if($category->dp_days)
                            Pelunasan paling lambat <strong>{{ $category->dp_days }} hari</strong> setelah DP diterima.
                        @else
                            Tanpa tenggat pelunasan.
                        @endif
                    </div>
                @else
                    <div class="alert alert-light border fs-2 py-2 mb-3">
                        <i class="ti ti-info-circle me-1"></i> DP belum aktif — semua pendaftar membayar penuh.
                    </div>
                @endif

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="dpAktif" wire:model.live="dpAktif">
                    <label class="form-check-label fw-semibold" for="dpAktif">Tawarkan pembayaran DP</label>
                </div>

                @if($dpAktif)
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Cara menentukan DP</label>
                            <select class="form-select" wire:model.live="dpMode">
                                <option value="nominal">Nominal tetap (Rp)</option>
                                <option value="persen">Persentase tagihan</option>
                            </select>
                        </div>
                        @if($dpMode === 'nominal')
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Nominal DP</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" wire:model="dpAmount" min="0" step="5000" placeholder="0">
                                </div>
                                @error('dpAmount') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Persentase DP</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" wire:model="dpPercent" min="1" max="99" placeholder="30">
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('dpPercent') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tenggat pelunasan</label>
                            <div class="input-group">
                                <input type="number" class="form-control" wire:model="dpDays" min="1" max="365" placeholder="14">
                                <span class="input-group-text">hari</span>
                            </div>
                            @error('dpDays') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            <small class="form-text text-muted fs-2">Kosongkan bila tidak ada tenggat.</small>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" wire:click="simpanDp">
                                <i class="ti ti-device-floppy"></i> Simpan DP
                            </button>
                        </div>
                    </div>
                @else
                    <button type="button" class="btn btn-sm btn-light" wire:click="simpanDp">
                        <i class="ti ti-device-floppy"></i> Simpan (DP tetap mati)
                    </button>
                @endif

                <small class="form-text text-muted fs-2 d-block mt-2">
                    Mematikan DP tidak membatalkan DP yang sudah dibayar: uang yang sudah masuk tetap tercatat
                    dan sisa tagihannya tetap ditagih. Yang berubah hanya penawaran untuk pendaftar berikutnya.
                </small>
            </div>
        </div>
    @endif

    {{-- ── Modal Loloskan Top-N ───────────────────────────────────────── --}}
    @if($qualifyRoundId && $this->qualifyPreview['round'])
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5); z-index:1060;">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">
                            Loloskan ke Babak {{ $this->qualifyPreview['round']->name }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeQualifyPanel"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted fs-2">
                            Ambil N terbaik tiap grup dari nilai babak penyisihan. Daftar masih bisa diubah manual
                            dengan mencentang ulang sebelum disimpan.
                        </p>

                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ambil N terbaik per grup</label>
                                <input type="number" class="form-control" wire:model="qualifyTopN" min="1">
                            </div>
                            <div class="col-md-auto">
                                <button type="button" class="btn btn-outline-primary" wire:click="terapkanTopN">
                                    <i class="ti ti-refresh"></i> Terapkan
                                </button>
                            </div>
                        </div>

                        @foreach($this->qualifyPreview['groups'] as $bucket)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-semibold mb-0">
                                        {{ $bucket['group']?->name ?? 'Belum bergrup' }}
                                        <span class="text-muted fw-normal fs-2">({{ count($bucket['rows']) }} peserta)</span>
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-link py-0 px-1 fs-2 text-decoration-none"
                                        wire:click="toggleQualifyGroup({{ $bucket['group']?->id ?? '' }})">
                                        centang / lepas semua
                                    </button>
                                </div>
                                <div class="bg-light p-2 rounded border">
                                    @foreach($bucket['rows'] as $row)
                                        @php
                                            $rowId = $row['registration']->id;
                                            $sudahFinalis = in_array((int) $rowId, $this->qualifyPreview['sudahFinalis'], true);
                                        @endphp
                                        <div class="d-flex align-items-center gap-2 py-1">
                                            <input class="form-check-input mt-0" type="checkbox"
                                                wire:model="qualifySelection.{{ $rowId }}"
                                                wire:click.prevent="toggleQualifySelection({{ $rowId }})"
                                                id="q_{{ $rowId }}">
                                            <label class="form-check-label flex-grow-1" for="q_{{ $rowId }}">
                                                <span class="badge bg-light-secondary text-secondary">#{{ $row['rank'] }}</span>
                                                {{ $row['registration']->display_name }}
                                                <span class="text-muted fs-2">— nilai {{ $row['final_total'] ?? $row['total'] }}</span>
                                                @if($sudahFinalis)
                                                    <span class="badge bg-success-subtle text-success">sudah finalis</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if(empty($this->qualifyPreview['groups']))
                            <p class="text-muted fs-2"><i>Belum ada peserta yang punya nilai pada babak penyisihan.</i></p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeQualifyPanel">Batal</button>
                        <button type="button" class="btn btn-primary" wire:click="saveQualify">
                            <i class="ti ti-arrow-up"></i> Loloskan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
