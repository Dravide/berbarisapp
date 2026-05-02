<div>
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Manajemen Tiket</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item">{{ $eventner->nama_event }}</li>
                            <li class="breadcrumb-item active">Tiket</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card w-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;"><i class="ti ti-ticket fs-5"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Total Terjual</p>
                            <h4 class="fw-bold mb-0">{{ $stats['paid'] + $stats['checked_in'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card w-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;"><i class="ti ti-currency-dollar fs-5"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Pendapatan</p>
                            <h4 class="fw-bold mb-0">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card w-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;"><i class="ti ti-user-check fs-5"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Sudah Check-in</p>
                            <h4 class="fw-bold mb-0">{{ $stats['checked_in'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card w-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px;"><i class="ti ti-clock fs-5"></i></div>
                        <div>
                            <p class="text-muted small mb-0">Menunggu Bayar</p>
                            <h4 class="fw-bold mb-0">{{ $stats['pending'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card w-100 mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex gap-2 align-items-center">
                    <input type="text" wire:model.live="search" class="form-control form-control-sm" placeholder="Cari kode/nama/email..." style="width:250px;">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm" style="width:150px;">
                        <option value="">Semua Status</option>
                        <option value="PENDING">Pending</option>
                        <option value="PAID">Paid</option>
                        <option value="CHECKED_IN">Checked In</option>
                        <option value="EXPIRED">Expired</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button wire:click="openCheckIn" class="btn btn-sm btn-success px-3 fw-semibold">
                        <i class="ti ti-scan me-1"></i> Check-in
                    </button>
                    <a href="{{ route('eventner.tickets.settings') }}" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="ti ti-settings me-1"></i> Pengaturan
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Check-In Modal --}}
    @if($showCheckIn)
        <div class="card w-100 mb-4 border-success">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-scan me-2"></i>Scan / Input Kode Tiket</h5>
                    <button wire:click="closeCheckIn" class="btn btn-sm btn-outline-light"><i class="ti ti-x"></i></button>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="input-group mb-3">
                            <input type="text" wire:model="checkInCode" class="form-control form-control-lg text-center fw-bold" placeholder="Masukkan kode (contoh: TKT-XXXXXX)" style="text-transform:uppercase; letter-spacing:2px;">
                            <button wire:click="lookupTicket" class="btn btn-success px-4 fw-semibold">
                                <i class="ti ti-search me-1"></i> Cari
                            </button>
                        </div>

                        @if($checkInResult)
                            @if(!$checkInResult['found'])
                                <div class="alert alert-danger text-center">{{ $checkInResult['message'] }}</div>
                            @elseif(isset($checkInResult['ready']))
                                <div class="card border-success">
                                    <div class="card-body text-center p-4">
                                        <i class="ti ti-circle-check text-success fs-10 d-block mb-2"></i>
                                        <h5 class="fw-semibold">{{ $checkInResult['ticket']->buyer_name }}</h5>
                                        <p class="text-muted small mb-2">{{ $checkInResult['ticket']->order_code }} &bull; {{ $checkInResult['ticket']->quantity }} tiket</p>
                                        <p class="fw-semibold text-primary mb-3">Rp {{ number_format($checkInResult['ticket']->total_amount, 0, ',', '.') }}</p>
                                        <button wire:click="confirmCheckIn({{ $checkInResult['ticket']->id }})" class="btn btn-success px-4 py-8 fw-semibold">
                                            <i class="ti ti-check me-1"></i> Konfirmasi Check-in & Berikan Gelang
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning text-center">
                                    <i class="ti ti-alert-triangle me-1"></i> {{ $checkInResult['message'] }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tickets Table --}}
    <div class="card w-100">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-ticket me-2"></i>Daftar Tiket ({{ $tickets->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th>Kode Order</th>
                            <th>Pembeli</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                            <tr>
                                <td><span class="fw-semibold text-primary">{{ $ticket->order_code }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $ticket->buyer_name }}</div>
                                    <div class="text-muted small">{{ $ticket->buyer_email }}</div>
                                </td>
                                <td class="text-center">{{ $ticket->quantity }}</td>
                                <td class="fw-semibold">Rp {{ number_format($ticket->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    @if($ticket->status === 'PENDING')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($ticket->status === 'PAID')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($ticket->status === 'CHECKED_IN')
                                        <span class="badge bg-info">Checked In</span>
                                    @elseif($ticket->status === 'EXPIRED')
                                        <span class="badge bg-secondary">Expired</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $ticket->created_at->translatedFormat('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                        @if($tickets->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="ti ti-ticket-off fs-10 d-block mb-2"></i>
                                    Belum ada tiket terjual
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
