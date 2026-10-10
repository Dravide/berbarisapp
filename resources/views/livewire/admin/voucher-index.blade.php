<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Kode Promo Pendaftaran</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Kode Promo</li>
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
        <div class="col-lg-10">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">Daftar Kode Promo</h5>
                            <p class="text-muted fs-3 mb-0">Potongan biaya paket saat pendaftaran akun event. Kuota dihitung dari pendaftar yang sudah membayar.</p>
                        </div>
                        <button class="btn btn-primary" wire:click="createVoucher"><i class="ti ti-plus me-1"></i> Tambah Kode</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nilai</th>
                                    <th>Paket</th>
                                    <th>Terpakai</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vouchers as $voucher)
                                    <tr wire:key="voucher-{{ $voucher->id }}">
                                        <td class="fw-semibold font-mono">{{ $voucher->code }}</td>
                                        <td>
                                            @if($voucher->type === 'percent')
                                                {{ $voucher->value }}%
                                                @if($voucher->max_discount)
                                                    <span class="text-muted fs-3">(cap {{ number_format($voucher->max_discount, 0, ',', '.') }})</span>
                                                @endif
                                            @else
                                                Rp {{ number_format($voucher->value, 0, ',', '.') }}
                                            @endif
                                        </td>
                                        <td>{{ $voucher->plan?->name ?? 'Semua paket' }}</td>
                                        <td>
                                            {{ $voucher->hitungPemakaian() }} / {{ $voucher->max_uses ?? '∞' }}
                                        </td>
                                        <td class="fs-3">
                                            @if($voucher->starts_at || $voucher->ends_at)
                                                {{ $voucher->starts_at?->format('d M Y') ?? '—' }} – {{ $voucher->ends_at?->format('d M Y') ?? '—' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    wire:click="toggle({{ $voucher->id }})" @checked($voucher->is_active) title="Aktif/nonaktif">
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" wire:click="editVoucher({{ $voucher->id }})"><i class="ti ti-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $voucher->id }})"
                                                wire:confirm="Hapus kode {{ $voucher->code }}? Kode yang sudah dipakai tidak bisa dihapus."><i class="ti ti-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada kode promo.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal form voucher — satu root div, modal di dalamnya --}}
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $voucherId ? 'Edit Kode Promo' : 'Tambah Kode Promo' }}</h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit="save">
                            <div class="mb-3">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror" wire:model="code" placeholder="KOMUNITAS25">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Tipe</label>
                                    <select class="form-select" wire:model.live="type">
                                        <option value="percent">Persen (%)</option>
                                        <option value="flat">Nominal (Rp)</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Nilai <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        @if($type === 'percent')
                                            <input type="number" class="form-control @error('value') is-invalid @enderror" wire:model="value" min="1" max="90">
                                            <span class="input-group-text">%</span>
                                        @else
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control @error('value') is-invalid @enderror" wire:model="value" min="1">
                                        @endif
                                    </div>
                                    @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            @if($type === 'percent')
                                <div class="mb-3">
                                    <label class="form-label">Batas Maksimal Diskon (opsional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('max_discount') is-invalid @enderror" wire:model="max_discount" min="1" placeholder="Kosong = tanpa batas">
                                    </div>
                                    @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Kuota (opsional)</label>
                                    <input type="number" class="form-control @error('max_uses') is-invalid @enderror" wire:model="max_uses" min="1" placeholder="Kosong = tanpa batas">
                                    @error('max_uses') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Paket Target</label>
                                    <select class="form-select" wire:model="saas_plan_id">
                                        <option value="">Semua paket berbayar</option>
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Mulai (opsional)</label>
                                    <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" wire:model="starts_at">
                                    @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Berakhir (opsional)</label>
                                    <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" wire:model="ends_at">
                                    @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" wire:model="is_active">
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('showModal', false)">Batal</button>
                        <button type="button" class="btn btn-primary px-4" wire:click="save" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save"><i class="ti ti-device-floppy me-1"></i> Simpan</span>
                            <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
