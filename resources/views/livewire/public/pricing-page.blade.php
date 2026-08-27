<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-6 fw-bold mb-3">Harga &amp; Paket</h1>
        <p class="text-muted mx-auto" style="max-width: 640px;">
            Kelola perlombaan sekolah dengan gratis. Aktifkan fitur premium sekali bayar per event — tanpa langganan bulanan.
        </p>
    </div>

    <div class="row g-4 justify-content-center align-items-stretch">
        {{-- ================= Paket Gratis ================= --}}
        <div class="col-md-5 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="fw-semibold mb-1">Gratis</h5>
                    <p class="text-muted small mb-4">Untuk mulai mengelola lomba</p>
                    <div class="mb-4">
                        <span class="fs-2 fw-bold">Rp 0</span>
                    </div>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-4 small">
                        <li><i class="ti ti-check text-success me-2"></i>Dashboard event & profil</li>
                        <li><i class="ti ti-check text-success me-2"></i>Kategori lomba & pendaftaran peserta</li>
                        <li><i class="ti ti-check text-success me-2"></i>Manajemen juri & input nilai</li>
                        <li><i class="ti ti-check text-success me-2"></i>Rekap nilai & scoreboard publik</li>
                        <li><i class="ti ti-check text-success me-2"></i>QR check-in & sertifikat dasar</li>
                    </ul>
                    <div class="mt-auto">
                        @auth
                            @if(auth()->user()->role === 'Eventner' && auth()->user()->eventner?->plan !== 'paid')
                                <a href="{{ route('eventner.billing.upgrade') }}" class="btn btn-outline-primary w-100 py-8 rounded-2">Kelola Paket</a>
                            @else
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary w-100 py-8 rounded-2">Ke Dashboard</a>
                            @endif
                        @else
                            <a href="{{ route('register.eventner') }}?plan=free" class="btn btn-outline-primary w-100 py-8 rounded-2">Daftar Gratis</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= Paket Berbayar ================= --}}
        <div class="col-md-5 col-lg-4">
            <div class="card h-100 border-0 shadow-lg rounded-4 position-relative overflow-hidden border-top border-4 border-success">
                <span class="badge bg-success position-absolute top-0 end-0 m-3">Rekomendasi</span>
                <div class="card-body p-4 d-flex flex-column">
                    <h5 class="fw-semibold mb-1">Paket Event Penuh</h5>
                    <p class="text-muted small mb-4">Bayar sekali, aktif selama event</p>
                    <div class="mb-1">
                        <span class="fs-2 fw-bold text-primary">Rp {{ number_format($planPrice, 0, ',', '.') }}</span>
                    </div>
                    <p class="text-muted small mb-4">
                        + Biaya pendaftaran event Rp {{ number_format($regFee, 0, ',', '.') }}
                    </p>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-4 small">
                        <li><i class="ti ti-check text-success me-2"></i>Semua fitur paket gratis</li>
                        @foreach($premiumFeatures as $feature)
                            <li><i class="ti ti-check text-success me-2"></i>{{ $feature['label'] }}</li>
                        @endforeach
                        <li><i class="ti ti-check text-success me-2"></i>Aktivasi otomatis setelah bayar</li>
                    </ul>
                    <div class="mt-auto">
                        @auth
                            @if(auth()->user()->role === 'Eventner' && auth()->user()->eventner?->plan !== 'paid')
                                <a href="{{ route('eventner.billing.upgrade') }}" class="btn btn-primary w-100 py-8 rounded-2"><i class="ti ti-bolt me-1"></i> Upgrade Sekarang</a>
                            @elseif(auth()->user()->role === 'Eventner')
                                <span class="btn btn-success disabled w-100"><i class="ti ti-circle-check me-1"></i> Sudah Aktif</span>
                            @else
                                <a href="{{ route('dashboard') }}" class="btn btn-primary w-100 py-8 rounded-2">Ke Dashboard</a>
                            @endif
                        @else
                            <a href="{{ route('register.eventner') }}" class="btn btn-primary w-100 py-8 rounded-2">Mulai Sekarang</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-lg-8">
            <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis rounded-4">
                <div class="d-flex gap-3">
                    <i class="ti ti-info-circle fs-4 flex-shrink-0"></i>
                    <div>
                        <strong>Cara kerja pembayaran:</strong>
                        Pilih upgrade → scan QRIS → aktivasi otomatis dalam hitungan detik setelah pembayaran terkonfirmasi. Tidak ada verifikasi manual, tidak ada biaya tersembunyi. Satu kali bayar berlaku untuk satu event sampai selesai.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
