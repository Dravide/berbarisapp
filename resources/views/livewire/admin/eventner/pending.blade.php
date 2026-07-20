<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h5 class="card-title fw-semibold mb-1">Pendaftaran Eventner Baru</h5>
                <p class="text-muted mb-0 small">Daftar eventner yang menunggu persetujuan</p>
            </div>
            <span class="badge bg-warning rounded-pill fs-2 px-3 py-1">
                {{ $pendingEventners->count() }} Menunggu
            </span>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                <i class="ti ti-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($pendingEventners->isEmpty())
            <div class="text-center py-5">
                <i class="ti ti-circle-check text-success" style="font-size: 3rem;"></i>
                <h6 class="mt-3 fw-semibold">Tidak ada pendaftaran yang menunggu</h6>
                <p class="text-muted small mb-0">Semua pendaftaran eventner sudah diproses.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="fw-semibold">Event</th>
                            <th class="fw-semibold">Penyelenggara</th>
                            <th class="fw-semibold">Kontak</th>
                            <th class="fw-semibold">Paket</th>
                            <th class="fw-semibold">Tanggal Daftar</th>
                            <th class="fw-semibold text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingEventners as $e)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $e->nama_event }}</div>
                                    <small class="text-muted">{{ $e->lokasi }}</small>
                                </td>
                                <td>{{ $e->user->name }}</td>
                                <td>
                                    <div><small>{{ $e->user->username }}</small></div>
                                    <div><small class="text-muted">{{ $e->user->email }}</small></div>
                                </td>
                                <td>
                                    @if($e->plan === 'free')
                                        <span class="badge bg-info-subtle text-info-emphasis rounded-1">Gratis</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success-emphasis rounded-1">Berbayar</span>
                                    @endif
                                </td>
                                <td>
                                    <div><small>{{ $e->created_at->format('d/m/Y') }}</small></div>
                                    <div><small class="text-muted">{{ $e->created_at->format('H:i') }}</small></div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-success btn-sm rounded-2" wire:click="approve({{ $e->id }})" wire:loading.attr="disabled">
                                            <i class="ti ti-check"></i> Setujui
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm rounded-2" wire:click="openRejectModal({{ $e->id }})" wire:loading.attr="disabled">
                                            <i class="ti ti-x"></i> Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
@if($showRejectModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-semibold"><i class="ti ti-alert-triangle text-danger me-1"></i> Tolak Pendaftaran</h6>
                    <button type="button" class="btn-close" wire:click="$set('showRejectModal', false)"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Berikan alasan penolakan agar pengguna dapat memperbaiki pendaftarannya.</p>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <small class="text-muted">(opsional)</small></label>
                        <textarea wire:model="rejectionReason" class="form-control @error('rejectionReason') is-invalid @enderror" rows="3" placeholder="Contoh: Data tidak lengkap, nama event sudah terdaftar..."></textarea>
                        @error('rejectionReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" wire:click="$set('showRejectModal', false)">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm rounded-2" wire:click="reject" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="ti ti-x"></i> Konfirmasi Tolak</span>
                        <span wire:loading><span class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
