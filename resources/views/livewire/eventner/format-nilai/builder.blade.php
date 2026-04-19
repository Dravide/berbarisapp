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

    <div class="row">
        <!-- Panel Utama Builder -->
        <div class="col-md-9">
            <div class="card">
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
                        <!-- List Kategori Utama -->
                        <div class="accordion" id="accordionCategories">
                            @foreach($this->categories as $category)
                            <div class="accordion-item mb-3 border bg-white rounded">
                                <h2 class="accordion-header d-flex" id="headingCat-{{ $category->id }}">
                                    <button class="accordion-button collapsed fw-semibold fs-5 text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCat-{{ $category->id }}" aria-expanded="false" aria-controls="collapseCat-{{ $category->id }}">
                                        {{ $category->name }}
                                    </button>
                                    <button class="btn btn-danger rounded-0 border-0" wire:click="deleteCategory({{ $category->id }})" title="Hapus Kategori LENGKAP dengan isinya" onclick="return confirm('Yakin hapus Kategori ini beserta SELURUH Sub-kategorinya?') || event.stopImmediatePropagation()">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </h2>
                                
                                <div id="collapseCat-{{ $category->id }}" class="accordion-collapse collapse" aria-labelledby="headingCat-{{ $category->id }}" data-bs-parent="#accordionCategories" wire:ignore.self>
                                    <div class="accordion-body bg-white pt-4">
                                        
                                        <!-- Form Tambah Sub Kategori -->
                                        <div class="d-flex mb-4 gap-2 align-items-center p-3 rounded bg-light-secondary border">
                                            <input type="text" class="form-control" wire:model="newSubCategoryNames.{{ $category->id }}" placeholder="Nama Sub-Kategori (Contoh: Gerakan Ditempat)">
                                            <button class="btn btn-secondary text-nowrap" wire:click="addSubCategory({{ $category->id }})">
                                                <i class="ti ti-plus"></i> Sub Kategori
                                            </button>
                                        </div>

                                        <!-- List Sub Kategori -->
                                        @foreach($category->subCategories as $subCat)
                                            <div class="card mb-3 border border-secondary">
                                                <div class="card-header bg-secondary-subtle d-flex justify-content-between align-items-center py-2">
                                                    <h6 class="mb-0 fw-semibold text-secondary">{{ $subCat->name }}</h6>
                                                    <button class="btn btn-sm btn-outline-danger border-0" wire:click="deleteSubCategory({{ $subCat->id }})" title="Hapus Sub-Kategori" onclick="return confirm('Hapus Sub-Kategori ini?') || event.stopImmediatePropagation()">
                                                        <i class="ti ti-x fs-4"></i>
                                                    </button>
                                                </div>
                                                <div class="card-body p-3">
                                                    
                                                    <!-- Table Kriteria -->
                                                    @if($subCat->criterias->isNotEmpty())
                                                        <div class="table-responsive mb-3">
                                                            <table class="table table-sm table-bordered mb-0 align-middle">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Kriteria Penilaian</th>
                                                                        <th width="50%">Pilihan Nilai (Skor)</th>
                                                                        <th width="50px" class="text-center">Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($subCat->criterias as $crit)
                                                                        <tr>
                                                                            <td class="fw-semibold">{{ $crit->name }}</td>
                                                                            <td>
                                                                                <div class="d-flex flex-wrap gap-1">
                                                                                    @foreach($crit->score_options as $score)
                                                                                        <span class="badge bg-primary rounded-1">{{ $score }}</span>
                                                                                    @endforeach
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <button class="btn btn-sm btn-danger p-1" wire:click="deleteCriteria({{ $crit->id }})">
                                                                                    <i class="ti ti-trash"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <p class="text-muted fs-3 mb-3"><i>Belum ada kriteria di sub-kategori ini.</i></p>
                                                    @endif

                                                    <!-- Form Tambah Kriteria -->
                                                    <div class="bg-light p-3 rounded border border-dashed">
                                                        <h6 class="fs-3 fw-semibold mb-2">Tambah Kriteria Baru</h6>
                                                        @if(session()->has("error_{$subCat->id}"))
                                                            <div class="text-danger fs-2 mb-2"><i class="ti ti-alert-circle"></i> {{ session("error_{$subCat->id}") }}</div>
                                                        @endif
                                                        <div class="row align-items-end g-2">
                                                            <div class="col-md-5">
                                                                <input type="text" class="form-control form-control-sm" wire:model="newCriteriaNames.{{ $subCat->id }}" placeholder="Nama Kriteria (Cth: Sikap istirahat)">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <input type="text" class="form-control form-control-sm" wire:model="newCriteriaScores.{{ $subCat->id }}" placeholder="Skor dipisah koma (Cth: 50,60,70,80,90,100)">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button class="btn btn-sm btn-primary w-100" wire:click="addCriteria({{ $subCat->id }})">
                                                                    Tambahkan
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Panel Samping: Form Kategori Utama -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">Buat Kategori Utama</h5>
                    <p class="fs-3 text-muted">Contoh kategori: PBB, Formasi, atau Kostum.</p>
                    
                    <form wire:submit="addCategory">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control @error('newCategoryName') is-invalid @enderror" wire:model="newCategoryName" placeholder="Masukkan nama kategori" required>
                            @error('newCategoryName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                            <i class="ti ti-plus"></i> Tambah Kategori
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card bg-primary-subtle border-0">
                <div class="card-body">
                    <h6 class="fw-semibold text-primary"><i class="ti ti-info-circle"></i> Petunjuk Pengisian Skor</h6>
                    <p class="fs-2 mb-0">Isi opsi nilai secara manual dengan pemisah koma (,). Kustomisasi ini membebaskan penilaian Anda, misal: <br><code>1, 2, 3, 4, 5</code> atau <br><code>50, 60, 70, 80, 90, 100</code>.</p>
                </div>
            </div>

            <button type="button" class="btn btn-warning w-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#previewModal">
                <i class="ti ti-eye"></i> Tampilkan Pratinjau Juri
            </button>
        </div>
    </div>


    <!-- Modal Preview -->
    <div wire:ignore.self class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white fw-semibold" id="previewModalLabel">Pratinjau Lembar Penilaian Juri</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">

                    @if($this->categories->isEmpty())
                        <div class="alert alert-info border-0">
                            Belum ada format yang dibuat. Silakan tambahkan pada panel utama.
                        </div>
                    @else
                        <div class="bg-white p-4 border shadow-sm mx-auto" style="max-width: 900px;">
                            <div class="text-center mb-4">
                                <h3 class="fw-bold fs-6">LEMBAR PENILAIAN</h3>
                                <hr class="border-2 border-dark" />
                            </div>

                            @foreach($this->categories as $category)
                                <div class="mb-4">
                                    <div class="bg-dark text-white p-2 fw-bold mb-3 fs-4">{{ $category->name }}</div>
                                    
                                    @foreach($category->subCategories as $subCat)
                                        <div class="ms-3 mb-3">
                                            <div class="fw-bold bg-light p-2 mb-2 border border-secondary">{{ $subCat->name }}</div>
                                            
                                            @if($subCat->criterias->isNotEmpty())
                                                <table class="table table-bordered border-secondary mb-0">
                                                    <tbody>
                                                        @foreach($subCat->criterias as $crit)
                                                            <tr>
                                                                <td width="40%" class="fw-medium align-middle">{{ $crit->name }}</td>
                                                                <td width="60%" class="text-center align-middle">
                                                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                                                        @foreach($crit->score_options as $score)
                                                                            <div class="form-check form-check-inline m-0">
                                                                                <input class="form-check-input" type="radio" name="preview_radio_{{ $crit->id }}" id="preview_rad_{{ $crit->id }}_{{ $loop->index }}">
                                                                                <label class="form-check-label px-2 py-1 border rounded-circle" for="preview_rad_{{ $crit->id }}_{{ $loop->index }}" style="min-width: 30px; text-align: center;">
                                                                                    {{ $score }}
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
                            @endforeach
                        </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="{{ route('eventner.format-nilai.pdf') }}" target="_blank" class="btn btn-danger">
                        <i class="ti ti-file-type-pdf fs-5 me-1"></i> Cetak / Unduh PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
