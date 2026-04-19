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

    @if (session()->has('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Panel List Juri -->
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Juri</h5>
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
                                        <th class="ps-0 border-0 fw-semibold text-dark">Data Juri</th>
                                        <th class="border-0 fw-semibold text-dark">Bagian / Kategori Penilaian</th>
                                        <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->judges as $judge)
                                        <tr>
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3" style="width: 40px; height: 40px; font-weight: bold; font-size: 16px;">
                                                        {{ strtoupper(substr($judge->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-semibold mb-1">{{ $judge->name }}</h6>
                                                        <p class="mb-0 text-muted fs-2">{{ $judge->phone_number ?? '-' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @if($judge->assessmentCategories->isEmpty())
                                                        <span class="badge bg-warning-subtle text-warning">Belum ada tugas</span>
                                                    @else
                                                        @foreach($judge->assessmentCategories as $cat)
                                                            <span class="badge bg-success-subtle text-success">{{ $cat->name }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $judge->id }})" title="Edit Juri">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $judge->id }})" title="Hapus Juri" onclick="return confirm('Hapus Juri ini?') || event.stopImmediatePropagation()">
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

        <!-- Panel Form -->
        <div class="col-lg-4">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">{{ $isEditMode ? 'Edit Data Juri' : 'Tambah Juri Baru' }}</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Misal: Juri PBB 1" required>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. Telepon <small class="text-muted">(Opsional)</small></label>
                            <input type="text" class="form-control" wire:model="phone_number" placeholder="Misal: 08123456789">
                            @error('phone_number') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        
                        <hr>
                        <h6 class="fw-semibold mb-3">Tugaskan Kategori (Checklist):</h6>
                        <div class="mb-4">
                            @if($this->availableCategories->isEmpty())
                                <p class="text-muted fs-2"><i>Belum ada format nilai. Silakan buat format penilaian terlebih dahulu.</i></p>
                            @else
                                <div class="bg-light p-3 rounded border">
                                    @foreach($this->availableCategories as $cat)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedCategories" value="{{ $cat->id }}" id="cat_{{ $cat->id }}">
                                            <label class="form-check-label fw-medium" for="cat_{{ $cat->id }}">
                                                {{ $cat->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @error('selectedCategories') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            @if($isEditMode)
                                <button type="button" class="btn btn-secondary flex-fill" wire:click="resetForm">Batal</button>
                            @endif
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
