<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Transaksi Voting</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Transaksi Voting</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Transaksi --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-primary-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Transaksi (Semua)</p>
                    <h3 class="fw-semibold text-primary mb-0">{{ number_format($totalTransactionsCount) }}</h3>
                </div>
            </div>
        </div>
        {{-- Total Transaksi Terverifikasi --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-success-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Transaksi PAID</p>
                    <h3 class="fw-semibold text-success mb-0">
                        {{ number_format($summaryPaid->trx_count ?? 0) }}
                        <span class="fs-2 text-muted fw-normal">({{ number_format($summaryPaid->total_votes ?? 0) }} Vote)</span>
                    </h3>
                </div>
            </div>
        </div>
        {{-- Total Pendapatan --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-info-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Pendapatan (PAID)</p>
                    <h3 class="fw-semibold text-info mb-0">Rp {{ number_format($summaryPaid->total_amount ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        {{-- Breakdown Pending / Expired --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-warning-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Pending / Expired</p>
                    <h3 class="fw-semibold text-warning mb-0">
                        {{ number_format($statusCounts['PENDING'] ?? 0) }}
                        <span class="fs-2 text-muted fw-normal">/</span>
                        <span class="text-danger">{{ number_format(($statusCounts['EXPIRED'] ?? 0) + ($statusCounts['FAILED'] ?? 0)) }}</span>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                {{-- Search Input --}}
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Nama, email, ID transaksi...">
                    </div>
                </div>
                {{-- Filter Status --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Status</label>
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="PAID">PAID</option>
                        <option value="PENDING">PENDING</option>
                        <option value="EXPIRED">EXPIRED</option>
                        <option value="FAILED">FAILED</option>
                    </select>
                </div>
                {{-- Filter Kontingen --}}
                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-muted">Kontingen / Sekolah</label>
                    <select class="form-select" wire:model.live="filterRegistration">
                        <option value="">Semua Kontingen</option>
                        @foreach($registrations as $reg)
                            <option value="{{ $reg->id }}">{{ $reg->nama_sekolah }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Filter Rentang Tanggal --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Rentang Tanggal (Dibuat)</label>
                    <div class="input-group">
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                        <span class="input-group-text bg-light">-</span>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
                {{-- Reset Button --}}
                <div class="col-md-1 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" wire:click="resetFilters" title="Reset Filter">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Transaksi --}}
    <div class="card w-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold"><i class="ti ti-receipt me-2"></i>Daftar Transaksi</h5>
            <a href="{{ route('eventner.vote-transactions.csv', [
                'search' => $search,
                'filterStatus' => $filterStatus,
                'filterRegistration' => $filterRegistration,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-file-export me-1"></i> Export CSV
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-bottom-0" width="50px"><h6 class="fw-semibold mb-0">#</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Detail Voter</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Kontingen / Sekolah</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Jumlah Vote</h6></th>
                            <th class="border-bottom-0 text-end"><h6 class="fw-semibold mb-0">Nominal Bayar</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Informasi Transaksi</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Waktu Transaksi</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Status</h6></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $i => $v)
                            <tr wire:key="trx-{{ $v->id }}">
                                <td class="text-muted">{{ $transactions->firstItem() + $i }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                                            <i class="ti ti-user fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-semibold mb-0">{{ $v->voter_name ?: 'Guest / Anonim' }}</h6>
                                            <span class="text-muted small" style="font-size: 0.75rem;">{{ $v->voter_email ?: '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($v->registration)
                                        <div>
                                            <h6 class="fw-semibold mb-0">{{ $v->registration->nama_sekolah }}</h6>
                                            <span class="badge bg-primary-subtle text-primary py-0 px-2 mt-1" style="font-size: 0.7rem;">
                                                {{ $v->registration->competitionCategory->name ?? '-' }}
                                            </span>
                                            <span class="text-muted fs-2 ms-1">NPSN: {{ $v->registration->npsn }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success text-white fw-bold px-3 py-1 fs-3 rounded-pill">
                                        {{ number_format($v->votes_earned) }} Vote
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($v->amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($v->autogopay_transaction_id)
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="text-primary fw-semibold fs-2 bg-light px-2 py-1 rounded" id="tx-{{ $v->id }}">{{ $v->autogopay_transaction_id }}</code>
                                            <button type="button" class="btn p-0 text-muted" onclick="navigator.clipboard.writeText('{{ $v->autogopay_transaction_id }}'); alert('ID Transaksi berhasil disalin!');" title="Salin ID Transaksi">
                                                <i class="ti ti-copy fs-4"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        @if($v->status === 'PAID')
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="badge bg-success-subtle text-success py-0 px-2" style="font-size: 0.7rem;">Bayar</span>
                                                <span class="text-dark fw-semibold fs-3">{{ $v->paid_at ? $v->paid_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                            </div>
                                        @endif
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-muted py-0 px-2" style="font-size: 0.7rem;">Dibuat</span>
                                            <span class="text-muted fs-2">{{ $v->created_at ? $v->created_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                        </div>
                                        @if($v->paid_at && $v->created_at && $v->status === 'PAID')
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
                                    @if($v->status === 'PAID')
                                        <span class="badge bg-success rounded-pill fw-semibold">{{ $v->status }}</span>
                                    @elseif($v->status === 'PENDING')
                                        <span class="badge bg-warning text-dark rounded-pill fw-semibold">{{ $v->status }}</span>
                                    @elseif($v->status === 'EXPIRED')
                                        <span class="badge bg-light text-muted rounded-pill fw-semibold border">{{ $v->status }}</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill fw-semibold">{{ $v->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="ti ti-receipt-off fs-10 text-muted d-block mb-3"></i>
                                    <h6 class="fw-semibold text-muted">Tidak Ada Transaksi</h6>
                                    <p class="text-muted mb-0">Belum ada data transaksi voting yang ditemukan atau cocok dengan kriteria filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer bg-light">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
