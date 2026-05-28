<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Sponsor & Media Partner</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Sponsor & Media Partner</li>
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
        <!-- Panel List Sponsor -->
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Sponsor</h5>
                </div>
                <div class="card-body p-4">
                    @if($this->sponsors->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="fw-semibold text-muted">Belum ada Sponsor / Media Partner</h5>
                            <p>Tambahkan logo perusahaan, link sosmed/website, dan posisikan di urutan yang tepat.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-0 border-0 fw-semibold text-dark">Logo</th>
                                        <th class="border-0 fw-semibold text-dark">Nama Sponsor</th>
                                        <th class="border-0 fw-semibold text-dark">Tipe</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Urutan</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                        <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->sponsors as $sponsor)
                                        <tr>
                                            <td class="ps-0">
                                                @if($sponsor->logo)
                                                    <img src="{{ asset('storage/' . $sponsor->logo) }}" alt="{{ $sponsor->name }}" class="object-fit-contain rounded border border-light" style="width: 48px; height: 48px;">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                                                        <i class="ti ti-photo text-muted fs-5"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <h6 class="fw-semibold mb-1 text-primary">{{ $sponsor->name }}</h6>
                                                @if($sponsor->link)
                                                    <a href="{{ $sponsor->link }}" target="_blank" class="fs-2 text-muted text-decoration-underline"><i class="ti ti-link"></i> {{ Str::limit($sponsor->link, 30) }}</a>
                                                @else
                                                    <span class="fs-2 text-muted">No website link</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $sponsor->type }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold text-dark">{{ $sponsor->sort_order }}</span>
                                            </td>
                                            <td class="text-center">
                                                <button wire:click="toggleActive({{ $sponsor->id }})" class="btn btn-sm border-0 btn-link">
                                                    @if($sponsor->is_active)
                                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                                    @endif
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $sponsor->id }})" title="Edit Sponsor">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $sponsor->id }})" title="Hapus Sponsor" onclick="return confirm('Hapus sponsor ini?') || event.stopImmediatePropagation()">
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

        <!-- Panel Form Model -->
        <div class="col-lg-4">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">{{ $isEditMode ? 'Edit Sponsor / Partner' : 'Tambah Sponsor / Partner' }}</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama Sponsor / Perusahaan / Partner</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Misal: PT. Sampoerna Bakti" required>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kategori / Tipe</label>
                            <select class="form-select" wire:model="type" required>
                                <option value="sponsor">Sponsor Utama</option>
                                <option value="gold">Sponsor Emas (Gold)</option>
                                <option value="silver">Sponsor Perak (Silver)</option>
                                <option value="bronze">Sponsor Perunggu (Bronze)</option>
                                <option value="medpart">Media Partner</option>
                                <option value="partner">Event Partner</option>
                                <option value="supporting">Supporting Partner</option>
                            </select>
                            @error('type') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link Website / Instagram <span class="text-muted">(Opsional)</span></label>
                            <input type="url" class="form-control" wire:model="link" placeholder="https://instagram.com/sponsor">
                            @error('link') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil <code>(Sort Order)</code></label>
                            <input type="number" class="form-control" wire:model="sort_order" min="0">
                            <small class="form-text text-muted">Angka lebih kecil tampil paling pertama.</small>
                            @error('sort_order') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Logo Partner / Sponsor </label>
                            <input type="file" class="form-control" wire:model="logo" accept="image/*">
                            @if ($logo)
                                <div class="mt-2 text-center">
                                    <span class="fs-2 text-muted d-block mb-1">Preview Logo Baru:</span>
                                    <img src="{{ $logo->temporaryUrl() }}" class="object-fit-contain rounded border border-light" style="max-height: 100px;">
                                </div>
                            @elseif ($currentLogoPath)
                                <div class="mt-2 text-center">
                                    <span class="fs-2 text-muted d-block mb-1">Logo Saat Ini:</span>
                                    <img src="{{ asset('storage/' . $currentLogoPath) }}" class="object-fit-contain rounded border border-light" style="max-height: 100px;">
                                </div>
                            @endif
                            @error('logo') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
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
