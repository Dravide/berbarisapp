<div>
    <div class="row">
        <div class="col-12">
            {{-- Pending Approval Banner --}}
            @if($eventner->status === 'pending')
                <div class="alert alert-warning border-0 rounded-3 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
                    <i class="ti ti-hourglass-high fs-4 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong>Akun Anda masih menunggu persetujuan.</strong>
                        <span class="small">
                            @if($eventner->plan === 'paid' && $eventner->qr_url)
                                Lakukan pembayaran untuk mengaktifkan akun secara otomatis.
                            @else
                                Admin akan memverifikasi akun Anda. Anda akan mendapat notifikasi melalui email setelah disetujui.
                            @endif
                        </span>
                    </div>
                    @if($eventner->plan === 'paid' && $eventner->qr_url)
                        <a href="{{ request()->url() }}" class="btn btn-primary btn-sm rounded-2 flex-shrink-0">Bayar Sekarang</a>
                    @endif
                </div>
            @endif

            {{-- Trial Banner --}}
            @if($eventner->plan === 'free')
                @if($isTrialExpired)
                    <div class="alert alert-warning border-0 rounded-3 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
                        <i class="ti ti-clock-off fs-4 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>Masa trial Anda telah berakhir.</strong>
                            <span class="small">Beberapa fitur premium tidak dapat diakses. Upgrade ke paket berbayar untuk mengaktifkan semua fitur.</span>
                        </div>
                        <a href="{{ route('eventner.billing.upgrade') }}" class="btn btn-warning fw-semibold flex-shrink-0">
                            <i class="ti ti-bolt me-1"></i> Upgrade Sekarang
                        </a>
                    </div>
                @elseif($trialDaysLeft > 0)
                    <div class="alert alert-info border-0 rounded-3 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
                        <i class="ti ti-clock-hour-4 fs-4 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>Masa trial tersisa {{ $trialDaysLeft }} hari.</strong>
                            <span class="small">Nikmati akses penuh ke semua fitur premium selama masa trial. Upgrade ke paket berbayar sebelum trial berakhir.</span>
                        </div>
                        <a href="{{ route('eventner.billing.upgrade') }}" class="btn btn-info fw-semibold flex-shrink-0">
                            <i class="ti ti-bolt me-1"></i> Upgrade
                        </a>
                    </div>
                @endif
            @endif

            {{-- Locked Features Panel --}}
            @if($eventner->plan === 'free' && $isTrialExpired && !empty($lockedFeatures))
                <div class="card border-warning-subtle bg-warning-subtle shadow-none mb-4">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-lock text-warning-emphasis"></i>
                                <h6 class="fw-semibold mb-0 text-warning-emphasis">Fitur Terkunci</h6>
                            </div>
                            <a href="{{ route('eventner.billing.upgrade') }}" class="btn btn-sm btn-warning fw-semibold">
                                <i class="ti ti-bolt me-1"></i> Buka Semua Fitur
                            </a>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($lockedFeatures as $key => $label)
                                <span class="badge bg-light text-muted border rounded-1 px-3 py-2">
                                    <i class="ti ti-lock me-1"></i> {{ $label }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Header & Breadcrumb -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Selamat Datang, Panitia {{ $eventner->nama_event }}</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Statistik Event</li>
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

            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-users fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Pendaftar</h6>
                                    <h4 class="mb-0 fw-bold">{{ $totalRegistrations }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-wallet fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Pendapatan</h6>
                                    <h4 class="mb-0 fw-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-category fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Kategori Lomba</h6>
                                    <h4 class="mb-0 fw-bold">{{ $totalCategories }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-secondary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-user-check fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Juri Terdaftar</h6>
                                    <h4 class="mb-0 fw-bold">{{ $totalJudges }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stat Card Tambahan --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-heart-filled fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Vote Masuk</h6>
                                    <h4 class="mb-0 fw-bold">{{ number_format($totalVotes, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success-subtle shadow-none">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="ti ti-ticket fs-6"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Tiket Terjual</h6>
                                    <h4 class="mb-0 fw-bold">{{ number_format($ticketsSold, 0, ',', '.') }}</h4>
                                    <small class="text-muted">{{ $ticketsCheckedIn }} check-in</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('eventner.finance.index') }}" class="text-decoration-none">
                        <div class="card bg-warning-subtle shadow-none h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="ti ti-credit-card fs-6"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 text-muted">Perlu Verifikasi</h6>
                                        <h4 class="mb-0 fw-bold {{ $pendingVerificationCount > 0 ? 'text-warning' : '' }}">{{ $pendingVerificationCount }}</h4>
                                        <small class="text-muted">pembayaran</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('eventner.participants.index') }}" class="text-decoration-none">
                        <div class="card bg-info-subtle shadow-none h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="ti ti-file-check fs-6"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 text-muted">Berkas Menunggu</h6>
                                        <h4 class="mb-0 fw-bold {{ $berkasMenungguCount > 0 ? 'text-info' : '' }}">{{ $berkasMenungguCount }}</h4>
                                        <small class="text-muted">pendaftar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Charts Row --}}
            <div class="row mb-4">
                {{-- Scoring Progress --}}
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Progress Scoring per Kategori</h5>
                        </div>
                        <div class="card-body">
                            @forelse($scoringProgress as $progress)
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-semibold fs-3">{{ $progress['name'] }}</span>
                                        <span class="text-muted fs-3">{{ $progress['scored'] }}/{{ $progress['total'] }}</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        @php $color = $progress['percentage'] >= 100 ? 'success' : ($progress['percentage'] >= 50 ? 'primary' : 'warning'); @endphp
                                        <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $progress['percentage'] }}%" aria-valuenow="{{ $progress['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-end"><small class="text-muted">{{ $progress['percentage'] }}%</small></div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="ti ti-chart-donut-3 fs-8"></i>
                                    <p class="mb-0 mt-2">Belum ada data penilaian.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Revenue Chart --}}
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-semibold mb-0">Revenue 30 Hari Terakhir</h5>
                            <span class="badge bg-success-subtle text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Top Participants --}}
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title fw-semibold mb-0">Top 10 Peserta</h5>
                                @if($categories->count() > 1)
                                    <select class="form-select form-select-sm w-auto" wire:model.live="selectedChartCategory">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->full_name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="topParticipantsChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Voting --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="card-title fw-semibold mb-0">
                                <i class="ti ti-heart-filled text-danger me-2"></i>Voting
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                @if($voteStatus === 'berjalan' && $voteTimeRemaining)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="ti ti-clock me-1"></i>Berjalan &middot; sisa {{ $voteTimeRemaining }}
                                    </span>
                                @elseif($voteStatus === 'belum')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">Belum Dimulai</span>
                                @elseif($voteStatus === 'selesai')
                                    <span class="badge bg-dark-subtle text-dark border">Selesai</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">Nonaktif</span>
                                @endif
                                <a href="{{ route('eventner.vote-results.index') }}" class="btn btn-sm btn-light">Hasil Voting <i class="ti ti-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded border">
                                        <i class="ti ti-heart-check text-success fs-4"></i>
                                        <div>
                                            <small class="text-muted d-block">Transaksi PAID</small>
                                            <span class="fw-bold">{{ $votePaidCount }}</span>
                                            <small class="text-muted">&middot; {{ number_format($totalVotes, 0, ',', '.') }} vote</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-2 mt-md-0">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded border">
                                        <i class="ti ti-hourglass text-warning fs-4"></i>
                                        <div>
                                            <small class="text-muted d-block">Transaksi PENDING</small>
                                            <span class="fw-bold {{ $votePendingCount > 0 ? 'text-warning' : '' }}">{{ $votePendingCount }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mt-2 mt-md-0">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded border">
                                        <i class="ti ti-rocket text-danger fs-4"></i>
                                        <div>
                                            <small class="text-muted d-block">Vote Booster Aktif</small>
                                            @if($activeBooster)
                                                <span class="badge bg-danger">x{{ $activeBooster->vote_multiplier }}</span>
                                                <small class="text-muted">s/d {{ $activeBooster->ends_at?->translatedFormat('d M H:i') }}</small>
                                            @else
                                                <span class="fw-bold text-muted">&mdash;</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Voter</th>
                                            <th>Kontingen</th>
                                            <th class="text-center">Jumlah Vote</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end pe-4">Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentVoteTransactions as $trx)
                                            <tr>
                                                <td class="ps-4">
                                                    <h6 class="mb-0 fw-semibold">{{ $trx->voter_name }}</h6>
                                                    <span class="text-muted fs-2">{{ $trx->voter_email }}</span>
                                                </td>
                                                <td>{{ $trx->registration?->nama_sekolah ?? '-' }}</td>
                                                <td class="text-center fw-semibold text-primary">{{ number_format($trx->votes_earned, 0, ',', '.') }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-success-subtle text-success">PAID</span>
                                                </td>
                                                <td class="text-end pe-4 fs-2 text-muted">{{ $trx->paid_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center p-4 text-muted">
                                                    <i class="ti ti-heart-off fs-8 d-block mb-2"></i>Belum ada transaksi vote.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top text-center">
                            <a href="{{ route('eventner.finance.index') }}" class="btn btn-sm btn-light">Lihat Semua Transaksi <i class="ti ti-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Pembayaran & Berkas --}}
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-credit-card me-2"></i>Status Pembayaran Pendaftar</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $paymentTotal = array_sum($paymentBreakdown);
                            @endphp
                            @if($paymentTotal > 0)
                                <div style="height: 220px;"><canvas id="paymentStatusChart"></canvas></div>
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="ti ti-credit-card-off fs-8"></i>
                                    <p class="mb-0 mt-2">Belum ada pendaftaran.</p>
                                </div>
                            @endif

                            @if($pendingVerificationCount > 0)
                                <hr>
                                <h6 class="fw-semibold mb-2">Menunggu Verifikasi ({{ $pendingVerificationCount }})</h6>
                                <ul class="list-group list-group-flush">
                                    @foreach($pendingVerifications as $pv)
                                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-semibold">{{ $pv->nama_sekolah }}</span>
                                                <small class="text-muted d-block">{{ $pv->competitionCategory?->full_name }}</small>
                                            </div>
                                            <span class="badge bg-warning-subtle text-warning">Rp {{ number_format($pv->total_fee, 0, ',', '.') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('eventner.finance.index') }}" class="btn btn-sm btn-warning fw-semibold w-100 mt-2">
                                    <i class="ti ti-checkbox me-1"></i>Verifikasi Sekarang
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-file-check me-2"></i>Status Berkas Pendaftar</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $berkasTotal = array_sum($berkasBreakdown);
                            @endphp
                            @if($berkasTotal > 0)
                                <div style="height: 220px;"><canvas id="berkasStatusChart"></canvas></div>
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="ti ti-file-off fs-8"></i>
                                    <p class="mb-0 mt-2">Belum ada pendaftaran.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section Tiket & Jadwal --}}
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-ticket me-2"></i>Tiket &amp; Check-in</h5>
                        </div>
                        <div class="card-body">
                            @if($eventner->ticket_active)
                                @php
                                    $ticketTotal = array_sum(array_column($ticketStatusBreakdown, 'total'));
                                @endphp
                                @if($ticketTotal > 0)
                                    <div style="height: 200px;"><canvas id="ticketStatusChart"></canvas></div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="ti ti-ticket-off fs-8"></i>
                                        <p class="mb-0 mt-2">Belum ada transaksi tiket.</p>
                                    </div>
                                @endif
                                <div class="row text-center mt-3">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Terjual</small>
                                        <span class="fw-bold fs-5">{{ number_format($ticketsSold, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Check-in</small>
                                        <span class="fw-bold fs-5">{{ $ticketsCheckedIn }}</span>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Pendapatan</small>
                                        <span class="fw-bold fs-5 text-success">Rp {{ number_format($ticketRevenue, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-5">
                                    <i class="ti ti-ticket-off fs-8"></i>
                                    <p class="mb-0 mt-2">Tiket tidak diaktifkan untuk event ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0"><i class="ti ti-calendar-event me-2"></i>Jadwal Event</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">Pendaftaran</span>
                                        <small class="text-muted d-block">s/d {{ $eventner->tanggal_pendaftaran ?? '-' }}</small>
                                    </div>
                                    <i class="ti ti-calendar-minus text-muted"></i>
                                </li>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">Hari-H</span>
                                        <small class="text-muted d-block">
                                            @if($eventner->tanggal)
                                                {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M Y') }}
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                    @if($daysUntilEvent !== null)
                                        @if($daysUntilEvent > 1)
                                            <span class="badge bg-primary-subtle text-primary">{{ $daysUntilEvent }} hari lagi</span>
                                        @elseif($daysUntilEvent === 1)
                                            <span class="badge bg-warning-subtle text-warning">Besok!</span>
                                        @elseif($daysUntilEvent === 0)
                                            <span class="badge bg-danger">Hari ini!</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Sudah lewat</span>
                                        @endif
                                    @endif
                                </li>
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">Voting</span>
                                        <small class="text-muted d-block">
                                            @if($eventner->vote_start || $eventner->vote_end)
                                                {{ $eventner->vote_start?->translatedFormat('d M') }} &mdash; {{ $eventner->vote_end?->translatedFormat('d M Y') }}
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                    @if($voteStatus === 'berjalan')
                                        <span class="badge bg-success">Berjalan</span>
                                    @elseif($voteStatus === 'belum')
                                        <span class="badge bg-info-subtle text-info">Belum</span>
                                    @elseif($voteStatus === 'selesai')
                                        <span class="badge bg-secondary">Selesai</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Nonaktif</span>
                                    @endif
                                </li>
                                @if($eventner->ticket_active)
                                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center border-bottom-0">
                                        <div>
                                            <span class="fw-semibold">Tiket</span>
                                            <small class="text-muted d-block">
                                                {{ $eventner->ticket_start?->translatedFormat('d M') }} &mdash; {{ $eventner->ticket_end?->translatedFormat('d M Y') }}
                                            </small>
                                        </div>
                                        <i class="ti ti-ticket text-muted"></i>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Event Info -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Informasi Event Anda</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center mb-4 mb-md-0">
                                    @if($eventner->logo_event)
                                        <img src="{{ Storage::url($eventner->logo_event) }}" class="img-fluid rounded border p-2" style="max-height: 150px;">
                                    @else
                                        <div class="bg-light rounded p-5 text-center">
                                            <i class="ti ti-photo fs-9 text-muted"></i>
                                            <p class="mb-0 text-muted mt-2">No Logo</p>
                                        </div>
                                    @endif
                                    <div class="mt-3">
                                        <a href="{{ $eventner->publicUrl('detail') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-external-link"></i> Link Pendaftaran Publik
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Tingkat Perlombaan</span>
                                            <span class="badge bg-primary rounded-pill">{{ $eventner->tingkat_perlombaan }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Tanggal Pelaksanaan</span>
                                            <span class="fw-bold">
                                                @if($eventner->tanggal_akhir)
                                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->format('d M') }} - {{ \Carbon\Carbon::parse($eventner->tanggal_akhir)->format('d F Y') }}
                                                @else
                                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->format('d F Y') }}
                                                @endif
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Batas Pendaftaran</span>
                                            <span class="fw-bold text-danger">{{ $eventner->tanggal_pendaftaran }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <span class="text-muted">Venue / Lokasi</span>
                                            <span class="fw-bold text-end">{{ $eventner->venue }}, {{ $eventner->lokasi }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0">
                                            <span class="text-muted">Technical Meeting</span>
                                            <span class="fw-bold">{{ $eventner->technical_meeting }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Status Kuota Lomba</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Nama Kategori</th>
                                            <th>Pelaksanaan</th>
                                            <th class="text-center">Kuota (Terisi)</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categories as $category)
                                            @php
                                                $regCount = $category->registrations_count;
                                                $kuota = $category->kuota ?: 0;
                                                $percent = $kuota > 0 ? ($regCount / $kuota) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-dark">{{ $category->full_name }}</span>
                                                </td>
                                                <td>{{ $category->tanggal_pelaksanaan ?: '-' }}</td>
                                                <td>
                                                    <div class="text-center mb-1">
                                                        <small>{{ $regCount }} / {{ $kuota }}</small>
                                                    </div>
                                                    <div class="progress" style="height: 4px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percent }}%"></div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($kuota > 0 && $regCount >= $kuota)
                                                        <span class="badge bg-danger">Full</span>
                                                    @else
                                                        <span class="badge bg-success">Open</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top text-center">
                            <a href="{{ route('eventner.competition-categories.index') }}" class="btn btn-sm btn-light">Kelola Kategori <i class="ti ti-arrow-right ms-1"></i></a>
                        </div>
                    </div>

                    <!-- Recent Registrations -->
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-semibold mb-0">Pendaftar Terbaru</h5>
                            <span class="badge bg-info-subtle text-info">10 Terakhir</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Sekolah</th>
                                            <th>Kategori</th>
                                            <th>Tanggal</th>
                                            <th class="text-center">Berkas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentRegistrations as $registration)
                                            <tr>
                                                <td class="ps-4">
                                                    <h6 class="mb-0 fw-semibold">{{ $registration->nama_sekolah }}</h6>
                                                    <span class="text-muted fs-2">{{ $registration->npsn }}</span>
                                                </td>
                                                <td>{{ $registration->competitionCategory->full_name }}</td>
                                                <td class="fs-2 text-muted">{{ $registration->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="text-center">
                                                    @if(in_array($registration->status_berkas, ['Terverifikasi', 'confirmed']))
                                                        <span class="badge bg-success-subtle text-success">Verified</span>
                                                    @elseif($registration->status_berkas == 'Ditolak')
                                                        <span class="badge bg-danger-subtle text-danger">Rejected</span>
                                                    @elseif($registration->status_berkas == 'Menunggu')
                                                        <span class="badge bg-warning-subtle text-warning">Menunggu</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">Booking</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center p-4">Belum ada pendaftaran</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top text-center">
                            <a href="{{ route('eventner.participants.index') }}" class="btn btn-sm btn-light">Lihat Semua Peserta <i class="ti ti-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Map Section -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Lokasi Venue</h5>
                        </div>
                        <div class="card-body">
                            @if($eventner->latitude && $eventner->longitude)
                                <div class="rounded overflow-hidden mb-3 border">
                                    <iframe
                                        width="100%"
                                        height="250"
                                        frameborder="0"
                                        scrolling="no"
                                        marginheight="0"
                                        marginwidth="0"
                                        src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
                                    </iframe>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $eventner->latitude }},{{ $eventner->longitude }}" target="_blank" class="btn btn-primary w-100">
                                    <i class="ti ti-navigation me-2"></i> Buka Google Maps
                                </a>
                            @else
                                <div class="bg-light rounded p-4 text-center border">
                                    <i class="ti ti-map-off fs-8 text-muted"></i>
                                    <p class="mb-0 text-muted mt-2">Koordinat lokasi belum diset.</p>
                                    <a href="{{ route('eventner.profile.index') }}" class="btn btn-sm btn-link">Setel Lokasi</a>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card">
                        <div class="card-header bg-white text-primary fw-bold">Pintasan Panitia</div>
                        <div class="list-group list-group-flush">
                            <a href="{{ route('eventner.judges.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="ti ti-gavel fs-5 me-2"></i> Kelola Juri
                            </a>
                            <a href="{{ route('eventner.format-nilai.builder') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="ti ti-file-text fs-5 me-2"></i> Builder Format Nilai
                            </a>
                            <a href="{{ route('eventner.vote-results.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="ti ti-chart-bar fs-5 me-2"></i> Hasil Voting
                            </a>
                            <a href="{{ event_url($eventner, 'drawing.spin') }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center bg-primary-subtle">
                                <i class="ti ti-arrows-shuffle fs-5 me-2 text-primary"></i> <span class="fw-bold">Layar Pengundian (Spin)</span>
                            </a>
                            <a href="{{ event_url($eventner, 'drawing.results') }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center bg-primary-subtle">
                                <i class="ti ti-list-numbers fs-5 me-2 text-primary"></i> <span class="fw-bold">Lihat Hasil Undian</span>
                            </a>
                        </div>
                    </div>

                    {{-- Drawing Results Summary --}}
                    <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-semibold mb-0">
                            <i class="ti ti-arrows-shuffle me-2"></i> Hasil Pengundian
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('eventner.drawing.index') }}" class="btn btn-sm btn-light">Kelola <i class="ti ti-arrow-right ms-1"></i></a>
                            <a href="{{ event_url($eventner, 'drawing.spin') }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="ti ti-arrows-shuffle me-1"></i> Layar Spin
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            @php
                                $drawingData = $this->drawingData;
                            @endphp
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-bottom-0 ps-4">
                                            <h6 class="fw-semibold mb-0">Kategori</h6>
                                        </th>
                                        <th class="border-bottom-0 text-center" width="150px">
                                            <h6 class="fw-semibold mb-0">Progress</h6>
                                        </th>
                                        <th class="border-bottom-0 text-center" width="80px">
                                            <h6 class="fw-semibold mb-0">Status</h6>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($drawingData as $dd)
                                        @php
                                            $drawPercent = $dd['total'] > 0 ? ($dd['drawn'] / $dd['total']) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td class="ps-4">
                                                <span class="fw-semibold">{{ $dd['name'] }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" style="width: {{ $drawPercent }}%"></div>
                                                    </div>
                                                    <small class="text-muted text-nowrap">{{ $dd['drawn'] }}/{{ $dd['total'] }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if($drawPercent >= 100)
                                                    <span class="badge bg-success-subtle text-success">Selesai</span>
                                                @elseif($dd['drawn'] > 0)
                                                    <span class="badge bg-warning-subtle text-warning">{{ round($drawPercent) }}%</span>
                                                @else
                                                    <span class="badge bg-light text-dark border">Belum</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center p-4 text-muted">Tidak ada kategori lomba.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const revenueData = @json($revenueData);
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueData.map(d => d.date),
                datasets: [{
                    label: 'Voting',
                    data: revenueData.map(d => d.vote),
                    backgroundColor: 'rgba(94, 126, 210, 0.7)',
                    borderRadius: 4,
                }, {
                    label: 'Tiket',
                    data: revenueData.map(d => d.ticket),
                    backgroundColor: 'rgba(41, 182, 115, 0.7)',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value/1000000).toFixed(1) + 'jt';
                                if (value >= 1000) return 'Rp ' + (value/1000).toFixed(0) + 'rb';
                                return 'Rp ' + value;
                            }
                        }
                    },
                    x: { ticks: { maxRotation: 45, maxTicksLimit: 15, font: { size: 10 } } }
                }
            }
        });
    }

    // Top Participants Chart
    const topCtx = document.getElementById('topParticipantsChart');
    if (topCtx) {
        const topData = @json($topParticipants);
        const colors = [
            'rgba(255, 193, 7, 0.8)',   // gold
            'rgba(173, 181, 189, 0.8)',  // silver
            'rgba(205, 127, 50, 0.8)',   // bronze
            'rgba(94, 126, 210, 0.6)',
            'rgba(41, 182, 115, 0.6)',
            'rgba(252, 143, 0, 0.6)',
            'rgba(239, 83, 80, 0.6)',
            'rgba(103, 58, 183, 0.6)',
            'rgba(0, 188, 212, 0.6)',
            'rgba(121, 134, 203, 0.6)',
        ];
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topData.map(d => d.name.length > 15 ? d.name.substring(0,15)+'...' : d.name),
                datasets: [{
                    label: 'Total Skor',
                    data: topData.map(d => d.total),
                    backgroundColor: colors.slice(0, topData.length),
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true },
                    y: { ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    // Payment Status Doughnut
    const paymentCtx = document.getElementById('paymentStatusChart');
    if (paymentCtx) {
        const paymentData = @json($paymentBreakdown);
        const paymentLabels = {paid: 'Paid', pending_verification: 'Pending Verifikasi', unpaid: 'Unpaid', free: 'Free', expired: 'Expired'};
        const paymentColors = {paid: 'rgba(41, 182, 115, 0.8)', pending_verification: 'rgba(252, 143, 0, 0.8)', unpaid: 'rgba(108, 117, 125, 0.8)', free: 'rgba(94, 126, 210, 0.8)', expired: 'rgba(239, 83, 80, 0.8)'};
        const paymentEntries = Object.entries(paymentData).filter(([s, t]) => t > 0);
        if (paymentEntries.length > 0) {
            new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: paymentEntries.map(([s]) => paymentLabels[s] ?? s),
                    datasets: [{
                        data: paymentEntries.map(([, t]) => t),
                        backgroundColor: paymentEntries.map(([s]) => paymentColors[s] ?? 'rgba(108, 117, 125, 0.8)'),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } }
                }
            });
        }
    }

    // Berkas Status Doughnut
    const berkasCtx = document.getElementById('berkasStatusChart');
    if (berkasCtx) {
        const berkasData = @json($berkasBreakdown);
        const berkasLabels = {Terverifikasi: 'Terverifikasi', confirmed: 'Confirmed', Menunggu: 'Menunggu', booking: 'Booking', Ditolak: 'Ditolak', dibatalkan: 'Dibatalkan'};
        const berkasColors = {Terverifikasi: 'rgba(41, 182, 115, 0.8)', confirmed: 'rgba(72, 187, 120, 0.8)', Menunggu: 'rgba(252, 143, 0, 0.8)', booking: 'rgba(94, 126, 210, 0.8)', Ditolak: 'rgba(239, 83, 80, 0.8)', dibatalkan: 'rgba(108, 117, 125, 0.8)'};
        const berkasEntries = Object.entries(berkasData).filter(([s, t]) => t > 0);
        if (berkasEntries.length > 0) {
            new Chart(berkasCtx, {
                type: 'doughnut',
                data: {
                    labels: berkasEntries.map(([s]) => berkasLabels[s] ?? s),
                    datasets: [{
                        data: berkasEntries.map(([, t]) => t),
                        backgroundColor: berkasEntries.map(([s]) => berkasColors[s] ?? 'rgba(108, 117, 125, 0.8)'),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } }
                }
            });
        }
    }

    // Ticket Status Bar
    const ticketCtx = document.getElementById('ticketStatusChart');
    if (ticketCtx) {
        const ticketData = @json($ticketStatusBreakdown);
        const ticketLabels = {PAID: 'PAID', CHECKED_IN: 'Checked In', PENDING: 'Pending', EXPIRED: 'Expired'};
        const ticketColors = {PAID: 'rgba(41, 182, 115, 0.8)', CHECKED_IN: 'rgba(94, 126, 210, 0.8)', PENDING: 'rgba(252, 143, 0, 0.8)', EXPIRED: 'rgba(239, 83, 80, 0.8)'};
        const order = ['PAID', 'CHECKED_IN', 'PENDING', 'EXPIRED'];
        const ticketEntries = order.map(s => [s, (ticketData[s] ? ticketData[s].qty : 0)]);
        new Chart(ticketCtx, {
            type: 'bar',
            data: {
                labels: ticketEntries.map(([s]) => ticketLabels[s] ?? s),
                datasets: [{
                    label: 'Jumlah Tiket',
                    data: ticketEntries.map(([, t]) => t),
                    backgroundColor: ticketEntries.map(([s]) => ticketColors[s] ?? 'rgba(108, 117, 125, 0.8)'),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
</script>
@endscript
