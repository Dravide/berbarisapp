<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Paket &amp; Tagihan</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Upgrade Paket</li>
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
    @if (session()->has('error'))
        <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ============================ --}}
    {{-- QRIS PEMBAYARAN --}}
    {{-- ============================ --}}
    @if ($showPayment)
        <div class="card w-100 mb-4">
            <div class="card-body p-4 text-center">
                <h5 class="fw-semibold mb-2">Scan QRIS untuk Bayar</h5>
                <p class="text-muted mb-4">
                    Paket berbayar — aktivasi otomatis setelah pembayaran terkonfirmasi.
                </p>

                @if ($paymentQrUrl)
                    <div class="d-flex justify-content-center mb-3">
                        <div class="border rounded-3 p-3 bg-white">
                            <img src="{{ $paymentQrUrl }}" alt="QRIS" style="width: 220px; height: 220px; object-fit: contain;">
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning d-inline-block">
                        QR tidak tersedia. Buat QR baru di bawah.
                    </div>
                @endif

                <p class="fw-semibold fs-5 mb-4">Total: <span class="text-primary">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</span></p>

                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-primary w-100 py-8 mb-2 rounded-2" wire:click="checkPayment" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="checkPayment"><i class="ti ti-refresh me-1"></i> Cek Status Pembayaran</span>
                            <span wire:loading wire:target="checkPayment"><span class="spinner-border spinner-border-sm me-1"></span> Memeriksa...</span>
                        </button>
                        <p class="text-muted small mb-0">
                            Pembayaran juga terdeteksi otomatis via sistem. Halaman akan mengarahkan ke dashboard setelah berhasil.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ============================ --}}
        {{-- STATUS PLAN & PERBANDINGAN --}}
        {{-- ============================ --}}
        @php
            $features = config('eventner_features', []);
            $price = (int) \App\Models\Setting::get('eventner_plan_price', 150000);
            $isTrialExpired = $eventner->isTrialExpired();
            $trialDaysLeft = $eventner->trialDaysLeft();
        @endphp

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100 {{ $isTrialExpired ? 'border-secondary' : 'border-primary' }}">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="fw-semibold mb-1">Paket Gratis</h5>
                                <p class="text-muted small mb-0">Paket Anda saat ini</p>
                            </div>
                            <span class="badge {{ $isTrialExpired ? 'bg-danger' : 'bg-warning text-dark' }} fs-2">
                                {{ $isTrialExpired ? 'Trial Berakhir' : 'Trial ' . $trialDaysLeft . ' Hari' }}
                            </span>
                        </div>
                        <h3 class="fw-bold mb-3">Gratis</h3>
                        <ul class="list-unstyled d-flex flex-column gap-2 mb-4 small">
                            <li><i class="ti ti-check text-success me-2"></i>Fitur dasar: peserta, juri, input & rekap nilai, scoreboard</li>
                            @if (!$isTrialExpired && $trialDaysLeft > 0)
                                <li><i class="ti ti-clock text-warning me-2"></i>Sementara bisa akses fitur premium (masih trial)</li>
                            @else
                                @foreach(array_slice($features, 0, 6) as $key => $config)
                                    <li class="text-muted"><i class="ti ti-lock text-muted me-2"></i>{{ $config['label'] }}</li>
                                @endforeach
                            @endif
                        </ul>
                        <span class="btn btn-light disabled w-100">Sedang Digunakan</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 border-success shadow-sm position-relative overflow-hidden">
                    <span class="badge bg-success position-absolute top-0 end-0 m-3">Rekomendasi</span>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <h5 class="fw-semibold mb-1">Paket Berbayar</h5>
                            <p class="text-muted small mb-0">Bayar sekali per event</p>
                        </div>
                        <h3 class="fw-bold mb-1">Rp {{ number_format($price, 0, ',', '.') }}</h3>
                        <p class="text-muted small mb-3">Sekali bayar, aktif selama event ini</p>
                        <ul class="list-unstyled d-flex flex-column gap-2 mb-4 small">
                            <li><i class="ti ti-check text-success me-2"></i>Semua fitur paket gratis</li>
                            @foreach($features as $key => $config)
                                <li><i class="ti ti-check text-success me-2"></i>{{ $config['label'] }}</li>
                            @endforeach
                            <li><i class="ti ti-check text-success me-2"></i>Update & dukungan prioritas</li>
                        </ul>
                        <button type="button" class="btn btn-primary w-100 py-8 rounded-2" wire:click="generatePayment" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="generatePayment"><i class="ti ti-bolt me-1"></i> Upgrade Sekarang</span>
                            <span wire:loading wire:target="generatePayment"><span class="spinner-border spinner-border-sm me-1"></span> Membuat QRIS...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis">
            <i class="ti ti-info-circle me-2"></i>
            Pembayaran via QRIS. Setelah settle, semua fitur premium pada event ini langsung aktif tanpa menunggu verifikasi manual.
        </div>
    @endif
</div>
