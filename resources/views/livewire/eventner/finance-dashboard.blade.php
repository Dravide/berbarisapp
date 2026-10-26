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
                    <a href="{{ route('eventner.finance.pdf') }}" class="btn btn-danger btn-sm rounded-pill px-3 mb-2">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                    </a>
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

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
                            <i class="ti ti-cash fs-6"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Uang Masuk (Pendaftaran)</h6>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($feeRevenue, 0, ',', '.') }}</h4>
                            <small class="text-muted">Dari total tagihan Rp {{ number_format($totalTagihan, 0, ',', '.') }}</small>
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
                            <i class="ti ti-receipt fs-6"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-0 text-muted">Sisa Tagihan</h6>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($piutang, 0, ',', '.') }}</h4>
                            <small class="text-muted">Belum dibayar peserta</small>
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
                            <h6 class="mb-0 text-muted">Tiket + Voting</h6>
                            <h4 class="mb-0 fw-bold">Rp {{ number_format($ticketRevenue + $voteRevenue, 0, ',', '.') }}</h4>
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
                        <span class="badge bg-warning rounded-pill">{{ $pendingVerificationCount }} Bukti Menunggu</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse($pendingPayments as $bayar)
                        @php $reg = $bayar->registration; @endphp
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <h6 class="fw-semibold mb-0">{{ $reg?->display_name }}</h6>
                                    <small class="text-muted">{{ $reg?->competitionCategory?->full_name }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge {{ $bayar->isDp() ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} mb-1">{{ $bayar->labelJenis() }}</span>
                                    <span class="fw-bold text-primary d-block">Rp {{ number_format((float) $bayar->amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <small class="text-muted d-block">Dikirim {{ $bayar->created_at?->diffForHumans() }}
                                @if($reg)
                                    &middot; sisa tagihan Rp {{ number_format($reg->sisaTagihan(), 0, ',', '.') }}
                                @endif
                            </small>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-sm btn-primary" wire:click="openPaymentModal({{ $bayar->id }})">
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
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title fw-semibold mb-0">Pendapatan per Kategori Lomba</h5>
            <button class="btn btn-sm btn-primary" wire:click="openDetailModal">
                <i class="ti ti-list-details me-1"></i> Detail Pembayaran
            </button>
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
                            <th class="text-center">DP</th>
                            <th class="text-center">Menunggu</th>
                            <th class="text-center">Belum Bayar</th>
                            <th class="text-end pe-4">Terkumpul</th>
                            <th class="text-end pe-4">Sisa Tagihan</th>
                            <th class="text-center pe-4">Aksi</th>
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
                                    @if($cat['dp_count'] > 0)
                                        <span class="badge bg-primary-subtle text-primary px-3 py-1">{{ $cat['dp_count'] }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
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
                                <td class="text-end pe-4 text-muted">Rp {{ number_format($cat['sisa_tagihan'], 0, ',', '.') }}</td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-light" wire:click="openDetailModal({{ $cat['id'] }})"
                                            title="Detail pembayaran kategori {{ $cat['name'] }}">
                                        <i class="ti ti-list-details"></i> Detail
                                    </button>
                                </td>
                            </tr>                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Belum ada kategori lomba.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Detail Pembayaran --}}
    <div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        Detail Pembayaran
                        @if($detailCategoryId !== 'all')
                            <span class="text-muted fs-6 fw-normal">— {{ $this->detailCategories->firstWhere('id', (int) $detailCategoryId)?->full_name }}</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Filter --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <select class="form-select" wire:model.live="detailCategoryId">
                                <option value="all">Semua Kategori</option>
                                @foreach($this->detailCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" wire:model.live="detailStatus">
                                <option value="all">Semua Status</option>
                                <option value="paid">Lunas</option>
                                <option value="dp_paid">Baru DP</option>
                                <option value="pending_verification">Menunggu Verifikasi</option>
                                <option value="unpaid">Belum Bayar</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                                {{ $this->filteredPaymentDetails->count() }} data
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Sekolah</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Total Biaya</th>
                                    <th class="text-end">Dibayar</th>
                                    <th class="text-end">Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->filteredPaymentDetails as $reg)
                                    <tr>
                                        <td class="fw-semibold">{{ $reg->display_name }}</td>
                                        <td class="text-muted">{{ $reg->competitionCategory?->full_name }}</td>
                                        <td class="text-center">
                                            @if($reg->payment_status === 'paid')
                                                <span class="badge bg-success-subtle text-success px-3 py-2"><i class="ti ti-circle-check me-1"></i>Lunas</span>
                                            @elseif($reg->payment_status === 'dp_paid')
                                                <span class="badge bg-primary-subtle text-primary px-3 py-2"><i class="ti ti-progress me-1"></i>Baru DP</span>
                                            @elseif($reg->payment_status === 'pending_verification')
                                                <span class="badge bg-warning-subtle text-warning px-3 py-2"><i class="ti ti-hourglass me-1"></i>Menunggu Verifikasi</span>
                                            @elseif($reg->payment_status === 'unpaid')
                                                <span class="badge bg-danger-subtle text-danger px-3 py-2"><i class="ti ti-circle-x me-1"></i>Belum Bayar</span>
                                            @else
                                                <span class="badge bg-light text-muted border px-3 py-2">{{ $reg->payment_status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ number_format($reg->total_fee, 0, ',', '.') }}</td>
                                        <td class="text-end text-success fw-semibold">Rp {{ number_format((float) $reg->amount_paid, 0, ',', '.') }}</td>
                                        <td class="text-end text-muted">Rp {{ number_format($reg->sisaTagihan(), 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            Tidak ada data sesuai filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
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
                    @php
                        $bayar = $this->selectedPayment;
                        $reg = $bayar->registration;
                        $seharusnya = $this->nominalSeharusnya;
                        $beda = $seharusnya !== null && abs((float) $bayar->amount - (float) $seharusnya) > 0.5;
                    @endphp
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted fs-3 mb-3">Detail Pendaftaran</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">Nama Sekolah</td>
                                    <td class="fw-semibold">{{ $reg?->display_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kategori Lomba</td>
                                    <td class="fw-semibold">{{ $reg?->competitionCategory?->full_name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pelatih</td>
                                    <td>{{ $reg?->nama_pelatih }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">No. HP</td>
                                    <td>{{ $reg?->no_hp }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Biaya</td>
                                    <td class="fw-bold text-primary">Rp {{ number_format((float) $reg?->total_fee, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Sudah Dibayar</td>
                                    <td class="text-success fw-semibold">Rp {{ number_format((float) $reg?->amount_paid, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Sisa Tagihan</td>
                                    <td class="fw-semibold">Rp {{ number_format((float) $reg?->sisaTagihan(), 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Rekening Tujuan</td>
                                    <td>
                                        @if($bayar->bankAccount)
                                            {{ $bayar->bankAccount->bank_name }} — {{ $bayar->bankAccount->account_number }}
                                            ({{ $bayar->bankAccount->account_name }})
                                        @else
                                            <span class="text-muted">Tidak tercatat</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-muted fs-3 mb-3">Bukti Transfer</h6>
                            @if($bayar->proof)
                                <a href="{{ asset('storage/' . $bayar->proof) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $bayar->proof) }}" class="img-fluid rounded border w-100" alt="Bukti pembayaran">
                                </a>
                            @else
                                <div class="text-center py-5 bg-light-subtle rounded border text-muted">
                                    <i class="ti ti-file-off fs-8 d-block mb-2"></i>
                                    Tidak ada bukti pembayaran.
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis &amp; nominal klaim peserta</label>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $bayar->isDp() ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} px-3 py-2">{{ $bayar->labelJenis() }}</span>
                                <span class="fw-bold fs-5">Rp {{ number_format((float) $bayar->amount, 0, ',', '.') }}</span>
                            </div>
                            @if($seharusnya !== null)
                                <small class="text-muted d-block mt-1">
                                    Seharusnya Rp {{ number_format((float) $seharusnya, 0, ',', '.') }}.
                                </small>
                            @endif
                            @if($beda)
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 fs-2">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    Nominal klaim berbeda dari yang seharusnya. Cocokkan dengan mutasi rekening sebelum menerima.
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nominal final diterima (Rp)</label>
                            <input type="number" min="0" step="1000" class="form-control" wire:model="nominalDiterima">
                            @error('nominalDiterima') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="text-muted d-block mt-1">Isi sesuai angka di mutasi rekening. Angka inilah yang dicatat.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alasan penolakan (opsional)</label>
                            <input type="text" class="form-control" wire:model="alasanTolak" placeholder="Contoh: nominal tidak sesuai mutasi">
                            <small class="text-muted d-block mt-1">Peserta melihat alasan ini di portal dan dapat mengunggah ulang.</small>
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
                    <button type="button" class="btn btn-outline-danger" wire:click="rejectPayment({{ $this->selectedPayment->id }})">
                        <i class="ti ti-x"></i> Tolak
                    </button>
                    <button type="button" class="btn btn-success" wire:click="verifyPayment({{ $this->selectedPayment->id }})">
                        <i class="ti ti-circle-check"></i> Terima
                    </button>
                @endif
            </div>
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

    // Modal detail pembayaran
    const detailModalEl = document.getElementById('paymentDetailModal');
    let detailModal = null;
    if (detailModalEl) {
        detailModal = new bootstrap.Modal(detailModalEl);
        window.Livewire.on('open-detail-modal', () => detailModal.show());
        window.Livewire.on('close-detail-modal', () => detailModal.hide());
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
