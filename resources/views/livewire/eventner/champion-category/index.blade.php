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
                    <span class="text-muted fw-semibold small"><i class="ti ti-filter me-1"></i> Kategori Lomba:</span>
                    @foreach($competitionCategories as $cc)
                        <button wire:click="selectCompetitionCategory({{ $cc->id }})"
                            class="btn btn-sm {{ $selectedCompetitionCategoryId == $cc->id ? 'btn-primary' : 'btn-outline-primary' }} px-3">
                            {{ $cc->name }}
                            <span class="badge {{ $selectedCompetitionCategoryId == $cc->id ? 'bg-white text-primary' : 'bg-primary' }} ms-1">{{ $cc->registrations_count }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eventner.champion-categories.pdf', ['competition_category_id' => $selectedCompetitionCategoryId]) }}"
                       class="btn btn-sm btn-danger px-3 fw-semibold" target="_blank">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                    </a>
                    @if(!$showForm && !$showRankTitleForm)
                        <button wire:click="create" class="btn btn-sm btn-primary px-3 fw-semibold">
                            <i class="ti ti-plus me-1"></i> Tambah Kategori
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Left: Form --}}
        @if($showForm)
            <div class="col-lg-5">
                <div class="card w-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white fw-semibold">
                            <i class="ti {{ $editingId ? 'ti-edit' : 'ti-plus' }} me-2"></i>
                            {{ $editingId ? 'Edit Kategori Juara' : 'Tambah Kategori Juara' }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Kategori Juara <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" class="form-control" placeholder="Contoh: Juara Umum, Pembaki Terbaik...">
                            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea wire:model="description" class="form-control" rows="2" placeholder="Opsional: keterangan tambahan..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Rubrik Penilaian yang Dihitung <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-3">Centang rubrik penilaian yang masuk ke perhitungan kategori juara ini.</p>
                            @error('selectedAssessmentCategories') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                            @foreach($assessmentCategories as $cat)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        wire:model="selectedAssessmentCategories"
                                        value="{{ $cat->id }}"
                                        id="ac_{{ $cat->id }}">
                                    <label class="form-check-label fw-semibold" for="ac_{{ $cat->id }}">
                                        {{ $cat->name }}
                                    </label>
                                    <span class="text-muted small ms-1">
                                        ({{ $cat->subCategories->sum(fn($s) => $s->criterias->count()) }} kriteria)
                                    </span>
                                </div>
                            @endforeach

                            @if($assessmentCategories->isEmpty())
                                <div class="text-muted small">
                                    <i class="ti ti-alert-circle me-1"></i>
                                    Belum ada rubrik penilaian. <a href="{{ route('eventner.format-nilai.builder') }}">Buat sekarang</a>.
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button wire:click="save" class="btn btn-primary px-4 fw-semibold" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save"><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                                <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                            </button>
                            <button wire:click="cancel" class="btn btn-outline-secondary px-4">Batal</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Rank Title Form --}}
        @if($showRankTitleForm)
            <div class="{{ $showForm ? 'col-lg-12' : 'col-lg-5' }}">
                <div class="card w-100 border-primary">
                    <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="ti ti-medal me-2"></i>
                            {{ $editingRankTitleId ? 'Edit Gelar Juara' : 'Tambah Gelar Juara' }}
                        </h5>
                        <button wire:click="resetRankTitleForm" class="btn btn-sm btn-light">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Nama Gelar <span class="text-danger">*</span></label>
                                <input type="text" wire:model="rankTitle" class="form-control" placeholder="Contoh: Juara Utama, Harapan 1...">
                                @error('rankTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Rank Awal <span class="text-danger">*</span></label>
                                <input type="number" wire:model="rankStart" class="form-control" min="1" placeholder="1">
                                @error('rankStart') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Rank Akhir <span class="text-danger">*</span></label>
                                <input type="number" wire:model="rankEnd" class="form-control" min="1" placeholder="3">
                                @error('rankEnd') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button wire:click="saveRankTitle" class="btn btn-primary w-100">
                                    <i class="ti ti-check me-1"></i> Simpan
                                </button>
                            </div>
                        </div>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="ti ti-info-circle me-1"></i> Contoh: Rank 1-3 = "Juara Utama", Rank 4-6 = "Harapan 1", dst.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Right: Rankings --}}
        <div class="{{ $showForm ? 'col-lg-7' : ($showRankTitleForm ? 'col-lg-7' : 'col-12') }}">
            @forelse($championCategories as $champion)
                @php
                    $rankingData = $rankings->get($champion->id, collect());
                @endphp
                <div class="card w-100 mb-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-trophy text-warning"></i>
                            <h5 class="mb-0 text-white fw-semibold">{{ $champion->name }}</h5>
                        </div>
                        <div class="d-flex gap-1">
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
                            @foreach($champion->assessmentCategories as $ac)
                                <span class="badge bg-primary-subtle text-primary rounded-pill">
                                    <i class="ti ti-check me-1"></i>{{ $ac->name }}
                                </span>
                            @endforeach
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
    </div>
</div>
