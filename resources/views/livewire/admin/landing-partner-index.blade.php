<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Sponsor &amp; Media Partner</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Sponsor &amp; Media Partner</li>
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

    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">Daftar Partner Landing</h5>
                            <p class="text-muted fs-3 mb-0">Tampil di section "Sponsor &amp; Media Partner" pada laman depan, sesuai urutan.</p>
                        </div>
                        <button class="btn btn-primary" wire:click="createPartner"><i class="ti ti-plus me-1"></i> Tambah Partner</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Logo</th>
                                    <th>Tipe</th>
                                    <th>Link</th>
                                    <th>Urutan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partners as $partner)
                                    <tr>
                                        <td class="fw-semibold">{{ $partner->name }}</td>
                                        <td>
                                            @if($partner->logo)
                                                <img src="{{ Storage::url($partner->logo) }}" alt="{{ $partner->name }}" style="height: 32px; object-fit: contain;">
                                            @else
                                                <span class="text-muted fs-3">Teks saja</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $partner->type === 'medpart' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }}">
                                                {{ $partner->type === 'medpart' ? 'Media Partner' : 'Sponsor' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($partner->link)
                                                <a href="{{ $partner->link }}" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width: 180px;">{{ $partner->link }}</a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $partner->sort_order }}</td>
                                        <td>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    wire:click="toggleActive({{ $partner->id }})" @checked($partner->is_active) title="Aktif/nonaktif">
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" wire:click="editPartner({{ $partner->id }})"><i class="ti ti-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" wire:click="deletePartner({{ $partner->id }})"
                                                wire:confirm="Hapus partner {{ $partner->name }}?"><i class="ti ti-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada partner — section landing-nya belum tampil.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal form partner --}}
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $partnerId ? 'Edit Partner' : 'Tambah Partner' }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="savePartner">
                            <div class="mb-3">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipe</label>
                                <select class="form-select" wire:model="type">
                                    <option value="sponsor">Sponsor</option>
                                    <option value="medpart">Media Partner</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo (opsional)</label>
                                @if($logo_current)
                                    <div class="mb-2">
                                        <img src="{{ Storage::url($logo_current) }}" class="img-fluid rounded border p-1" style="max-height: 60px;">
                                        <small class="d-block text-muted mt-1">Logo saat ini — unggah baru untuk mengganti.</small>
                                    </div>
                                @endif
                                <input type="file" class="form-control @error('logo') is-invalid @enderror" wire:model="logo" accept="image/*">
                                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Link (opsional)</label>
                                <input type="url" class="form-control @error('link') is-invalid @enderror" wire:model="link" placeholder="https://...">
                                @error('link') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Urutan Tampil</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" wire:model="sort_order" min="0">
                                    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6 mb-3 d-flex align-items-end">
                                    <div class="form-check pb-3">
                                        <input class="form-check-input" type="checkbox" id="is_active" wire:model="is_active">
                                        <label class="form-check-label" for="is_active">Aktif (tampil di landing)</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('showModal', false)">Batal</button>
                        <button type="button" class="btn btn-primary px-4" wire:click="savePartner" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="savePartner"><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                            <span wire:loading wire:target="savePartner"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
