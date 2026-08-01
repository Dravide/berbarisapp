<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Format Penilaian</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Pembuat Format Penilaian</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Pilih tingkat lomba --}}
    <div class="row mb-4">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-primary text-white"><i class="ti ti-category"></i></span>
                <select class="form-select" wire:model.live="activeTab">
                    <option value="">Semua Tingkat (Global)</option>
                    @foreach($this->competitionCategories as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if($activeTab !== '')
        <div class="col-md-7 d-flex align-items-center gap-2">
            <span class="text-primary fw-semibold">
                <i class="ti ti-check me-1"></i> Format untuk: {{ $this->competitionCategories->firstWhere('id', $activeTab)?->full_name }}
            </span>
        </div>
        @endif
    </div>

    <div class="row">
        {{-- Panel Utama Builder --}}
        <div class="col-md-9">
            <div class="card w-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Struktur Rubrik Penilaian</h5>
                </div>
                <div class="card-body bg-light">

                    @if($this->categories->isEmpty())
                        <div class="text-center py-5">
                            <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" width="150" alt="empty" class="mb-3 opacity-50">
                            <h5 class="fw-semibold text-muted">Belum ada Kategori Penilaian</h5>
                            <p>Mulai dengan menambahkan kategori utama di panel kanan.</p>
                        </div>
                    @else
                        {{-- List Kategori Utama --}}
                        <div class="accordion" id="accordionCategories" wire:sort="reorderCategories">
                            @foreach($this->categories as $category)
                            <div class="accordion-item mb-3 border bg-white" wire:key="cat-{{ $category->id }}" wire:sort:item="{{ $category->id }}">
                                <h2 class="accordion-header d-flex align-items-center" id="headingCat-{{ $category->id }}">
                                    <div class="d-flex align-items-center px-2 py-2 text-muted cursor-grab" wire:sort:handle title="Seret untuk mengurutkan">
                                        <i class="ti ti-grip-vertical fs-5"></i>
                                    </div>
                                    @if($editingCategoryId == $category->id)
                                        <div class="d-flex align-items-center gap-2 flex-grow-1 px-3 py-2">
                                            <input type="text" class="form-control form-control-sm" wire:model="editCategoryName" wire:keydown.enter="saveEditCategory" wire:keydown.escape="cancelEditCategory" placeholder="Nama kategori...">
                                            <button class="btn btn-sm btn-success" wire:click="saveEditCategory" title="Simpan"><i class="ti ti-check"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="cancelEditCategory" title="Batal"><i class="ti ti-x"></i></button>
                                        </div>
                                    @else
                                        <button class="accordion-button collapsed fw-semibold fs-5 text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCat-{{ $category->id }}" aria-expanded="false" aria-controls="collapseCat-{{ $category->id }}">
                                            {{ $category->name }}
                                            @if($category->competitionCategory)
                                                <span class="badge bg-success-subtle text-success ms-2 fs-1">{{ $category->competitionCategory->full_name }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary ms-2 fs-1">Semua Kategori</span>
                                            @endif
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary border-0" wire:click="startEditCategory({{ $category->id }})" title="Edit nama kategori">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info border-0" wire:click="duplicateCategory({{ $category->id }})" title="Duplikat Kategori">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success border-0"
                                            type="button"
                                            data-copy-source-id="{{ $category->id }}"
                                            data-copy-source-name="{{ $category->name }}"
                                            onclick="event.stopPropagation(); openCopyToOffcanvas(this);"
                                            title="Salin ke Tingkat Lain">
                                            <i class="ti ti-arrow-right-circle"></i> Salin Ke
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger border-0" wire:click="deleteCategory({{ $category->id }})" title="Hapus Kategori" onclick="return confirm('Yakin hapus Kategori ini beserta SELURUH Sub-kategorinya?') || event.stopImmediatePropagation()">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    @endif
                                </h2>

                                <div id="collapseCat-{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="headingCat-{{ $category->id }}" wire:ignore.self>
                                    <div class="accordion-body bg-white pt-4" wire:sort="reorderSubCategories" wire:sort:group="subcategories" wire:sort:group-id="{{ $category->id }}">

                                        {{-- Form Tambah Sub Kategori --}}
                                        <div class="d-flex mb-4 gap-2 align-items-center p-3 bg-light border">
                                            <input type="text" class="form-control form-control-sm" wire:model="newSubCategoryNames.{{ $category->id }}" placeholder="Nama Sub-Kategori (Contoh: Gerakan Ditempat)">
                                            <button class="btn btn-sm btn-secondary text-nowrap" wire:click="addSubCategory({{ $category->id }})">
                                                <i class="ti ti-plus"></i> Sub
                                            </button>
                                        </div>

                                        {{-- List Sub Kategori --}}
                                        @foreach($category->subCategories as $subCat)
                                            <div class="card mb-3 border" wire:key="sub-{{ $subCat->id }}" wire:sort:item="{{ $subCat->id }}">
                                                <div class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center py-2">
                                                    @if($editingSubCategoryId == $subCat->id)
                                                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                            <input type="text" class="form-control form-control-sm" wire:model="editSubCategoryName" wire:keydown.enter="saveEditSubCategory" wire:keydown.escape="cancelEditSubCategory" placeholder="Nama sub-kategori...">
                                                            <button class="btn btn-sm btn-success" wire:click="saveEditSubCategory" title="Simpan"><i class="ti ti-check"></i></button>
                                                            <button class="btn btn-sm btn-outline-secondary" wire:click="cancelEditSubCategory" title="Batal"><i class="ti ti-x"></i></button>
                                                        </div>
                                                    @else
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="text-muted cursor-grab" wire:sort:handle title="Seret untuk mengurutkan"><i class="ti ti-grip-vertical"></i></span>
                                                            <h6 class="mb-0 fw-semibold text-secondary">{{ $subCat->name }}</h6>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                            <button class="btn btn-sm btn-outline-primary border-0 p-1" wire:click="startEditSubCategory({{ $subCat->id }})" title="Edit">
                                                                <i class="ti ti-pencil fs-5"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger border-0 p-1" wire:click="deleteSubCategory({{ $subCat->id }})" title="Hapus" onclick="return confirm('Hapus Sub-Kategori ini?') || event.stopImmediatePropagation()">
                                                                <i class="ti ti-x fs-5"></i>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="card-body p-3">

                                                    {{-- Table Kriteria --}}
                                                    @if($subCat->criterias->isNotEmpty())
                                                        <div class="table-responsive mb-3">
                                                            <table class="table table-sm align-middle mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th class="border-0 fw-semibold">Kriteria Penilaian</th>
                                                                        <th class="border-0 fw-semibold" width="35%">Pilihan Nilai (Skor)</th>
                                                                        <th class="border-0 fw-semibold text-center" width="70px">Bobot</th>
                                                                        <th class="border-0 fw-semibold text-center" width="80px">Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody wire:sort="reorderCriterias">
                                                                    @foreach($subCat->criterias as $crit)
                                                                        <tr wire:key="crit-{{ $crit->id }}" wire:sort:item="{{ $crit->id }}">
                                                                            <td class="fw-semibold">
                                                                                <span class="text-muted cursor-grab me-2" wire:sort:handle title="Seret"><i class="ti ti-grip-vertical"></i></span>
                                                                                {{ $crit->name }}
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-wrap gap-1">
                                                                                    @foreach($crit->score_options as $score)
                                                                                        @php $val = is_array($score) ? $score['score'] : $score; $lbl = is_array($score) ? ($score['label'] ?? null) : null; @endphp
                                                                                        <span class="badge bg-primary">{{ $val }}@if($lbl) <small class="opacity-75">({{ $lbl }})</small>@endif</span>
                                                                                    @endforeach
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                @if($crit->weight != 1)
                                                                                    <span class="badge bg-info text-dark">{{ $crit->weight }}x</span>
                                                                                @else
                                                                                    <span class="text-muted">1x</span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <div class="d-flex justify-content-center gap-1">
                                                                                    <button class="btn btn-sm btn-outline-primary p-1" wire:click="openCriteriaModal({{ $subCat->id }}, {{ $crit->id }})" title="Edit Kriteria">
                                                                                        <i class="ti ti-pencil"></i>
                                                                                    </button>
                                                                                    <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteCriteria({{ $crit->id }})" title="Hapus">
                                                                                        <i class="ti ti-trash"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <p class="text-muted fs-3 mb-3"><i>Belum ada kriteria di sub-kategori ini.</i></p>
                                                    @endif

                                                    {{-- Tambah Kriteria — satu tombol buka modal --}}
                                                    <div class="bg-light p-3 border border-dashed text-center">
                                                        <button class="btn btn-primary btn-sm" wire:click="openCriteriaModal({{ $subCat->id }})">
                                                            <i class="ti ti-plus me-1"></i>Tambah Kriteria
                                                        </button>
                                                        <small class="d-block text-muted mt-2">Nama, skor, bobot, dan label diatur dalam satu modal.</small>
                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach

                                        {{-- ========== PENGURANGAN NILAI untuk kategori ini ========== --}}
                                        <div class="mt-4 pt-3 border-top">
                                            <h6 class="fs-3 fw-semibold text-danger mb-3"><i class="ti ti-minus-circle me-1"></i> Rubrik Pengurangan Nilai untuk "{{ $category->name }}"</h6>

                                            @if($category->deductionCategories->isNotEmpty())
                                                @foreach($category->deductionCategories as $deductionCat)
                                                    <div class="card mb-3 border border-danger-subtle" wire:key="dedcat-{{ $deductionCat->id }}">
                                                        <div class="card-header bg-danger-subtle d-flex justify-content-between align-items-center py-2">
                                                            @if($editingDeductionCategoryId == $deductionCat->id)
                                                                <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                                    <input type="text" class="form-control form-control-sm" wire:model="editDeductionCategoryName" wire:keydown.enter="saveEditDeductionCategory" wire:keydown.escape="cancelEditDeductionCategory" placeholder="Nama kategori pengurangan...">
                                                                    <button class="btn btn-sm btn-success" wire:click="saveEditDeductionCategory" title="Simpan"><i class="ti ti-check"></i></button>
                                                                    <button class="btn btn-sm btn-outline-secondary" wire:click="cancelEditDeductionCategory" title="Batal"><i class="ti ti-x"></i></button>
                                                                </div>
                                                            @else
                                                                <h6 class="mb-0 fw-semibold text-danger">{{ $deductionCat->name }}</h6>
                                                                <div class="d-flex gap-1">
                                                                    <button class="btn btn-sm btn-outline-primary border-0 p-1" wire:click="startEditDeductionCategory({{ $deductionCat->id }})" title="Edit">
                                                                        <i class="ti ti-pencil fs-5"></i>
                                                                    </button>
                                                                    <button class="btn btn-sm btn-outline-danger border-0 p-1" wire:click="deleteDeductionCategory({{ $deductionCat->id }})" title="Hapus" onclick="return confirm('Hapus kategori pengurangan ini beserta seluruh kriterianya?') || event.stopImmediatePropagation()">
                                                                        <i class="ti ti-trash fs-5"></i>
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="card-body p-3">

                                                            {{-- List Kriteria Pengurangan --}}
                                                            @if($deductionCat->criterias->isNotEmpty())
                                                                <div class="table-responsive mb-3">
                                                                    <table class="table table-sm align-middle mb-0">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th class="border-0 fw-semibold">Kriteria Pengurangan</th>
                                                                                <th class="border-0 fw-semibold" width="40%">Opsi Pengurangan</th>
                                                                                <th class="border-0 fw-semibold text-center" width="80px">Aksi</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($deductionCat->criterias as $deductionCrit)
                                                                                @if($editingDeductionCriteriaId == $deductionCrit->id)
                                                                                <tr class="table-warning">
                                                                                    <td>
                                                                                        <input type="text" class="form-control form-control-sm" wire:model="editDeductionCriteriaName" wire:keydown.enter="saveEditDeductionCriteria" wire:keydown.escape="cancelEditDeductionCriteria" placeholder="Nama kriteria">
                                                                                    </td>
                                                                                    <td>
                                                                                        <input type="text" class="form-control form-control-sm" wire:model="editDeductionCriteriaOptions" placeholder="-5,-10,-15" wire:keydown.enter="saveEditDeductionCriteria">
                                                                                        <span class="text-muted fs-2">Negatif, pisah koma</span>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <div class="d-flex justify-content-center gap-1">
                                                                                            <button class="btn btn-sm btn-success p-1" wire:click="saveEditDeductionCriteria"><i class="ti ti-check"></i></button>
                                                                                            <button class="btn btn-sm btn-outline-secondary p-1" wire:click="cancelEditDeductionCriteria"><i class="ti ti-x"></i></button>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                                @else
                                                                                <tr>
                                                                                    <td class="fw-semibold">{{ $deductionCrit->name }}</td>
                                                                                    <td>
                                                                                        <div class="d-flex flex-wrap gap-1">
                                                                                            @foreach($deductionCrit->deduction_options as $opt)
                                                                                                <span class="badge bg-danger">{{ $opt }}</span>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </td>
                                                                                    <td class="text-center">
                                                                                        <div class="d-flex justify-content-center gap-1">
                                                                                            <button class="btn btn-sm btn-outline-primary p-1" wire:click="startEditDeductionCriteria({{ $deductionCrit->id }})"><i class="ti ti-pencil"></i></button>
                                                                                            <button class="btn btn-sm btn-outline-danger p-1" wire:click="deleteDeductionCriteria({{ $deductionCrit->id }})"><i class="ti ti-trash"></i></button>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                                @endif
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <p class="text-muted fs-3 mb-3"><i>Belum ada kriteria pengurangan.</i></p>
                                                            @endif

                                                            {{-- Form Tambah Kriteria Pengurangan --}}
                                                            <div class="bg-light p-3 border border-dashed">
                                                                <h6 class="fs-3 fw-semibold mb-2">Tambah Kriteria Pengurangan</h6>
                                                                <div class="row align-items-end g-2">
                                                                    <div class="col-md-5">
                                                                        <input type="text" class="form-control form-control-sm" wire:model="newDeductionCriteriaNames.{{ $deductionCat->id }}" placeholder="Nama (Cth: Terlambat masuk)">
                                                                    </div>
                                                                    <div class="col-md-5">
                                                                        <input type="text" class="form-control form-control-sm" wire:model="newDeductionCriteriaOptions.{{ $deductionCat->id }}" placeholder="Opsi pengurangan (Cth: -5,-10,-15)">
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <button class="btn btn-sm btn-danger w-100" wire:click="addDeductionCriteria({{ $deductionCat->id }})">
                                                                            <i class="ti ti-plus me-1"></i>Tambah
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <p class="text-muted fs-3 mb-3"><i>Kategori ini tidak punya pengurangan nilai.</i></p>
                                            @endif

                                            {{-- Form Tambah Kelompok Pengurangan --}}
                                            <div class="bg-light p-3 border border-dashed border-danger-subtle">
                                                <h6 class="fs-3 fw-semibold mb-2 text-danger"><i class="ti ti-plus me-1"></i> Tambah Kelompok Pengurangan</h6>
                                                @if(session()->has("error_dedcat_{$category->id}"))
                                                    <div class="text-danger fs-2 mb-2"><i class="ti ti-alert-circle"></i> {{ session("error_dedcat_{$category->id}") }}</div>
                                                @endif
                                                <div class="d-flex gap-2">
                                                    <input type="text" class="form-control form-control-sm" wire:model="newDeductionCategoryNames.{{ $category->id }}" placeholder="Nama kelompok (Cth: Pelanggaran Disiplin)">
                                                    <button class="btn btn-sm btn-danger text-nowrap" wire:click="addDeductionCategory({{ $category->id }})">
                                                        <i class="ti ti-plus me-1"></i> Kelompok
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- ========== END PENGURANGAN ========== --}}
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel Kanan --}}
        <div class="col-md-3">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Buat Kategori Utama</h5>
                    <p class="fs-3 text-muted">Contoh kategori: PBB, Formasi, atau Kostum. Rubrik pengurangan diatur di dalam masing-masing kategori.</p>

                    @if($activeTab !== '')
                        <div class="alert alert-info bg-info-subtle border-0 fs-2 py-2 mb-3">
                            <i class="ti ti-info-circle me-1"></i> Format akan disimpan untuk: <strong>{{ $this->competitionCategories->firstWhere('id', $activeTab)?->full_name }}</strong>
                        </div>
                    @endif

                    <form wire:submit="addCategory">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Kategori</label>
                            <input type="text" class="form-control @error('newCategoryName') is-invalid @enderror" wire:model="newCategoryName" placeholder="Masukkan nama kategori" required>
                            @error('newCategoryName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                            <i class="ti ti-plus me-1"></i> Tambah Kategori
                        </button>
                    </form>
                </div>
            </div>

            <div class="card w-100 mb-0 bg-primary-subtle border-0 mt-3">
                <div class="card-body">
                    <h6 class="fw-semibold text-primary"><i class="ti ti-info-circle me-1"></i> Petunjuk Pengisian Skor</h6>
                    <p class="fs-2 mb-0">Isi opsi nilai secara manual dengan pemisah koma (,). Kustomisasi ini membebaskan penilaian Anda, misal: <br><code>1, 2, 3, 4, 5</code> atau <br><code>50, 60, 70, 80, 90, 100</code>.</p>
                    <hr class="my-2">
                    <p class="fs-2 mb-0 text-danger"><i class="ti ti-minus-circle me-1"></i> Pengurangan nilai hanya mempengaruhi kategori penilaian tempat ia dibuat, bukan total keseluruhan.</p>
                </div>
            </div>

            <button type="button" class="btn btn-warning w-100 mt-3" data-bs-toggle="modal" data-bs-target="#previewModal">
                <i class="ti ti-eye me-1"></i> Pratinjau Juri
            </button>
        </div>
    </div>


    {{-- Modal Preview --}}
    <div wire:ignore.self class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-semibold" id="previewModalLabel">Pratinjau Lembar Penilaian Juri</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">

                    @if($this->categories->isEmpty())
                        <div class="alert alert-info border-0 bg-info-subtle">
                            Belum ada format yang dibuat. Silakan tambahkan pada panel utama.
                        </div>
                    @else
                        <div class="bg-white p-4 border mx-auto" style="max-width: 900px;">
                            <div class="text-center mb-4">
                                <h3 class="fw-semibold fs-6">LEMBAR PENILAIAN</h3>
                                <hr class="border-2 border-dark" />
                            </div>

                            @foreach($this->categories as $category)
                                <div class="mb-4">
                                    <div class="bg-dark text-white p-2 fw-semibold mb-3 fs-4">{{ $category->name }}</div>

                                    @foreach($category->subCategories as $subCat)
                                        <div class="ms-3 mb-3">
                                            <div class="fw-semibold bg-light p-2 mb-2 border">{{ $subCat->name }}</div>

                                            @if($subCat->criterias->isNotEmpty())
                                                <table class="table table-bordered mb-0">
                                                    <tbody>
                                                        @foreach($subCat->criterias as $crit)
                                                            <tr>
                                                                <td width="40%" class="fw-medium align-middle">{{ $crit->name }}</td>
                                                                <td width="60%" class="text-center align-middle">
                                                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                                                        @foreach($crit->score_options as $score)
                                                                            @php $sv = is_array($score) ? $score['score'] : $score; $lb = is_array($score) ? ($score['label'] ?? null) : null; @endphp
                                                                            <div class="form-check form-check-inline m-0">
                                                                                <input class="form-check-input" type="radio" name="preview_radio_{{ $crit->id }}" id="preview_rad_{{ $crit->id }}_{{ $loop->index }}">
                                                                                <label class="form-check-label px-2 py-1 border text-center d-flex flex-column align-items-center lh-1" for="preview_rad_{{ $crit->id }}_{{ $loop->index }}" style="min-width: 40px;">
                                                                                    <span>{{ $sv }}</span>
                                                                                    @if($lb) <small class="fs-1 opacity-75 mt-0">{{ $lb }}</small> @endif
                                                                                </label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- Preview: Pengurangan untuk kategori ini --}}
                                    @if($category->deductionCategories->isNotEmpty())
                                        <div class="ms-3 mb-3">
                                            <div class="fw-semibold bg-danger-subtle text-danger p-2 mb-2 border">Pengurangan Nilai</div>
                                            @foreach($category->deductionCategories as $deductionCat)
                                                <div class="mb-2">
                                                    @if($deductionCat->criterias->isNotEmpty())
                                                        <table class="table table-bordered mb-0">
                                                            <tbody>
                                                                @foreach($deductionCat->criterias as $deductionCrit)
                                                                    <tr>
                                                                        <td width="40%" class="fw-medium align-middle text-danger">{{ $deductionCrit->name }}</td>
                                                                        <td width="60%" class="text-center align-middle">
                                                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                                                <div class="form-check form-check-inline m-0">
                                                                                    <input class="form-check-input" type="radio" name="preview_deduction_{{ $deductionCrit->id }}" id="preview_ded_{{ $deductionCrit->id }}_0">
                                                                                    <label class="form-check-label px-2 py-1 border border-success text-success" for="preview_ded_{{ $deductionCrit->id }}_0" style="min-width: 30px; text-align: center;">
                                                                                        0
                                                                                    </label>
                                                                                </div>
                                                                                @foreach($deductionCrit->deduction_options as $option)
                                                                                    <div class="form-check form-check-inline m-0">
                                                                                        <input class="form-check-input" type="radio" name="preview_deduction_{{ $deductionCrit->id }}" id="preview_ded_{{ $deductionCrit->id }}_{{ $loop->index + 1 }}">
                                                                                        <label class="form-check-label px-2 py-1 border border-danger text-danger" for="preview_ded_{{ $deductionCrit->id }}_{{ $loop->index + 1 }}" style="min-width: 30px; text-align: center;">
                                                                                            {{ $option }}
                                                                                        </label>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <a href="{{ route('eventner.format-nilai.pdf') }}" target="_blank" class="btn btn-danger">
                        <i class="ti ti-file-type-pdf fs-5 me-1"></i> Cetak / Unduh PDF
                    </a>
                </div>
            </div>
        </div>
    </div>



<!-- Modal Kriteria (Nama + Bobot + Label Groups) -->
@if($showCriteriaModal)
<div class="modal fade show d-block" tabindex="-1" style="display:block; background-color: rgba(0,0,0,.5); z-index: 1050;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white fw-semibold">
                    <i class="ti ti-edit me-2"></i>{{ $criteriaModalTargetId ? 'Edit' : 'Tambah' }} Kriteria
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="closeCriteriaModal"></button>
            </div>
            <div class="modal-body">
                @if(session()->has('error_criteria_modal'))
                    <div class="alert alert-danger py-2 fs-3">{{ session('error_criteria_modal') }}</div>
                @endif

                {{-- Nama & Bobot --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nama Kriteria <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" wire:model="criteriaModalName" placeholder="Cth: Sikap Istirahat">
                        @error('criteriaModalName') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Bobot</label>
                        <input type="number" class="form-control" wire:model="criteriaModalWeight" min="0" step="0.5" value="1">
                    </div>
                </div>

                <hr class="my-4">

                {{-- Label Groups --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0"><i class="ti ti-tags text-primary me-1"></i> Kelompok Nilai (Label)</h6>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary" wire:click="addLabelRow"><i class="ti ti-plus me-1"></i>Baris</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="fillLabelPreset">⚡ Preset</button>
                    </div>
                </div>
                <p class="text-muted fs-3 mb-3">Kelompokkan nilai ke dalam label agar mudah dinilai juri. Kosongkan label untuk nilai biasa.</p>

                <table class="table table-sm align-middle mb-3 border">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold" width="30%">Label</th>
                            <th class="fw-semibold">Angka Skor</th>
                            <th class="text-center" width="50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($labelGroups as $idx => $group)
                        <tr>
                            <td>
                                <select class="form-select form-select-sm" wire:model="labelGroups.{{ $idx }}.label">
                                    <option value="">— Tanpa Label —</option>
                                    <option value="Kurang">Kurang</option>
                                    <option value="Cukup">Cukup</option>
                                    <option value="Baik">Baik</option>
                                    <option value="Sangat Baik">Sangat Baik</option>
                                    <option value="Memuaskan">Memuaskan</option>
                                    <option value="Istimewa">Istimewa</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" wire:model="labelGroups.{{ $idx }}.scores" placeholder="Cth: 23, 30 atau rentang 0 – 25">
                            </td>
                            <td class="text-center">
                                @if(count($labelGroups) > 1)
                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="removeLabelRow({{ $idx }})" title="Hapus"><i class="ti ti-x"></i></button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Preview --}}
                @php
                    $previewScores = [];
                    foreach($labelGroups as $g) {
                        $label = trim($g['label'] ?? '');
                        $scoresRaw = str_replace([' &ndash; ', ' - ', '&ndash;', ';'], ',', $g['scores'] ?? '');
                        foreach(array_filter(array_map('trim', explode(',', $scoresRaw))) as $s) {
                            $previewScores[] = $label ? "$s ($label)" : $s;
                        }
                    }
                @endphp
                @if(!empty($previewScores))
                <div class="bg-light p-3 rounded border">
                    <span class="fs-2 fw-semibold text-muted d-block mb-2">Preview:</span>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($previewScores as $ps)
                            <span class="badge bg-primary px-2 py-1">{{ $ps }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" wire:click="closeCriteriaModal">Batal</button>
                <button class="btn btn-primary" wire:click="saveCriteriaModal">
                    <i class="ti ti-check me-1"></i> {{ $criteriaModalTargetId ? 'Simpan Perubahan' : 'Tambah Kriteria' }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Offcanvas Salin Ke — selalu dirender, dibuka via JS native (tanpa Livewire buat open) --}}
<div class="bb-offcanvas-backdrop" id="bbOffcanvasBackdrop" style="display:none;" onclick="document.getElementById('bbOffcanvasSalinKe').style.display='none'; document.getElementById('bbOffcanvasBackdrop').style.display='none';"></div>
<div class="bb-offcanvas" id="bbOffcanvasSalinKe" style="display:none;">
    <div class="bb-offcanvas-header">
        <h5 style="margin:0; font-weight:600; font-size:1.05rem;">
            <i class="ti ti-arrow-right-circle me-1"></i> Salin Rubrik ke Tingkat Lain
        </h5>
        <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('bbOffcanvasSalinKe').style.display='none'; document.getElementById('bbOffcanvasBackdrop').style.display='none';"></button>
    </div>
    <div class="bb-offcanvas-body">
        <input type="hidden" id="bbCopySourceId">
        <div id="bbCopySourceInfo" class="alert alert-success border-0 bg-success-subtle text-success mb-3" style="display:none;">
            <i class="ti ti-check me-1"></i> Sumber: <strong id="bbCopySourceName"></strong>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Salin ke Tingkat</label>
            <select class="form-select" wire:model.live="copyToTargetCompetitionCategoryId">
                <option value="">― Pilih Tingkat Tujuan ―</option>
                @foreach($this->competitionCategories as $cc)
                    <option value="{{ $cc->id }}">{{ $cc->full_name }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">Struktur rubrik ini akan disalin sebagai kategori baru di tingkat tujuan.</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-secondary" onclick="document.getElementById('bbOffcanvasSalinKe').style.display='none'; document.getElementById('bbOffcanvasBackdrop').style.display='none';">
                <i class="ti ti-x me-1"></i> Batal
            </button>
            <button type="button" class="btn btn-success" id="bbBtnSalin" {{ !$copyToTargetCompetitionCategoryId ? 'disabled' : '' }} onclick="bbSalinKe()">
                <i class="ti ti-copy me-1"></i> Salin
            </button>
        </div>
    </div>
</div>
<script>
function openCopyToOffcanvas(btn) {
    document.getElementById('bbCopySourceId').value = btn.getAttribute('data-copy-source-id');
    document.getElementById('bbCopySourceName').textContent = btn.getAttribute('data-copy-source-name');
    document.getElementById('bbCopySourceInfo').style.display = 'block';
    document.getElementById('bbOffcanvasSalinKe').style.display = 'flex';
    document.getElementById('bbOffcanvasBackdrop').style.display = 'block';
}
function bbSalinKe() {
    var sid = document.getElementById('bbCopySourceId').value;
    if (!sid) return;
    var comp = window.Livewire && (Livewire.find('{{ $this->getId() }}') || Livewire.first());
    if (comp) comp.call('executeCopyTo', sid);
}
</script>

<style>
    .cursor-grab { cursor: grab; }
    .cursor-grab:active { cursor: grabbing; }
    [wire\:sort\:item] { transition: transform 0.15s ease, box-shadow 0.15s ease; }
    [wire\:sort\:item].sortable-ghost { opacity: 0.5; }

    /* Offcanvas custom — bukan Bootstrap offcanvas (di-disable template admin) */
    .bb-offcanvas-backdrop {
        position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1050;
    }
    .bb-offcanvas {
        position: fixed; top: 0; right: 0; height: 100%; width: 100%;
        max-width: 480px; background: #fff; z-index: 1051;
        box-shadow: -8px 0 30px rgba(0,0,0,.2);
        display: flex; flex-direction: column;
        animation: bbSlideIn .25s ease-out;
    }
    .bb-offcanvas-header {
        background: #198754; color: #fff; padding: 1rem;
        display: flex; justify-content: space-between; align-items: center;
    }
    .bb-offcanvas-body { padding: 1.25rem; overflow-y: auto; flex: 1; }
    @keyframes bbSlideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
</style>
