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
                            <li class="breadcrumb-item active" aria-current="page">{{ $registration->nama_sekolah }}</li>
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
                    <h4 class="fw-semibold mb-1">{{ $registration->nama_sekolah }}</h4>
                    <p class="text-muted mb-0">
                        <span class="badge bg-primary-subtle text-primary me-2">{{ $registration->competitionCategory->name ?? '-' }}</span>
                        NPSN: {{ $registration->npsn }}
                    </p>
                </div>
                <div class="ms-auto">
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
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">#</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Voter</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Email</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Vote</h6></th>
                            <th class="border-bottom-0 text-end"><h6 class="fw-semibold mb-0">Nominal</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">ID Transaksi</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Waktu Bayar</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Status</h6></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($voters as $i => $v)
                            <tr wire:key="voter-{{ $v->id }}">
                                <td class="text-muted">{{ $voters->firstItem() + $i }}</td>
                                <td class="fw-semibold">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                            <i class="ti ti-user"></i>
                                        </div>
                                        {{ $v->voter_name }}
                                    </div>
                                </td>
                                <td><span class="text-muted">{{ $v->voter_email }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success fw-semibold">{{ number_format($v->votes_earned) }}</span>
                                </td>
                                <td class="text-end fw-semibold">Rp {{ number_format($v->amount, 0, ',', '.') }}</td>
                                <td>
                                    <code class="text-muted fs-3">{{ Str::limit($v->autogopay_transaction_id, 18, '…') }}</code>
                                </td>
                                <td>
                                    <span class="text-muted fs-3">{{ optional($v->paid_at)->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">{{ $v->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
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
