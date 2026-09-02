<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Manajemen Tiket</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Tiket</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Message --}}
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Tiket --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-primary-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Total Tiket (Semua)</p>
                    <h3 class="fw-semibold text-primary mb-0">{{ number_format($totalTicketsCount) }}</h3>
                </div>
            </div>
        </div>
        {{-- Terjual (PAID) --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-success-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Tiket Terjual (PAID)</p>
                    <h3 class="fw-semibold text-success mb-0">{{ number_format($summaryPaid->trx_count ?? 0) }}</h3>
                </div>
            </div>
        </div>
        {{-- Pendapatan PAID + CHECKED_IN --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-info-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Pendapatan (PAID + Check-in)</p>
                    <h3 class="fw-semibold text-info mb-0">Rp {{ number_format(($summaryPaid->total_amount ?? 0) + ($checkedIn->total_amount ?? 0), 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        {{-- Pending / Expired --}}
        <div class="col-md-3">
            <div class="card mb-0 bg-warning-subtle border-0">
                <div class="card-body p-3 text-center">
                    <p class="text-muted small mb-1 fw-semibold">Pending / Expired</p>
                    <h3 class="fw-semibold text-warning mb-0">
                        {{ number_format($statusCounts['PENDING'] ?? 0) }}
                        <span class="fs-2 text-muted fw-normal">/</span>
                        <span class="text-danger">{{ number_format($statusCounts['EXPIRED'] ?? 0) }}</span>
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
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Kode order, nama, email, ID transaksi...">
                    </div>
                </div>
                {{-- Filter Status --}}
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-muted">Status</label>
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="PAID">PAID</option>
                        <option value="PENDING">PENDING</option>
                        <option value="CHECKED_IN">CHECKED IN</option>
                        <option value="EXPIRED">EXPIRED</option>
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

    {{-- Daftar Tiket --}}
    <div class="card w-100">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-semibold"><i class="ti ti-ticket me-2"></i>Daftar Tiket</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('eventner.tickets.csv', [
                    'search' => $search,
                    'filterStatus' => $filterStatus,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                ]) }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-file-export me-1"></i> Export CSV
                </a>
                <button class="btn btn-sm btn-warning" wire:click="syncPending" wire:loading.attr="disabled" wire:target="syncPending" onclick="return confirm('Sinkron semua tiket PENDING dengan AutoGoPay?') || event.stopImmediatePropagation()">
                    <i class="ti ti-refresh me-1" wire:loading.remove wire:target="syncPending"></i>
                    <span wire:loading.remove wire:target="syncPending">Sinkron Status PENDING</span>
                    <span wire:loading wire:target="syncPending"><span class="spinner-border spinner-border-sm me-1"></span> Mengecek...</span>
                </button>
                <button class="btn btn-sm btn-success" wire:click="openCheckIn">
                    <i class="ti ti-scan me-1"></i> Check-in
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            {{-- Check-In Panel --}}
            @if($showCheckIn)
                <div class="p-4 border-bottom">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-semibold mb-0"><i class="ti ti-scan me-1"></i> Scan / Input Kode Tiket</h6>
                                <button wire:click="closeCheckIn" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></button>
                            </div>
                            <div class="input-group mb-3">
                                <input type="text" wire:model="checkInCode" class="form-control text-center fw-bold" placeholder="Masukkan kode (contoh: TKT-XXXXXX)" style="text-transform:uppercase; letter-spacing:2px;">
                                <button wire:click="lookupTicket" class="btn btn-success px-4 fw-semibold">
                                    <i class="ti ti-search me-1"></i> Cari
                                </button>
                            </div>

                            @if($checkInResult)
                                @if(!$checkInResult['found'])
                                    <div class="alert alert-danger text-center mb-0">{{ $checkInResult['message'] }}</div>
                                @elseif(isset($checkInResult['ready']))
                                    <div class="card border-success">
                                        <div class="card-body text-center p-4">
                                            <i class="ti ti-circle-check text-success fs-10 d-block mb-2"></i>
                                            <h5 class="fw-semibold">{{ $checkInResult['ticket']->buyer_name }}</h5>
                                            <p class="text-muted small mb-2">{{ $checkInResult['ticket']->order_code }} &bull; {{ $checkInResult['ticket']->quantity }} tiket</p>
                                            <p class="fw-semibold text-primary mb-3">Rp {{ number_format($checkInResult['ticket']->total_amount, 0, ',', '.') }}</p>
                                            <button wire:click="confirmCheckIn({{ $checkInResult['ticket']->id }})" class="btn btn-success px-4 fw-semibold">
                                                <i class="ti ti-check me-1"></i> Konfirmasi Check-in & Berikan Gelang
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning text-center mb-0">
                                        <i class="ti ti-alert-triangle me-1"></i> {{ $checkInResult['message'] }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-bottom-0" width="50px"><h6 class="fw-semibold mb-0">#</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Detail Pembeli</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Kode Order</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Jumlah</h6></th>
                            <th class="border-bottom-0 text-end"><h6 class="fw-semibold mb-0">Total Bayar</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Informasi Transaksi</h6></th>
                            <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Waktu Transaksi</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Status</h6></th>
                            <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Aksi</h6></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $i => $t)
                            <tr wire:key="ticket-{{ $t->id }}">
                                <td class="text-muted">{{ $tickets->firstItem() + $i }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                                            <i class="ti ti-user fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-semibold mb-0">{{ $t->buyer_name }}</h6>
                                            <span class="text-muted small" style="font-size: 0.75rem;">{{ $t->buyer_email ?: '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="fw-semibold text-primary">{{ $t->order_code }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 fs-3 rounded-pill">
                                        {{ $t->quantity }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    Rp {{ number_format($t->total_amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    @if($t->autogopay_transaction_id)
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="text-primary fw-semibold fs-2 bg-light px-2 py-1 rounded" id="tx-{{ $t->id }}">{{ $t->autogopay_transaction_id }}</code>
                                            <button type="button" class="btn p-0 text-muted" onclick="navigator.clipboard.writeText('{{ $t->autogopay_transaction_id }}'); alert('ID Transaksi berhasil disalin!');" title="Salin ID Transaksi">
                                                <i class="ti ti-copy fs-4"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        @if($t->status === 'PAID' || $t->status === 'CHECKED_IN')
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="badge bg-success-subtle text-success py-0 px-2" style="font-size: 0.7rem;">Bayar</span>
                                                <span class="text-dark fw-semibold fs-3">{{ $t->paid_at ? $t->paid_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                            </div>
                                        @endif
                                        @if($t->status === 'CHECKED_IN')
                                            <div class="d-flex align-items-center gap-1 mt-1">
                                                <span class="badge bg-info-subtle text-info py-0 px-2" style="font-size: 0.7rem;">Check-in</span>
                                                <span class="text-muted fs-2">{{ $t->checked_in_at ? $t->checked_in_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                            </div>
                                        @endif
                                        <div class="d-flex align-items-center gap-1 mt-1">
                                            <span class="badge bg-light text-muted py-0 px-2" style="font-size: 0.7rem;">Dibuat</span>
                                            <span class="text-muted fs-2">{{ $t->created_at ? $t->created_at->translatedFormat('d M Y H:i:s') : '-' }} WIB</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($t->status === 'PAID')
                                        <span class="badge bg-success rounded-pill fw-semibold">{{ $t->status }}</span>
                                    @elseif($t->status === 'PENDING')
                                        <span class="badge bg-warning text-dark rounded-pill fw-semibold">{{ $t->status }}</span>
                                    @elseif($t->status === 'CHECKED_IN')
                                        <span class="badge bg-info rounded-pill fw-semibold">CHECKED IN</span>
                                    @elseif($t->status === 'EXPIRED')
                                        <span class="badge bg-light text-muted rounded-pill fw-semibold border">{{ $t->status }}</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill fw-semibold">{{ $t->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($t->status === 'PENDING')
                                        <button type="button"
                                            class="btn btn-success btn-sm rounded-pill px-3"
                                            wire:click="markAsPaid({{ $t->id }})"
                                            wire:confirm="Konfirmasi pembayaran tiket {{ $t->order_code }} menjadi PAID secara manual? Pastikan uang BENAR-BENAR sudah masuk."
                                            title="Konfirmasi Pembayaran Manual">
                                            <i class="ti ti-check me-1"></i> Konfirmasi Bayar
                                        </button>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="ti ti-ticket-off fs-10 text-muted d-block mb-3"></i>
                                    <h6 class="fw-semibold text-muted">Tidak Ada Tiket</h6>
                                    <p class="text-muted mb-0">Belum ada data tiket yang ditemukan atau cocok dengan kriteria filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tickets->hasPages())
            <div class="card-footer bg-light">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
