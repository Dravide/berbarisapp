<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Pendapatan Platform</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Pendapatan</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ --}}
    {{-- KARTU RINGKASAN --}}
    {{-- ============================ --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-semibold text-muted mb-0">Paket Event (SaaS)</h6>
                        <span class="rounded-circle p-2 bg-primary-subtle"><i class="ti ti-crown text-primary fs-5"></i></span>
                    </div>
                    <h4 class="fw-bold mb-0">Rp {{ number_format($saasRevenue, 0, ',', '.') }}</h4>
                    <small class="text-muted">{{ $paidEventners }} eventner paid</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-semibold text-muted mb-0">Tiket</h6>
                        <span class="rounded-circle p-2 bg-success-subtle"><i class="ti ti-ticket text-success fs-5"></i></span>
                    </div>
                    <h4 class="fw-bold mb-0">Rp {{ number_format($ticketRevenue, 0, ',', '.') }}</h4>
                    <small class="text-muted">Semua event</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-semibold text-muted mb-0">Vote</h6>
                        <span class="rounded-circle p-2 bg-info-subtle"><i class="ti ti-heart text-info fs-5"></i></span>
                    </div>
                    <h4 class="fw-bold mb-0">Rp {{ number_format($voteRevenue, 0, ',', '.') }}</h4>
                    <small class="text-muted">Semua event</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card bg-primary text-white border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-semibold mb-0 opacity-75">Total</h6>
                        <span class="rounded-circle p-2 bg-white bg-opacity-25"><i class="ti ti-wallet fs-5"></i></span>
                    </div>
                    <h4 class="fw-bold mb-0">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                    <small class="opacity-75">Akumulasi</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ --}}
    {{-- FUNNEL PAKET --}}
    {{-- ============================ --}}
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted fw-semibold mb-1">Free Aktif / Trial</h6>
                    <h3 class="fw-bold mb-0">{{ $freeActive }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted fw-semibold mb-1">Trial Berakhir</h6>
                    <h3 class="fw-bold text-warning mb-0">{{ $trialExpired }}</h3>
                    @if($trialExpired > 0)
                        <a href="{{ route('admin.eventner.index') }}" class="small text-decoration-none">Prospek upgrade →</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <h6 class="text-muted fw-semibold mb-1">Konversi ke Paid</h6>
                    <h3 class="fw-bold text-success mb-0">{{ $conversionRate }}%</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ --}}
    {{-- GRAFIK 12 BULAN --}}
    {{-- ============================ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-semibold mb-4">Pendapatan 12 Bulan Terakhir</h5>
            <div style="overflow-x: auto;">
                <table class="table table-sm align-middle mb-0" style="min-width: 640px;">
                    <thead>
                        <tr class="text-muted small">
                            <th>Bulan</th>
                            <th class="text-end">SaaS</th>
                            <th class="text-end">Tiket</th>
                            <th class="text-end">Vote</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyData as $m)
                            <tr>
                                <td class="fw-medium">{{ $m['month'] }}</td>
                                <td class="text-end">{{ $m['saas'] ? 'Rp ' . number_format($m['saas'], 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ $m['ticket'] ? 'Rp ' . number_format($m['ticket'], 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ $m['vote'] ? 'Rp ' . number_format($m['vote'], 0, ',', '.') : '-' }}</td>
                                <td class="text-end fw-semibold">{{ $m['total'] ? 'Rp ' . number_format($m['total'], 0, ',', '.') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================ --}}
    {{-- TOP EVENTNER --}}
    {{-- ============================ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="fw-semibold mb-4">Top Eventner (Tiket + Vote)</h5>
            @if(count($topEventners))
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>#</th>
                                <th>Event</th>
                                <th>Penyelenggara</th>
                                <th class="text-center">Plan</th>
                                <th class="text-end">Tiket</th>
                                <th class="text-end">Vote</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topEventners as $i => $ev)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-medium">{{ \Illuminate\Support\Str::limit($ev['nama_event'], 40) }}</td>
                                    <td class="text-muted">{{ $ev['owner_name'] }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $ev['plan'] === 'paid' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($ev['plan']) }}</span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($ev['ticket_total'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($ev['vote_total'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($ev['grand_total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Belum ada transaksi tiket/vote.</p>
            @endif
        </div>
    </div>
</div>
