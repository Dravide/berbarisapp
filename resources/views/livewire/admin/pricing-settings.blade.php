<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Harga & Paket SaaS</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Harga & Paket</li>
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
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">Daftar Paket</h5>
                            <p class="text-muted fs-3 mb-0">Paket aktif tampil di halaman harga publik (/pricing, landing) sesuai urutan.</p>
                        </div>
                        <button class="btn btn-primary" wire:click="createPlan"><i class="ti ti-plus me-1"></i> Tambah Paket</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Paket</th>
                                    <th>Harga</th>
                                    <th>Biaya Daftar</th>
                                    <th>Fitur</th>
                                    <th>Urutan</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $plan->name }}</span>
                                            @if($plan->highlight) <span class="badge bg-warning text-dark ms-1">Rekomendasi</span> @endif
                                            @if($plan->is_free) <span class="badge bg-secondary ms-1">Gratis</span> @endif
                                            @if($plan->is_contact) <span class="badge bg-info ms-1">Hubungi Admin</span> @endif
                                            @if($plan->description)
                                                <div class="text-muted fs-3">{{ $plan->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($plan->is_contact)
                                                <span class="text-muted">Kustom</span>
                                            @else
                                                Rp {{ number_format($plan->price, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($plan->is_contact)
                                                <span class="text-muted">-</span>
                                            @else
                                                Rp {{ number_format($plan->registration_fee, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($plan->is_free)
                                                <span class="text-muted fs-3">Dasar saja</span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary">{{ $plan->features->count() }} fitur</span>
                                            @endif
                                        </td>
                                        <td>{{ $plan->sort_order }}</td>
                                        <td>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    wire:click="toggleActive({{ $plan->id }})" @checked($plan->is_active) title="Aktif/nonaktif">
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" wire:click="editPlan({{ $plan->id }})"><i class="ti ti-pencil"></i></button>
                                            @unless($plan->is_free)
                                                <button class="btn btn-sm btn-outline-danger" wire:click="deletePlan({{ $plan->id }})"
                                                    wire:confirm="Hapus paket {{ $plan->name }}?"><i class="ti ti-trash"></i></button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada paket — halaman harga memakai paket bawaan (Gratis + Event Penuh).</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal form paket --}}
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $planId ? 'Edit Paket' : 'Tambah Paket' }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="savePlan">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Paket</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Urutan Tampil</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" wire:model="sort_order" min="0">
                                    @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Harga (Rp)</label>
                                    <input type="number" class="form-control @error('price') is-invalid @enderror" wire:model="price" min="0" step="1000" @disabled($is_free || $is_contact)>
                                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @if($is_contact) <div class="form-text">Harga disepakati langsung dengan admin.</div> @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Biaya Pendaftaran (Rp)</label>
                                    <input type="number" class="form-control @error('registration_fee') is-invalid @enderror" wire:model="registration_fee" min="0" step="1000" @disabled($is_free || $is_contact)>
                                    @error('registration_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">Dibayar saat mendaftar dengan paket ini. 0 = gratis.</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">Deskripsi Singkat</label>
                                    <input type="text" class="form-control" wire:model="description" placeholder="mis. Bayar sekali, aktif selama event">
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="is_free" wire:model.live="is_free">
                                        <label class="form-check-label" for="is_free">Paket gratis (harga Rp 0, tanpa fitur premium)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="is_contact" wire:model.live="is_contact">
                                        <label class="form-check-label" for="is_contact">Paket khusus (tombol Hubungi Admin, tanpa pembayaran online)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="highlight" wire:model="highlight" @disabled($is_free || $is_contact)>
                                        <label class="form-check-label" for="highlight">Tandai sebagai Rekomendasi</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="is_active" wire:model="is_active">
                                        <label class="form-check-label" for="is_active">Aktif (tampil di halaman harga)</label>
                                    </div>
                                </div>
                                @if($is_contact)
                                    <div class="col-12 mb-3">
                                        <label class="form-label">URL Kontak <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control @error('contact_url') is-invalid @enderror" wire:model="contact_url" placeholder="https://wa.me/62812xxxxx atau link form">
                                        @error('contact_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">Dibuka saat pengunjung klik tombol "Hubungi Admin" pada paket ini.</div>
                                    </div>
                                @endif
                            </div>

                            @unless($is_free)
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-semibold mb-2">Fitur Paket</h6>
                                    <p class="text-muted fs-3 mb-3">Centang fitur yang termasuk dalam paket ini. Fitur tidak dicentang akan terkunci bagi pengguna paket ini.</p>
                                    <div class="row">
                                        @foreach($plan_features as $key => $included)
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" wire:model="plan_features.{{ $key }}" id="pf_{{ $key }}">
                                                    <label class="form-check-label" for="pf_{{ $key }}">{{ config("eventner_features.{$key}.label") }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="form-text mb-0">Tambah fitur baru di config/eventner_features.php.</div>
                                </div>
                            @endunless
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('showModal', false)">Batal</button>
                        <button type="button" class="btn btn-primary px-4" wire:click="savePlan" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="savePlan"><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                            <span wire:loading wire:target="savePlan"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
