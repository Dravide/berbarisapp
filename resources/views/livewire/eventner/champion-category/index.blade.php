<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Kategori Juara</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">{{ $eventner->nama_event }}</li>
                            <li class="breadcrumb-item active" aria-current="page">Kategori Juara</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Competition Category Filter --}}
    <div class="card w-100 mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="input-group" style="max-width: 360px;">
                        <span class="input-group-text bg-primary text-white"><i class="ti ti-category"></i></span>
                        <select class="form-select" wire:model.live="selectedCompetitionCategoryId">
                            @foreach($competitionCategories as $cc)
                                <option value="{{ $cc->id }}">{{ $cc->full_name }} ({{ $cc->registrations_count }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eventner.champion-categories.pdf', ['competition_category_id' => $selectedCompetitionCategoryId]) }}"
                       class="btn btn-sm btn-danger px-3 fw-semibold" target="_blank">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                    </a>
                    <button wire:click="create" class="btn btn-sm btn-primary px-3 fw-semibold">
                        <i class="ti ti-plus me-1"></i> Tambah Kategori
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: Tambah/Edit Kategori Juara ========== --}}
    @if($showForm)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="cancel">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-semibold">
                        <i class="ti {{ $editingId ? 'ti-edit' : 'ti-plus' }} me-2"></i>
                        {{ $editingId ? 'Edit Kategori Juara' : 'Tambah Kategori Juara' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="cancel"></button>
                </div>
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row">
                        {{-- Left Column: Basic Info --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Kategori Juara <span class="text-danger">*</span></label>
                                <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Juara Umum, Pembaki Terbaik...">
                                @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi</label>
                                <textarea wire:model="description" class="form-control" rows="2" placeholder="Opsional: keterangan tambahan..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Jumlah Juara (Top N) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-users"></i></span>
                                    <input type="number" wire:model="quantity" class="form-control" min="1" placeholder="Contoh: 3, 6, 10...">
                                </div>
                                <p class="text-muted small mt-1 mb-0"><i class="ti ti-info-circle me-1"></i> Jumlah peringkat teratas yang akan ditampilkan.</p>
                                @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" wire:model="isPublic" id="isPublic" role="switch">
                                    <label class="form-check-label fw-semibold" for="isPublic">
                                        <i class="ti ti-world me-1"></i> Tampilkan di Laman Hasil
                                    </label>
                                </div>
                                <p class="text-muted small mt-1 mb-0"><i class="ti ti-info-circle me-1"></i> Jika aktif, tampil di halaman publik <strong>Hasil Perlombaan</strong>.</p>
                            </div>
                        </div>

                        {{-- Right Column: Rubrik & Tie Break --}}
                        <div class="col-md-6">
                            {{-- Rubrik Penilaian --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Rubrik Penilaian <span class="text-danger">*</span></label>
                                <p class="text-muted small mb-2">Centang rubrik yang masuk perhitungan juara.</p>
                                @error('selectedSubCategories') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                <div class="border rounded p-3" style="max-height: 260px; overflow-y: auto;">
                                    @foreach($rubrikByLevel as $levelGroup)
                                        <div class="mb-3">
                                            <div class="fw-bold text-primary small mb-1" style="text-transform: uppercase; letter-spacing: 0.5px;">
                                                <i class="ti ti-layers-subtract me-1"></i>{{ $levelGroup['level_name'] }}
                                            </div>
                                            @foreach($levelGroup['categories'] as $cat)
                                                @php
                                                    $catSubs = $cat->subCategories->pluck('id')->map(fn($id) => (string) $id)->toArray();
                                                    $selectedCount = count(array_intersect($catSubs, $selectedSubCategories));
                                                    $allChecked = count($catSubs) > 0 && $selectedCount === count($catSubs);
                                                    $someChecked = $selectedCount > 0;
                                                @endphp
                                                <div class="mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            wire:click="toggleCategory({{ $cat->id }})"
                                                            {{ $allChecked ? 'checked' : '' }}
                                                            data-indeterminate="{{ $someChecked && !$allChecked ? '1' : '0' }}"
                                                            id="ac_{{ $cat->id }}"
                                                            @if(empty($catSubs)) disabled @endif>
                                                        <label class="form-check-label fw-bold text-dark" for="ac_{{ $cat->id }}">
                                                            {{ $cat->name }}
                                                        </label>
                                                        <span class="text-muted small ms-1">
                                                            ({{ $cat->subCategories->sum(fn($s) => $s->criterias->count()) }} kriteria)
                                                        </span>
                                                    </div>
                                                    @if(count($catSubs) > 0)
                                                        <div class="ms-4 mt-1">
                                                            @foreach($cat->subCategories as $sub)
                                                                <div class="form-check mb-1">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        wire:model.live="selectedSubCategories"
                                                                        value="{{ $sub->id }}"
                                                                        id="asc_{{ $sub->id }}"
                                                                        @if(in_array((string) $sub->id, $selectedSubCategories)) checked @endif>
                                                                    <label class="form-check-label text-muted small" for="asc_{{ $sub->id }}">
                                                                        {{ $sub->name }} <span class="text-muted">({{ $sub->criterias->count() }})</span>
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    @if($rubrikByLevel->isEmpty())
                                        <div class="text-muted small">
                                            <i class="ti ti-alert-circle me-1"></i>
                                            Belum ada rubrik penilaian. <a href="{{ route('eventner.format-nilai.builder') }}">Buat sekarang</a>.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Tie Break --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold"><i class="ti ti-arrows-sort me-1"></i> Tie Break</label>
                                <p class="text-muted small mb-2">Jika skor sama, pemenang ditentukan oleh rubrik ini.</p>

                                <div class="border rounded p-3" style="max-height: 260px; overflow-y: auto;">
                                    @foreach($rubrikByLevel as $levelGroup)
                                        <div class="mb-3">
                                            <div class="fw-bold text-primary small mb-1" style="text-transform: uppercase; letter-spacing: 0.5px;">
                                                <i class="ti ti-layers-subtract me-1"></i>{{ $levelGroup['level_name'] }}
                                            </div>
                                            @foreach($levelGroup['categories'] as $cat)
                                                @php
                                                    $catSubs = $cat->subCategories->pluck('id')->map(fn($id) => (string) $id)->toArray();
                                                    $tbSelectedCount = count(array_intersect($catSubs, $selectedTiebreakSubCategories));
                                                    $tbAllChecked = count($catSubs) > 0 && $tbSelectedCount === count($catSubs);
                                                    $tbSomeChecked = $tbSelectedCount > 0;
                                                @endphp
                                                <div class="mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            wire:click="toggleTiebreakCategory({{ $cat->id }})"
                                                            {{ $tbAllChecked ? 'checked' : '' }}
                                                            data-indeterminate="{{ $tbSomeChecked && !$tbAllChecked ? '1' : '0' }}"
                                                            id="tb_ac_{{ $cat->id }}"
                                                            @if(empty($catSubs)) disabled @endif>
                                                        <label class="form-check-label fw-bold text-dark" for="tb_ac_{{ $cat->id }}">
                                                            {{ $cat->name }}
                                                        </label>
                                                    </div>
                                                    @if(count($catSubs) > 0)
                                                        <div class="ms-4 mt-1">
                                                            @foreach($cat->subCategories as $sub)
                                                                <div class="form-check mb-1">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        wire:model.live="selectedTiebreakSubCategories"
                                                                        value="{{ $sub->id }}"
                                                                        id="tb_asc_{{ $sub->id }}"
                                                                        @if(in_array((string) $sub->id, $selectedTiebreakSubCategories)) checked @endif>
                                                                    <label class="form-check-label text-muted small" for="tb_asc_{{ $sub->id }}">
                                                                        {{ $sub->name }} <span class="text-muted">({{ $sub->criterias->count() }})</span>
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="cancel" class="btn btn-outline-secondary px-4">Batal</button>
                    <button wire:click="save" class="btn btn-primary px-4 fw-semibold" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save"><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                        <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ========== MODAL: Tambah/Edit Gelar Juara ========== --}}
    @if($showRankTitleForm)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="resetRankTitleForm">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="mb-0 fw-semibold">
                        <i class="ti ti-medal me-2"></i>
                        {{ $editingRankTitleId ? 'Edit Gelar Juara' : 'Tambah Gelar Juara' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="resetRankTitleForm"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Gelar <span class="text-danger">*</span></label>
                        <input type="text" wire:model="rankTitle" class="form-control" placeholder="Contoh: Juara Utama, Harapan 1...">
                        @error('rankTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Rank Awal <span class="text-danger">*</span></label>
                            <input type="number" wire:model="rankStart" class="form-control" min="1" placeholder="1">
                            @error('rankStart') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Rank Akhir <span class="text-danger">*</span></label>
                            <input type="number" wire:model="rankEnd" class="form-control" min="1" placeholder="3">
                            @error('rankEnd') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="ti ti-info-circle me-1"></i> Contoh: Rank 1-3 = "Juara Utama", Rank 4-6 = "Harapan 1", dst.
                    </p>
                </div>
                <div class="modal-footer">
                    <button wire:click="resetRankTitleForm" class="btn btn-outline-secondary px-4">Batal</button>
                    <button wire:click="saveRankTitle" class="btn btn-primary px-4 fw-semibold">
                        <i class="ti ti-check me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ========== RANKINGS LIST (always full-width) ========== --}}
    @forelse($championCategories as $champion)
        @php
            $rankingData = $rankings->get($champion->id, collect());
        @endphp
        <div class="card w-100 mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-trophy text-warning"></i>
                    <h5 class="mb-0 text-white fw-semibold">{{ $champion->name }}</h5>
                    <span class="badge bg-white text-dark rounded-pill ms-2">Top {{ $champion->quantity }}</span>
                    @if($champion->is_public)
                        <span class="badge bg-success rounded-pill ms-1" title="Tampil di laman publik"><i class="ti ti-world"></i></span>
                    @endif
                </div>
                <div class="d-flex gap-1">
                    <a href="{{ route('eventner.champion-categories.pdf', [
                        'competition_category_id' => $selectedCompetitionCategoryId,
                        'champion_category_id' => $champion->id,
                    ]) }}" target="_blank" class="btn btn-sm btn-danger text-white" title="Unduh PDF Kategori Juara ini">
                        <i class="ti ti-file-type-pdf me-1"></i> PDF
                    </a>
                    @if(!empty($eventner->scoring_code))
                        <a href="{{ route('public.scoreboard.champion', ['scoringCode' => $eventner->scoring_code, 'championCategoryId' => $champion->id]) }}?category_id={{ $selectedCompetitionCategoryId }}"
                           target="_blank"
                           class="btn btn-sm btn-success text-white"
                           title="Buka Live Scoreboard Juara">
                            <i class="ti ti-device-tv me-1"></i> Scoreboard
                        </a>
                    @endif
                    <button wire:click="showAddRankTitle({{ $champion->id }})" class="btn btn-sm btn-warning" title="Kelola Gelar">
                        <i class="ti ti-medal me-1"></i> Gelar
                    </button>
                    <button wire:click="edit({{ $champion->id }})" class="btn btn-sm btn-light" title="Edit">
                        <i class="ti ti-edit"></i>
                    </button>
                    <button wire:click="delete({{ $champion->id }})" class="btn btn-sm btn-outline-light" title="Hapus" onclick="return confirm('Hapus kategori juara ini?')">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>

            {{-- Rank Title Badges --}}
            @if($champion->rankTitles->count() > 0)
                <div class="px-3 pt-3 pb-0">
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        @foreach($champion->rankTitles as $rt)
                            <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-2 py-1">
                                <i class="ti ti-medal me-1"></i>{{ $rt->title }}
                                <small class="text-muted ms-1">(Rank {{ $rt->rank_start }}-{{ $rt->rank_end }})</small>
                                <button wire:click="editRankTitle({{ $rt->id }})" class="btn btn p-0 ms-1 text-primary"><i class="ti ti-edit fs-3"></i></button>
                                <button wire:click="deleteRankTitle({{ $rt->id }})" class="btn btn p-0 ms-1 text-danger" onclick="return confirm('Hapus gelar ini?')"><i class="ti ti-x fs-3"></i></button>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Assessment category badges --}}
            <div class="px-3 pt-2 pb-0">
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @php
                        $groupedSubs = $champion->assessmentSubCategories->groupBy('assessment_category_id');
                    @endphp
                    @foreach($groupedSubs as $catId => $subs)
                        @php
                            $cat = $subs->first()->category;
                            $totalCount = $cat ? $cat->subCategories->count() : 0;
                        @endphp
                        @if($cat)
                            <span class="badge bg-primary-subtle text-primary rounded-pill" title="{{ $subs->pluck('name')->join(', ') }}">
                                <i class="ti ti-check me-1"></i>{{ $cat->name }}
                                @if($subs->count() < $totalCount)
                                    <span class="badge bg-primary text-white rounded-pill ms-1" style="font-size: 0.7rem;">
                                        {{ $subs->count() }}/{{ $totalCount }} sub
                                    </span>
                                @endif
                            </span>
                        @endif
                    @endforeach

                    {{-- Tiebreak badges --}}
                    @if($champion->tiebreakSubCategories->count() > 0)
                        @php
                            $tbGrouped = $champion->tiebreakSubCategories->groupBy('assessment_category_id');
                        @endphp
                        @foreach($tbGrouped as $catId => $subs)
                            @php
                                $cat = $subs->first()->category;
                            @endphp
                            @if($cat)
                                <span class="badge bg-warning-subtle text-dark rounded-pill" title="Tie Break: {{ $subs->pluck('name')->join(', ') }}">
                                    <i class="ti ti-arrows-sort me-1"></i>TB: {{ $cat->name }}
                                </span>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Ranking Table --}}
            <div class="card-body p-0">
                @if($rankingData->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center" style="width:60px;">
                                        <h6 class="fw-semibold mb-0">Rank</h6>
                                    </th>
                                    <th>
                                        <h6 class="fw-semibold mb-0">Peserta</h6>
                                    </th>
                                    <th class="text-center" style="width:130px;">
                                        <h6 class="fw-semibold mb-0">Gelar</h6>
                                    </th>
                                    <th class="text-end" style="width:100px;">
                                        <h6 class="fw-semibold mb-0">Total Nilai</h6>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rankingData as $ps)
                                    @php
                                        $rankClass = '';
                                        if ($ps['rank'] == 1) $rankClass = 'table-warning';
                                        elseif ($ps['rank'] == 2) $rankClass = 'table-light';
                                        elseif ($ps['rank'] == 3) $rankClass = 'table-info';
                                    @endphp
                                    <tr class="{{ $rankClass }}">
                                        <td class="text-center fw-bold">
                                            @if($ps['rank'] == 1)
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1">🥇 1</span>
                                            @elseif($ps['rank'] == 2)
                                                <span class="badge bg-secondary text-white rounded-pill px-2 py-1">🥈 2</span>
                                            @elseif($ps['rank'] == 3)
                                                <span class="badge bg-info text-white rounded-pill px-2 py-1">🥉 3</span>
                                            @else
                                                {{ $ps['rank'] }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($ps['participant']->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $ps['participant']->logo_sekolah) }}" class="rounded-circle border" width="32" height="32" style="object-fit:cover;" alt="">
                                                @else
                                                    <div class="bg-primary bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                                        <i class="ti ti-school text-primary"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $ps['participant']->nama_sekolah }}</div>
                                                    <div class="text-muted small">Pelatih: {{ $ps['participant']->nama_pelatih }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($ps['title'])
                                                <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-1">
                                                    <i class="ti ti-award me-1"></i>{{ $ps['title'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold fs-5 {{ $ps['rank'] <= 3 ? 'text-dark' : '' }}">{{ $ps['total'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted small mb-0"><i class="ti ti-info-circle me-1"></i> Belum ada data peserta atau nilai untuk kategori lomba ini.</p>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card w-100">
            <div class="card-body text-center py-5">
                <i class="ti ti-trophy-off fs-10 text-muted d-block mb-3"></i>
                <h5 class="fw-semibold text-muted">Belum Ada Kategori Juara</h5>
                <p class="text-muted small mb-3">Tambahkan kategori juara untuk menentukan rubrik penilaian mana yang dihitung.</p>
                <button wire:click="create" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Tambah Kategori Juara
                </button>
            </div>
        </div>
    @endforelse
</div>

@script
<script>
    const applyIndeterminate = () => {
        document.querySelectorAll('[data-indeterminate="1"]').forEach(el => {
            el.indeterminate = true;
        });
    };

    // Run on initial load
    applyIndeterminate();

    // Hook into Livewire morph cycle
    Livewire.hook('morph.updated', () => {
        applyIndeterminate();
    });
</script>
@endscript
