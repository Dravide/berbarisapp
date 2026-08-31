<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Dashboard Keuangan</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Keuangan</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Revenue Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
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
            <div class="card shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-success text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="ti ti-file-invoice fs-6"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Biaya Pendaftaran</h6>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($feeRevenue, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="ti ti-heart fs-6"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Voting</h6>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($voteRevenue, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-none border">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-info text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="ti ti-ticket fs-6"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Tiket</h6>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($ticketRevenue, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Pending --}}
    <div class="row mb-4">
        {{-- Revenue Chart --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">Revenue 30 Hari Terakhir</h5>
                </div>
                <div class="card-body">
                    <canvas id="financeRevenueChart" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Verifikasi Pembayaran (via modal) --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-semibold mb-0">Verifikasi Pembayaran</h5>
                    @if($pendingVerificationCount > 0)
                        <span class="badge bg-warning rounded-pill">{{ $pendingVerificationCount }} Menunggu</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse($pendingPayments as $reg)
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <h6 class="fw-semibold mb-0">{{ $reg->nama_sekolah }}</h6>
                                    <small class="text-muted">{{ $reg->competitionCategory->full_name }}</small>
                                </div>
                                <span class="fw-bold text-primary">Rp {{ number_format($reg->total_fee, 0, ',', '.') }}</span>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-sm btn-primary" wire:click="openPaymentModal({{ $reg->id }})">
                                    <i class="ti ti-eye"></i> Review
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="ti ti-circle-check fs-8 d-block mb-2 text-success"></i>
                            Tidak ada pembayaran yang menunggu verifikasi.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Category Breakdown --}}
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title fw-semibold mb-0">Pendapatan per Kategori Lomba</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Kategori</th>
                            <th class="text-center">Biaya/Pasukan</th>
                            <th class="text-center">Total Daftar</th>
                            <th class="text-center">Lunas</th>
                            <th class="text-center">Menunggu</th>
                            <th class="text-center">Belum Bayar</th>
                            <th class="text-end pe-4">Terkumpul</th>
                            <th class="text-end pe-4">Potensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoryBreakdown as $cat)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $cat['name'] }}</td>
                                <td class="text-center">
                                    @if($cat['fee'])
                                        Rp {{ number_format($cat['fee'], 0, ',', '.') }}
                                    @else
                                        <span class="badge bg-light text-muted border">Gratis</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ $cat['total_registrations'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success px-3 py-1">{{ $cat['paid_count'] }}</span>
                                </td>
                                <td class="text-center">
                                    @if($cat['pending_count'] > 0)
                                        <span class="badge bg-warning-subtle text-warning px-3 py-1">{{ $cat['pending_count'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($cat['unpaid_count'] > 0)
                                        <span class="badge bg-danger-subtle text-danger px-3 py-1">{{ $cat['unpaid_count'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format($cat['paid_revenue'], 0, ',', '.') }}</td>
                                <td class="text-end pe-4 text-muted">Rp {{ number_format($cat['potential_revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Belum ada kategori lomba.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Review Pembayaran --}}
<div class="modal fade" id="paymentReviewModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">Review Bukti Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($this->selectedPayment)
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted fs-3 mb-3">Detail Pendaftaran</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Nama Sekolah</td>
                                    <td class="fw-semibold">{{ $this->selectedPayment->nama_sekolah }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kategori Lomba</td>
                                    <td class="fw-semibold">{{ $this->selectedPayment->competitionCategory?->full_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pelatih</td>
                                    <td>{{ $this->selectedPayment->nama_pelatih }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. HP</td>
                                    <td>{{ $this->selectedPayment->no_hp }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Biaya</td>
                                    <td class="fw-bold text-primary">Rp {{ number_format($this->selectedPayment->total_fee, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Rekening Tujuan</td>
                                    <td>
                                        @if($this->selectedPayment->paymentBankAccount)
                                            {{ $this->selectedPayment->paymentBankAccount->bank_name }} — {{ $this->selectedPayment->paymentBankAccount->account_number }}
                                            ({{ $this->selectedPayment->paymentBankAccount->account_name }})
                                        @else
                                            <span class="text-muted">Tidak tercatat</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted fs-3 mb-3">Bukti Transfer</h6>
                            @if($this->selectedPayment->payment_proof)
                                <a href="{{ asset('storage/' . $this->selectedPayment->payment_proof) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $this->selectedPayment->payment_proof) }}" class="img-fluid rounded border w-100" alt="Bukti pembayaran">
                                </a>
                            @else
                                <div class="text-center py-5 bg-light-subtle rounded border text-muted">
                                    <i class="ti ti-file-off fs-8 d-block mb-2"></i>
                                    Tidak ada bukti pembayaran.
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <span class="spinner-border"></span>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                @if($this->selectedPayment)
                    <button type="button" class="btn btn-outline-danger" wire:click="rejectPayment({{ $this->selectedPayment->id }})" wire:confirm="Tolak bukti pembayaran {{ $this->selectedPayment->nama_sekolah }}? Peserta harus upload ulang.">
                        <i class="ti ti-x"></i> Tolak
                    </button>
                    <button type="button" class="btn btn-success" wire:click="verifyPayment({{ $this->selectedPayment->id }})">
                        <i class="ti ti-circle-check"></i> Verifikasi
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@script
<script>
    // Modal buka/tutup dari komponen Livewire
    const paymentModalEl = document.getElementById('paymentReviewModal');
    let paymentModal = null;
    if (paymentModalEl) {
        paymentModal = new bootstrap.Modal(paymentModalEl);
        window.Livewire.on('open-payment-modal', () => paymentModal.show());
        window.Livewire.on('close-payment-modal', () => paymentModal.hide());
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('financeRevenueChart');
    if (ctx) {
        const data = @json($revenueData);
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.date),
                datasets: [
                    {
                        label: 'Pendaftaran',
                        data: data.map(d => d.fee),
                        backgroundColor: 'rgba(41, 182, 115, 0.7)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Voting',
                        data: data.map(d => d.vote),
                        backgroundColor: 'rgba(94, 126, 210, 0.7)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Tiket',
                        data: data.map(d => d.ticket),
                        backgroundColor: 'rgba(252, 143, 0, 0.7)',
                        borderRadius: 4,
                    },
                ],
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
</script>
@endscript
