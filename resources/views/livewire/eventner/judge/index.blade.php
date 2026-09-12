<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Daftar Juri Penilaian</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Manajemen Juri</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel List Juri -->
        <div class="col-lg-12">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Juri</h5>
                    <div class="d-flex align-items-center gap-2">
                        @if($this->judges->isNotEmpty())
                            <a href="{{ route('eventner.judges.kartu-akses') }}" target="_blank"
                               class="btn btn-outline-light btn-sm fw-semibold"
                               title="Cetak kartu akses semua juri — 2 lembar per juri">
                                <i class="ti ti-id-badge-2 me-1"></i> Kartu Akses Semua Juri
                            </a>
                        @endif
                        <button type="button" class="btn btn-light btn-sm fw-semibold" wire:click="openCreate">
                            <i class="ti ti-plus me-1"></i> Tambah Juri
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if($this->judges->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="fw-semibold text-muted">Belum ada Juri</h5>
                            <p>Silakan tambahkan profil juri di panel kanan.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-0 border-0 fw-semibold text-dark">Foto & Data Juri</th>
                                        <th class="border-0 fw-semibold text-dark">Bagian / Kategori Penilaian</th>
                                        <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->judges as $judge)
                                        <tr>
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    @if($judge->photo)
                                                        <img src="{{ asset('storage/' . $judge->photo) }}"
                                                             alt="{{ $judge->name }}"
                                                             class="rounded-circle me-3 object-fit-cover"
                                                             style="width: 48px; height: 48px;">
                                                    @else
                                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3"
                                                             style="width: 48px; height: 48px; font-weight: bold; font-size: 18px;">
                                                            {{ strtoupper(substr($judge->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h6 class="fw-semibold mb-1">{{ $judge->name }}</h6>
                                                        <p class="mb-0 text-muted fs-2">{{ $judge->phone_number ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                {{-- Rincian kategori tidak lagi dijejal di kolom —
                                                     dibuka lewat modal, dipisah per tingkat lomba. --}}
                                                @if($judge->assessmentCategories->isEmpty())
                                                    <span class="badge bg-warning-subtle text-warning">Belum ada tugas</span>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold"
                                                            wire:click="openCategoriesModal({{ $judge->id }})">
                                                        <i class="ti ti-list-check me-1"></i>
                                                        {{ $judge->assessmentCategories->count() }} Kategori Penilaian
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-secondary p-1 me-1" wire:click="openTabletModal({{ $judge->id }})" title="Akses Tablet Juri">
                                                    <i class="ti ti-device-tablet fs-4"></i>
                                                </button>
                                                <a href="{{ route('eventner.judges.kartu-akses', $judge->id) }}" target="_blank"
                                                   class="btn btn-sm btn-outline-secondary p-1 me-1"
                                                   title="Unduh Kartu Akses Juri (identitas + QR)">
                                                    <i class="ti ti-id-badge-2 fs-4"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-secondary p-1 me-1" wire:click="selectJudgeForPdf({{ $judge->id }})" title="Unduh Format Penilaian Juri">
                                                    <i class="ti ti-file-type-pdf fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $judge->id }})" title="Edit Juri">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $judge->id }})" title="Hapus Juri" wire:confirm="Hapus Juri ini?">
                                                    <i class="ti ti-trash fs-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Bagian / Kategori Penilaian Juri — dikelompokkan per tingkat lomba -->
    @if($selectedCategoriesJudgeId)
    @php $catJudge = $this->categoriesJudge; @endphp
    <div class="modal fade show d-block" tabindex="-1" style="display:block; background-color: rgba(0,0,0,.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white fw-semibold">
                        <i class="ti ti-list-check me-1"></i> Kategori Penilaian Juri
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCategoriesModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success border-0 bg-success-subtle text-success mb-3">
                        <i class="ti ti-user me-1"></i> Juri: <strong>{{ $catJudge?->name ?? '-' }}</strong>
                    </div>

                    @if($this->categoriesJudgeGrouped->isEmpty())
                        <p class="text-muted mb-0">
                            <i>Juri ini belum ditugaskan ke kategori penilaian mana pun.</i>
                        </p>
                    @else
                        @foreach($this->categoriesJudgeGrouped as $group)
                            <div class="mb-3">
                                <div class="fw-semibold text-primary mb-2">
                                    <i class="ti ti-school me-1"></i> {{ $group['name'] }}
                                </div>
                                <div class="bg-light p-3 rounded border">
                                    @foreach($group['items'] as $cat)
                                        <div class="d-flex align-items-center gap-2 {{ !$loop->last ? 'mb-2' : '' }}">
                                            <i class="ti ti-check text-success"></i>
                                            <span class="fw-medium">{{ $cat->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-primary"
                            wire:click="edit({{ $selectedCategoriesJudgeId }})">
                        <i class="ti ti-edit me-1"></i> Ubah Tugas
                    </button>
                    <button type="button" class="btn btn-secondary" wire:click="closeCategoriesModal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Akses Tablet Juri (QR + link) -->
    @if($selectedTabletJudgeId)
    @php $tablet = $this->tabletQr; @endphp
    <div class="modal fade show d-block" tabindex="-1" style="display:block; background-color: rgba(0,0,0,.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-semibold">
                        <i class="ti ti-device-tablet me-1"></i> Akses Tablet Juri
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeTabletModal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="alert alert-primary border-0 bg-primary-subtle text-primary mb-3 text-start">
                        <i class="ti ti-user me-1"></i> Juri: <strong>{{ $tablet['judge']->name ?? '-' }}</strong>
                    </div>

                    @if($tablet['image'])
                        <img src="{{ $tablet['image'] }}" alt="QR akses tablet juri"
                             class="img-fluid border rounded-3 mb-3" style="max-width: 240px;">
                    @else
                        <div class="alert alert-warning border-0 mb-3">
                            QR gagal dibuat. Pakai link di bawah.
                        </div>
                    @endif

                    <p class="text-muted fs-3 text-start mb-1">Link tablet (buka di tablet juri):</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" readonly value="{{ $tablet['url'] }}"
                               onclick="this.select()">
                        <a href="{{ $tablet['url'] }}" target="_blank" class="btn btn-outline-primary">
                            <i class="ti ti-external-link"></i> Buka
                        </a>
                    </div>

                    <div class="alert alert-light border text-start mb-0">
                        <p class="fs-3 mb-1">
                            <i class="ti ti-info-circle me-1"></i>
                            Buka link ini di tablet juri, lalu simpan sebagai bookmark. Satu tablet untuk satu juri.
                        </p>
                        <p class="fs-3 mb-0 text-danger">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Jangan bagikan link ini — siapa pun yang punya link bisa mengisi nilai.
                        </p>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger"
                            wire:click="regenerateAccessToken({{ $selectedTabletJudgeId }})"
                            wire:confirm="Ganti token? Link dan QR lama tidak akan berlaku lagi.">
                        <i class="ti ti-refresh me-1"></i> Ganti Token
                    </button>
                    <button type="button" class="btn btn-secondary" wire:click="closeTabletModal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Pilih Tingkat untuk Unduh Format PDF Juri -->
    @if($selectedJudgeId)
    <div class="modal fade show d-block" tabindex="-1" style="display:block; background-color: rgba(0,0,0,.5); z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white fw-semibold">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh Format Penilaian Juri
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="cancelPdfFilter"></button>
                </div>
                <div class="modal-body">
                    @php $pdfJudge = $this->judges->firstWhere('id', $selectedJudgeId); @endphp
                    <div class="alert alert-danger border-0 bg-danger-subtle text-danger mb-3">
                        <i class="ti ti-user me-1"></i> Juri: <strong>{{ $pdfJudge?->name }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Tingkat Lomba</label>
                        <select class="form-select" wire:model.live="selectedJudgePdfLevel">
                            <option value="">— Semua Tingkat —</option>
                            @foreach($this->judgePdfLevels as $level)
                                <option value="{{ $level['id'] }}">{{ $level['full_name'] }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">PDF hanya memuat kategori penilaian juri pada tingkat terpilih.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" wire:click="cancelPdfFilter">
                        <i class="ti ti-x me-1"></i> Batal
                    </button>
                    <a href="{{ route('eventner.judges.format-pdf', ['judge' => $selectedJudgeId, 'competitionCategoryId' => $selectedJudgePdfLevel]) }}"
                       target="_blank" class="btn btn-danger">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Tambah/Edit Juri (Bootstrap, selalu dirender) -->
    <div class="modal fade" id="judgeModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-semibold">
                        <i class="ti ti-user me-1"></i>{{ $isEditMode ? 'Edit Data Juri' : 'Tambah Juri Baru' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="save">
                        <!-- Foto Profile -->
                        <div class="mb-3">
                            <label class="form-label">Foto Profil</label>
                            <div class="text-center mb-2">
                                @if ($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="rounded-circle object-fit-cover" style="width: 80px; height: 80px;">
                                @elseif($currentPhotoPath)
                                    <img src="{{ asset('storage/' . $currentPhotoPath) }}" alt="Current" class="rounded-circle object-fit-cover" style="width: 80px; height: 80px;">
                                @else
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                        <i class="ti ti-user text-muted" style="font-size: 32px;"></i>
                                    </div>
                                @endif
                            </div>
                            <input type="file" class="form-control" wire:model="photo" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
                            @error('photo') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Misal: H. Ahmad Dahlan, S.Pd." required>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Telepon <small class="text-muted">(Opsional)</small></label>
                            <input type="text" class="form-control" wire:model="phone_number" placeholder="Misal: 08123456789">
                            @error('phone_number') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <hr>
                        <h6 class="fw-semibold mb-3">Tugaskan Kategori (Checklist):</h6>
                        <div class="mb-3">
                            @if($this->availableCategories->isEmpty())
                                <p class="text-muted fs-2"><i>Belum ada format nilai. Silakan buat format penilaian terlebih dahulu.</i></p>
                            @else
                                @foreach($this->availableCategoriesGrouped as $group)
                                    <div class="mb-3">
                                        <div class="fw-semibold text-primary mb-2">
                                            <i class="ti ti-school me-1"></i> {{ $group['name'] }}
                                        </div>
                                        <div class="bg-light p-3 rounded border">
                                            @foreach($group['items'] as $cat)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" wire:model="selectedCategories" value="{{ $cat->id }}" id="cat_{{ $cat->id }}">
                                                    <label class="form-check-label fw-medium" for="cat_{{ $cat->id }}">
                                                        {{ $cat->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            @error('selectedCategories') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary flex-fill" wire:loading.attr="disabled">
                                <i class="ti ti-{{ $isEditMode ? 'device-floppy' : 'plus' }}"></i> {{ $isEditMode ? 'Simpan' : 'Tambahkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    window.addEventListener('open-judge-modal', () => {
        const el = document.getElementById('judgeModal');
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
    });
    window.addEventListener('close-judge-modal', () => {
        const el = document.getElementById('judgeModal');
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).hide();
    });
});
</script>
