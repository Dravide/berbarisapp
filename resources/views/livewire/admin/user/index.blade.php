<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Manajemen User</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Manajemen User</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-semibold">Data User</h5>
                <button type="button" class="btn btn-primary" wire:click="resetForm" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="ti ti-plus me-1"></i> Tambah User
                </button>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <input type="text" class="form-control" wire:model.live="search" placeholder="Cari nama, username, atau email...">
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterRole">
                        <option value="">Semua Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Eventner">Eventner</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead>
                        <tr class="text-muted fw-semibold">
                            <th scope="col" class="ps-0">Nama</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top">
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-0">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-{{ $user->role === 'Admin' ? 'primary' : 'info' }}-subtle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <span class="fw-semibold text-{{ $user->role === 'Admin' ? 'primary' : 'info' }} fs-4">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <h6 class="fw-semibold mb-0">{{ $user->name }}</h6>
                                            @if($user->eventner)
                                                <span class="fs-2 text-muted">{{ $user->eventner->nama_event }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $user->username }}</span>
                                </td>
                                <td>
                                    <span class="fs-3">{{ $user->email }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $user->role === 'Admin' ? 'primary' : 'info' }}-subtle text-{{ $user->role === 'Admin' ? 'primary' : 'info' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        @if($user->id !== auth()->id())
                                            <button type="button" class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}"
                                                wire:click="toggleActive({{ $user->id }})"
                                                title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="ti ti-{{ $user->is_active ? 'lock' : 'lock-open' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                wire:click="resetPassword({{ $user->id }})"
                                                onclick="return confirm('Reset password user ini ke default (password)?') || event.stopImmediatePropagation()"
                                                title="Reset Password">
                                                <i class="ti ti-key"></i>
                                            </button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-info" wire:click="edit({{ $user->id }})" title="Edit">
                                            <i class="ti ti-pencil"></i>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="delete({{ $user->id }})"
                                                onclick="return confirm('Hapus user ini? Semua data terkait juga akan dihapus.') || event.stopImmediatePropagation()"
                                                title="Hapus">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Tidak ada data user ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form User -->
    <div wire:ignore.self class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="userModalLabel">{{ $isEditMode ? 'Edit' : 'Tambah' }} User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="save">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Nama lengkap">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" wire:model="username" placeholder="Username untuk login">
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" placeholder="Alamat email">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Password
                                    @if($isEditMode)
                                        <span class="text-secondary">(Kosongkan jika tidak diubah)</span>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password" placeholder="{{ $isEditMode ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' }}">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select @error('role') is-invalid @enderror" wire:model="role">
                                    <option value="Admin">Admin</option>
                                    <option value="Eventner">Eventner</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="is_active" style="width: 48px; height: 24px;">
                                    <label class="form-check-label ms-2" for="is_active">
                                        {{ $is_active ? 'Aktif' : 'Nonaktif' }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer px-0 border-0 pt-3">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click="resetForm">Batal</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove>Simpan</span>
                                <span wire:loading>Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('close-modal', () => {
                var myModalEl = document.getElementById('userModal');
                var modal = bootstrap.Modal.getInstance(myModalEl);
                if (modal) modal.hide();
            });

            Livewire.on('open-modal', () => {
                var myModal = new bootstrap.Modal(document.getElementById('userModal'));
                myModal.show();
            });
        });
    </script>
@endsection
