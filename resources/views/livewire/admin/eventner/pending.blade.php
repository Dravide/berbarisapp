{{-- Modal detail & tolak HARUS di dalam elemen root ini: Livewire hanya
     memorph root element, jadi modal yang ditaruh setelah </div> penutup
     tidak pernah muncul setelah aksi wire:click. Pola yang sama dipakai
     pricing-settings.blade.php dan school/show.blade.php. --}}
<div>
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
                                        <button type="button" class="btn btn-light btn-sm rounded-2" wire:click="openDetailModal({{ $e->id }})" title="Detail & kontak">
                                            <i class="ti ti-info-circle"></i>
                                        </button>
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

{{-- Detail & Kontak Modal --}}
@if($showDetailModal && $detail)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-semibold"><i class="ti ti-user-search text-primary me-1"></i> Detail Pendaftaran</h6>
                    <button type="button" class="btn-close" wire:click="$set('showDetailModal', false)"></button>
                </div>
                <div class="modal-body">
                    {{-- Kontak --}}
                    <div class="mb-2 text-muted fs-2 fw-semibold text-uppercase">Kontak Pendaftar</div>
                    <div class="list-group list-group-flush mb-4">
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-user me-1"></i>Nama Penyelenggara</span>
                            <span class="fw-semibold text-end">{{ $detail['penyelenggara'] ?: '—' }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-at me-1"></i>Username</span>
                            <span class="fw-semibold text-end">{{ $detail['username'] ?: '—' }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-mail me-1"></i>Email</span>
                            @if($detail['email'])
                                <a href="mailto:{{ $detail['email'] }}" class="fw-semibold text-end text-decoration-none">{{ $detail['email'] }}</a>
                            @else
                                <span class="fw-semibold text-end">—</span>
                            @endif
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-brand-whatsapp me-1"></i>WhatsApp</span>
                            @if($detail['whatsapp_url'])
                                <a href="{{ $detail['whatsapp_url'] }}" target="_blank" rel="noopener" class="fw-semibold text-end text-decoration-none">{{ $detail['whatsapp'] }}</a>
                            @else
                                {{-- Nomor telepon tidak diminta saat mendaftar; isian ini
                                     baru ada kalau eventner mengisi Tautan Tambahan. --}}
                                <span class="text-muted text-end fst-italic">Belum diisi</span>
                            @endif
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-brand-instagram me-1"></i>Instagram</span>
                            @if($detail['instagram'])
                                <a href="{{ $detail['instagram'] }}" target="_blank" rel="noopener" class="fw-semibold text-end text-decoration-none text-truncate" style="max-width: 16rem;">{{ $detail['instagram'] }}</a>
                            @else
                                <span class="text-muted text-end fst-italic">Belum diisi</span>
                            @endif
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-brand-tiktok me-1"></i>TikTok</span>
                            @if($detail['tiktok'])
                                <a href="{{ $detail['tiktok'] }}" target="_blank" rel="noopener" class="fw-semibold text-end text-decoration-none text-truncate" style="max-width: 16rem;">{{ $detail['tiktok'] }}</a>
                            @else
                                <span class="text-muted text-end fst-italic">Belum diisi</span>
                            @endif
                        </div>
                    </div>

                    {{-- Info pendaftaran --}}
                    <div class="mb-2 text-muted fs-2 fw-semibold text-uppercase">Info Pendaftaran</div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-calendar-event me-1"></i>Tanggal Daftar</span>
                            <span class="fw-semibold text-end">{{ $detail['terdaftar']->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-route me-1"></i>Sumber</span>
                            <span class="fw-semibold text-end">{{ $detail['sumber'] }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-package me-1"></i>Paket</span>
                            <span class="fw-semibold text-end">{{ $detail['paket'] }}</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span class="text-muted"><i class="ti ti-credit-card me-1"></i>Pembayaran</span>
                            @if($detail['dibayar'])
                                <span class="badge bg-success-subtle text-success-emphasis rounded-1">Sudah dibayar</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis rounded-1">Belum dibayar</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm rounded-2" wire:click="$set('showDetailModal', false)">Tutup</button>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-danger btn-sm rounded-2" wire:click="openRejectModal({{ $detail['id'] }})">
                            <i class="ti ti-x"></i> Tolak
                        </button>
                        <button type="button" class="btn btn-success btn-sm rounded-2" wire:click="approve({{ $detail['id'] }})">
                            <i class="ti ti-check"></i> Setujui
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

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
</div>
