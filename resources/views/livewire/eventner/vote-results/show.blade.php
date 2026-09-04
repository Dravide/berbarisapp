<div>
    {{-- Page Header (Template Standard) --}}
    <div class="card bg-warning-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Detail Voter</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('eventner.vote-results.index') }}">Voting</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $registration->display_name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Kontingen Info --}}
    <div class="card w-100 mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                @if($registration->logo_sekolah)
                    <img src="{{ asset('storage/' . $registration->logo_sekolah) }}" class="rounded-circle border" width="64" height="64" style="object-fit:cover;" alt="">
                @else
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="ti ti-school fs-7"></i>
                    </div>
                @endif
                <div>
                    <h4 class="fw-semibold mb-1">{{ $registration->display_name }}</h4>
                    <p class="text-muted mb-0">
                        <span class="badge bg-primary-subtle text-primary me-2">{{ $registration->competitionCategory->name ?? '-' }}</span>
                        NPSN: {{ $registration->npsn }}
                    </p>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('eventner.vote-results.detail-pdf', $registration) }}" class="btn btn-sm btn-danger">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                    </a>
                    <a href="{{ route('eventner.vote-results.index') }}" class="btn btn-sm btn-light">
                        <i class="ti ti-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card mb-0 bg-primary-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Transaksi (PAID)</p>
                    <h3 class="fw-semibold text-primary mb-0">{{ number_format($summary->trx_count ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0 bg-success-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Vote</p>
                    <h3 class="fw-semibold text-success mb-0">{{ number_format($summary->total_votes ?? 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-0 bg-info-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Pendapatan</p>
                    <h3 class="fw-semibold text-info mb-0">Rp {{ number_format($summary->total_amount ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Voter --}}
    <div class="card w-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="ti ti-users me-2"></i>Daftar Voter</h5>
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari nama, email, ID transaksi...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-bottom-0" width="50px"><h6 class="fw-semibold mb-0">#</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Detail Voter</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Jumlah Vote</h6></th>
                            <th class="border-bottom-0 text-end"><h6 class="fw-semibold mb-0">Nominal Bayar</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Informasi Transaksi</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Waktu Transaksi</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Status</h6></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($voters as $i => $v)
                            <tr wire:key="voter-{{ $v->id }}">
                                <td class="text-muted">{{ $voters->firstItem() + $i }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                                            <i class="ti ti-user fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-semibold mb-0">{{ $v->voter_name }}</h6>
                                            <span class="text-muted small" style="font-size: 0.75rem;">{{ $v->voter_email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success text-white fw-bold px-3 py-1 fs-3 rounded-pill">{{ number_format($v->votes_earned) }} Vote</span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($v->amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="text-primary fw-semibold fs-2 bg-light px-2 py-1 rounded" id="tx-{{ $v->id }}">{{ $v->autogopay_transaction_id }}</code>
                                        <button type="button" class="btn btn p-0 text-muted" onclick="navigator.clipboard.writeText('{{ $v->autogopay_transaction_id }}'); alert('ID Transaksi berhasil disalin!');" title="Salin ID Transaksi">
                                            <i class="ti ti-copy fs-4"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-success-subtle text-success py-0 px-2" style="font-size: 0.7rem;">Bayar</span>
                                            <span class="text-dark fw-semibold fs-3">{{ $v->paid_at ? $v->paid_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-muted py-0 px-2" style="font-size: 0.7rem;">Dibuat</span>
                                            <span class="text-muted fs-2">{{ $v->created_at ? $v->created_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                        </div>
                                        @if($v->paid_at && $v->created_at)
                                            @php
                                                $diff = $v->paid_at->diffInSeconds($v->created_at);
                                                $duration = $diff > 60 ? round($diff / 60) . ' menit' : $diff . ' detik';
                                            @endphp
                                            <small class="text-primary-emphasis d-block mt-1" style="font-size: 0.75rem;">
                                                <i class="ti ti-clock me-1"></i> Proses bayar: {{ $duration }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success rounded-pill fw-semibold">{{ $v->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="ti ti-users-off fs-10 text-muted d-block mb-3"></i>
                                    <h6 class="fw-semibold text-muted">Belum Ada Voter</h6>
                                    <p class="text-muted mb-0">Belum ada pembayaran vote yang terverifikasi untuk kontingen ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($voters->hasPages())
            <div class="card-footer bg-light">
                {{ $voters->links() }}
            </div>
        @endif
    </div>
</div>
