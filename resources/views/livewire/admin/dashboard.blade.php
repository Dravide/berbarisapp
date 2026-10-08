{{-- Modal tolak HARUS di dalam elemen root ini: Livewire hanya memorph root
     element, jadi modal yang ditaruh setelah </div> penutup tidak pernah
     muncul setelah aksi wire:click. --}}
<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Admin Dashboard Overview</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Admin Stats</li>
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

            {{-- Pencarian global --}}
            <div class="card mb-4 position-relative" style="z-index: 1020;">
                <div class="card-body py-3">
                    <div class="position-relative">
                        <i class="ti ti-search position-absolute top-50 translate-middle-y text-muted ms-3"></i>
                        <input type="text" class="form-control ps-5" wire:model.live.debounce.300ms="globalSearch"
                               placeholder="Cari event, user, sekolah, atau pendaftar... (min. 2 karakter)">
                        <button type="button" class="btn-close position-absolute top-50 translate-middle-y me-2"
                                wire:click="closeSearch" style="right: 0;"
                                wire:show="$showSearchResults"></button>
                    </div>

                    @if($showSearchResults && $searchResults)
                        <div class="border rounded p-3 mt-3 bg-white shadow-sm">
                            @php $adaHasil = $searchResults['events']->isNotEmpty() || $searchResults['users']->isNotEmpty() || $searchResults['schools']->isNotEmpty() || $searchResults['registrations']->isNotEmpty(); @endphp
                            @if(!$adaHasil)
                                <p class="text-muted text-center py-3 mb-0">Tidak ada hasil untuk "{{ $globalSearch }}".</p>
                            @else
                                <div class="row g-3">
                                    @if($searchResults['events']->isNotEmpty())
                                        <div class="col-md-6">
                                            <h6 class="fw-semibold text-muted mb-2"><i class="ti ti-calendar-event me-1"></i> Event</h6>
                                            @foreach($searchResults['events'] as $e)
                                                <a href="{{ route('admin.eventner.show', $e->id) }}" class="d-block text-decoration-none text-dark py-1 border-bottom">
                                                    <span class="fw-semibold">{{ $e->nama_event }}</span>
                                                    <small class="text-muted d-block">{{ $e->diselenggarakan_oleh }} &bull; {{ $e->status }}</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($searchResults['users']->isNotEmpty())
                                        <div class="col-md-6">
                                            <h6 class="fw-semibold text-muted mb-2"><i class="ti ti-user me-1"></i> User</h6>
                                            @foreach($searchResults['users'] as $u)
                                                <a href="{{ route('admin.users.index') }}" class="d-block text-decoration-none text-dark py-1 border-bottom">
                                                    <span class="fw-semibold">{{ $u->name }}</span>
                                                    <small class="text-muted d-block">{{ $u->email }} &bull; {{ $u->role }}</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($searchResults['schools']->isNotEmpty())
                                        <div class="col-md-6">
                                            <h6 class="fw-semibold text-muted mb-2"><i class="ti ti-school me-1"></i> Sekolah</h6>
                                            @foreach($searchResults['schools'] as $s)
                                                <a href="{{ route('admin.schools.show', $s->npsn) }}" class="d-block text-decoration-none text-dark py-1 border-bottom">
                                                    <span class="fw-semibold">{{ $s->nama_sekolah }}</span>
                                                    <small class="text-muted d-block">NPSN {{ $s->npsn }}</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($searchResults['registrations']->isNotEmpty())
                                        <div class="col-md-6">
                                            <h6 class="fw-semibold text-muted mb-2"><i class="ti ti-id-badge me-1"></i> Pendaftar</h6>
                                            @foreach($searchResults['registrations'] as $r)
                                                <a href="{{ route('admin.eventner.show', $r->eventner_id) }}" class="d-block text-decoration-none text-dark py-1 border-bottom">
                                                    <span class="fw-semibold">{{ $r->nama_sekolah }}</span>
                                                    <small class="text-muted d-block">{{ $r->eventner?->nama_event ?? '-' }} &bull; {{ $r->nama_pelatih }}</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Global Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-info-subtle shadow-none border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="ti ti-users fs-7"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Eventner</h6>
                                    <h3 class="mb-0 fw-bold">{{ $totalEventners }}</h3>
                                    <small class="text-muted">Penyelenggara Terdaftar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary-subtle shadow-none border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="ti ti-id-badge fs-7"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Total Pendaftar</h6>
                                    <h3 class="mb-0 fw-bold">{{ $totalRegistrations }}</h3>
                                    <small class="text-muted">Seluruh Event</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success-subtle shadow-none border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="ti ti-currency-dollar fs-7"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0 text-muted">Global Revenue</h6>
                                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                                    <small class="text-muted">Voting Terbayar</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('admin.eventner.pending') }}" class="text-decoration-none">
                        <div class="card {{ $pendingCount > 0 ? 'bg-warning-subtle' : 'bg-light' }} shadow-none border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="{{ $pendingCount > 0 ? 'bg-warning' : 'bg-secondary' }} text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="ti ti-hourglass fs-7"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0 text-muted">Menunggu Approval</h6>
                                        <h3 class="mb-0 fw-bold {{ $pendingCount > 0 ? 'text-warning-emphasis' : '' }}">{{ $pendingCount }}</h3>
                                        <small class="text-muted">{{ $pendingCount > 0 ? 'Perlu diproses' : 'Semua beres' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row">
                {{-- Antrean approval --}}
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between">
                            <h5 class="card-title fw-semibold mb-0">Antrean Persetujuan</h5>
                            <a href="{{ route('admin.eventner.pending') }}" class="btn btn-sm btn-outline-primary">
                                Semua <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
                            </a>
                        </div>
                        <div class="card-body">
                            @if($pendingQueue->isEmpty())
                                <div class="text-center py-4">
                                    <i class="ti ti-circle-check text-success" style="font-size: 2.5rem;"></i>
                                    <p class="text-muted mb-0 mt-2">Tidak ada pendaftaran yang menunggu.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Event</th>
                                                <th>Penyelenggara</th>
                                                <th>Daftar</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingQueue as $e)
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{{ $e->nama_event }}</div>
                                                        <small class="text-muted">{{ $e->lokasi }}</small>
                                                    </td>
                                                    <td>
                                                        <div>{{ $e->user->name }}</div>
                                                        <small class="text-muted">{{ $e->user->email }}</small>
                                                    </td>
                                                    <td><small>{{ $e->created_at->format('d/m/Y') }}</small></td>
                                                    <td class="text-end">
                                                        <div class="d-flex gap-1 justify-content-end">
                                                            <button type="button" class="btn btn-success btn-sm" wire:click="approve({{ $e->id }})" wire:loading.attr="disabled">
                                                                <i class="ti ti-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" wire:click="openRejectModal({{ $e->id }})" wire:loading.attr="disabled">
                                                                <i class="ti ti-x"></i>
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
                </div>

                {{-- Paket & trial + kesehatan pembayaran --}}
                <div class="col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Paket & Masa Uji</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
                                <span><i class="ti ti-lock-up text-danger me-2"></i>Trial berakhir (terkunci)</span>
                                <span class="badge bg-danger-subtle text-danger fs-6">{{ $trialExpiredCount }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span><i class="ti ti-hourglass-high text-warning me-2"></i>Berakhir ≤ 7 hari</span>
                                <span class="badge bg-warning-subtle text-warning-emphasis fs-6">{{ $trialSoonCount }}</span>
                            </div>
                            @if($expiredEvents->isNotEmpty())
                                <hr>
                                <div class="fs-2 fw-semibold text-muted text-uppercase mb-2">Perlu Follow-up</div>
                                @foreach($expiredEvents as $e)
                                    <div class="d-flex align-items-center justify-content-between gap-2 py-1">
                                        <div class="text-truncate">
                                            <a href="{{ route('admin.eventner.show', $e->id) }}" class="text-decoration-none">{{ \Illuminate\Support\Str::limit($e->nama_event, 28) }}</a>
                                            <small class="text-muted d-block">{{ $e->user->name }}</small>
                                        </div>
                                        <span class="badge bg-danger-subtle text-danger text-nowrap">habis {{ $e->trial_ends_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Kesehatan Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            @php $paymentHealthy = $stuckVoteCount === 0 && $stuckTicketCount === 0; @endphp
                            @if($paymentHealthy)
                                <div class="d-flex align-items-center gap-2 text-success">
                                    <i class="ti ti-heartbeat fs-5"></i>
                                    <span>Aman — tidak ada transaksi menggantung &gt; 2 jam.</span>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2 {{ $stuckVoteCount > 0 ? 'text-warning-emphasis' : '' }}">
                                    <span><i class="ti ti-heart me-2"></i>Vote PENDING/EXPIRED &gt; 2 jam</span>
                                    <span class="badge {{ $stuckVoteCount > 0 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted' }} fs-6">{{ $stuckVoteCount }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span><i class="ti ti-ticket me-2"></i>Tiket PENDING &gt; 2 jam</span>
                                    <span class="badge {{ $stuckTicketCount > 0 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-light text-muted' }} fs-6">{{ $stuckTicketCount }}</span>
                                </div>
                                <small class="text-muted d-block mt-2 mb-0">Cek di {{ route('admin.revenue') }} atau dashboard event terkait — bisa jadi webhook telat.</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- Tren --}}
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Tren 6 Bulan</h5>
                        </div>
                        <div class="card-body">
                            <div style="position: relative; height: 280px;">
                                <canvas id="adminTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Event mendekati hari-H --}}
                <div class="col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title fw-semibold mb-0">Event ≤ 7 Hari</h5>
                        </div>
                        <div class="card-body">
                            @forelse($upcomingEvents as $e)
                                @php $siap = $e->rubrik_count > 0 && $e->juri_count > 0; @endphp
                                <div class="d-flex align-items-start justify-content-between gap-2 border-bottom pb-2 mb-2 {{ $loop->last ? 'border-0 mb-0 pb-0' : '' }}">
                                    <div class="text-truncate">
                                        <a href="{{ route('admin.eventner.show', $e->id) }}" class="fw-semibold text-decoration-none">{{ \Illuminate\Support\Str::limit($e->nama_event, 32) }}</a>
                                        <small class="text-muted d-block">
                                            {{ $e->tanggal_pendek ?? '-' }} &bull; {{ $e->lokasi }}
                                        </small>
                                        <small class="d-block mt-1">
                                            @if($e->rubrik_count > 0)
                                                <span class="badge bg-success-subtle text-success">Rubrik {{ $e->rubrik_count }}</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Rubrik kosong</span>
                                            @endif
                                            @if($e->juri_count > 0)
                                                <span class="badge bg-success-subtle text-success">Juri {{ $e->juri_count }}</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Juri kosong</span>
                                            @endif
                                        </small>
                                    </div>
                                    @if(!$siap)
                                        <span class="badge bg-warning text-dark text-nowrap align-self-start">Belum siap</span>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="ti ti-calendar-check text-muted" style="font-size: 2.5rem;"></i>
                                    <p class="text-muted mb-0 mt-2">Tidak ada event dalam 7 hari ke depan.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fw-semibold">Quick Actions</h5>
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <a href="{{ route('admin.eventner.index') }}" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-users fs-8 mb-2"></i>
                                        <span>Kelola Eventner</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-user-cog fs-8 mb-2"></i>
                                        <span>Manajemen User</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-school fs-8 mb-2"></i>
                                        <span>Data Sekolah</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center">
                                        <i class="ti ti-settings fs-8 mb-2"></i>
                                        <span>Pengaturan Situs</span>
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <a href="{{ route('admin.exports.eventners') }}" class="btn btn-outline-dark w-100 py-2 d-flex flex-column align-items-center">
                                        <i class="ti ti-download fs-6"></i>
                                        <span class="small">CSV Eventner</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.exports.registrations') }}" class="btn btn-outline-dark w-100 py-2 d-flex flex-column align-items-center">
                                        <i class="ti ti-download fs-6"></i>
                                        <span class="small">CSV Pendaftar</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.exports.transactions') }}" class="btn btn-outline-dark w-100 py-2 d-flex flex-column align-items-center">
                                        <i class="ti ti-download fs-6"></i>
                                        <span class="small">CSV Transaksi</span>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('admin.audit-log') }}" class="btn btn-outline-dark w-100 py-2 d-flex flex-column align-items-center">
                                        <i class="ti ti-history fs-6"></i>
                                        <span class="small">Audit Log</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tolak --}}
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
                            <textarea wire:model="rejectionReason" class="form-control" rows="3" placeholder="Contoh: Data tidak lengkap, nama event sudah terdaftar..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showRejectModal', false)">Batal</button>
                        <button type="button" class="btn btn-danger btn-sm" wire:click="reject" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ti ti-x"></i> Konfirmasi Tolak</span>
                            <span wire:loading><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Chart: pola sama dengan eventner/dashboard.blade.php — @assets untuk
         CDN, @script agar ikut lifecycle Livewire. --}}
    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endassets
    @script
    <script>
        const trendCtx = document.getElementById('adminTrendChart');
        if (trendCtx) {
            const regs = @json($this->registrationsTrend);
            const rev = @json($this->revenueTrend);
            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: regs.map(d => d.month),
                    datasets: [
                        {
                            label: 'Pendaftar',
                            data: regs.map(d => d.total),
                            backgroundColor: 'rgba(94, 126, 210, 0.7)',
                            borderRadius: 4,
                        },
                        {
                            label: 'Revenue (Rp)',
                            type: 'line',
                            data: rev.map(d => d.total),
                            borderColor: '#29b673',
                            backgroundColor: '#29b673',
                            tension: 0.3,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Pendaftar' },
                            ticks: { precision: 0 }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value/1000000).toFixed(1) + 'jt';
                                    if (value >= 1000) return 'Rp ' + (value/1000).toFixed(0) + 'rb';
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: { ticks: { maxRotation: 45, maxTicksLimit: 8, font: { size: 10 } } }
                    }
                }
            });
        }
    </script>
    @endscript
</div>
