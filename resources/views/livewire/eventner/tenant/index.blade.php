<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Tenant / Stand Bazaar</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Tenant / Stand Bazaar</li>
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
        <!-- Panel List Tenant -->
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Tenant</h5>
                </div>
                <div class="card-body p-4">
                    @if($this->tenants->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="fw-semibold text-muted">Belum ada Tenant / Stand Bazaar</h5>
                            <p>Tambahkan stand makanan, minuman, bazaar, atau booth lainnya yang hadir di event Anda.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-0 border-0 fw-semibold text-dark">Logo</th>
                                        <th class="border-0 fw-semibold text-dark">Nama Tenant</th>
                                        <th class="border-0 fw-semibold text-dark">Tipe</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Urutan</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                        <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->tenants as $tenant)
                                        <tr>
                                            <td class="ps-0">
                                                @if($tenant->logo)
                                                    <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="object-fit-contain rounded border border-light" style="width: 48px; height: 48px;">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                                                        <i class="ti ti-building-store text-muted fs-5"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <h6 class="fw-semibold mb-1 text-primary">{{ $tenant->name }}</h6>
                                                @if($tenant->description)
                                                    <span class="fs-2 text-muted">{{ Str::limit($tenant->description, 40) }}</span>
                                                @else
                                                    <span class="fs-2 text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-uppercase text-dark">{{ $tenant->type }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold text-dark">{{ $tenant->sort_order }}</span>
                                            </td>
                                            <td class="text-center">
                                                <button wire:click="toggleActive({{ $tenant->id }})" class="btn btn-sm border-0 btn-link">
                                                    @if($tenant->is_active)
                                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                                    @endif
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $tenant->id }})" title="Edit Tenant">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $tenant->id }})" title="Hapus Tenant" onclick="return confirm('Hapus tenant ini?') || event.stopImmediatePropagation()">
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

        <!-- Panel Form Tenant -->
        <div class="col-lg-4">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">{{ $isEditMode ? 'Edit Tenant' : 'Tambah Tenant' }}</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama Tenant / Booth</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Misal: Bakso Mantap" required>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kategori / Tipe</label>
                            <select class="form-select" wire:model="type" required>
                                <option value="culinary">Kuliner / Makanan</option>
                                <option value="beverage">Minuman</option>
                                <option value="bazaar">Bazaar / Stand</option>
                                <option value="souvenir">Souvenir / Merchandise</option>
                                <option value="activity">Activity / Booth Aktivitas</option>
                                <option value="other">Lainnya</option>
                            </select>
                            @error('type') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi <span class="text-muted">(Opsional)</span></label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Deskripsi singkat tenant/booth"></textarea>
                            @error('description') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Urutan Tampil <code>(Sort Order)</code></label>
                            <input type="number" class="form-control" wire:model="sort_order" min="0">
                            <small class="form-text text-muted">Angka lebih kecil tampil paling pertama.</small>
                            @error('sort_order') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Logo / Foto Tenant</label>
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
