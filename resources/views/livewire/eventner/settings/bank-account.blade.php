<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Rekening Bank</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Pengaturan Event</li>
                            <li class="breadcrumb-item" aria-current="page">Rekening Bank</li>
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
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">
                        {{ $isEditMode ? 'Edit Rekening' : 'Tambah Rekening' }}
                    </h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="bank_name" placeholder="Contoh: BCA, Mandiri, BRI">
                            @error('bank_name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Rekening <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="account_number" placeholder="Contoh: 1234567890">
                            @error('account_number') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Atas Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="account_name" placeholder="Contoh: Panitia Lomba...">
                            @error('account_name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model.live="is_active" id="isActive">
                                <label class="form-check-label" for="isActive">Aktif (ditampilkan ke peserta)</label>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> {{ $isEditMode ? 'Update' : 'Simpan' }}
                            </button>
                            @if($isEditMode)
                                <button type="button" class="btn btn-light" wire:click="resetForm">Batal</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">Daftar Rekening</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Bank</th>
                                    <th>Nomor Rekening</th>
                                    <th>Atas Nama</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->accounts as $acc)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $acc->bank_name }}</td>
                                        <td class="font-monospace">{{ $acc->account_number }}</td>
                                        <td>{{ $acc->account_name }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm {{ $acc->is_active ? 'btn-success' : 'btn-light text-muted' }} rounded-pill px-3"
                                                wire:click="toggleActive({{ $acc->id }})">
                                                {{ $acc->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-light me-1" wire:click="edit({{ $acc->id }})">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light text-danger" wire:click="delete({{ $acc->id }})"
                                                onclick="return confirm('Hapus rekening ini?')">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="ti ti-building-bank fs-8 d-block mb-2"></i>
                                            Belum ada rekening. Tambahkan rekening untuk pembayaran pendaftaran.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
