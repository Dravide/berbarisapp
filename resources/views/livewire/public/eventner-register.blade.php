<div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
    <div class="position-relative z-index-5">
        <div class="row">
            <div class="col-xl-7 col-xxl-8">
                <a href="{{ url('/') }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
                    @php
                        $logoSrc = get_setting('logo_dark') ? Storage::url(get_setting('logo_dark')) : asset('templates/assets/images/logos/dark-logo.svg');
                    @endphp
                    <img src="{{ $logoSrc }}" width="180" alt="Logo" />
                </a>
                <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
                    <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="modernize-img" class="img-fluid" width="500">
                </div>
            </div>

            <div class="col-xl-5 col-xxl-4">
                <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                    <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">

                        @if($showPayment)
                            {{-- Payment Page --}}
                            <div class="text-center">
                                <h2 class="mb-1 fs-7 fw-bold">Bayar Pendaftaran</h2>
                                <p class="mb-4 text-muted">Scan QRIS untuk mengaktifkan akun eventner</p>

                                <div class="card border rounded-3 p-4 mb-4 bg-white shadow-sm">
                                    <div class="d-flex justify-content-between mb-3 pb-2 border-bottom">
                                        <span class="text-muted">Paket</span>
                                        <span class="fw-semibold">Berbayar</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted">Total</span>
                                        <span class="fw-bold fs-5" style="color: #0062ff;">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                @if($paymentQrUrl)
                                    <div class="mb-4 p-3 bg-white border rounded-3 d-inline-block shadow-sm">
                                        <img src="{{ $paymentQrUrl }}" alt="QRIS" style="width: 220px; height: 220px; object-fit: contain;">
                                    </div>
                                @endif

                                <p class="text-muted small mb-4">Scan QRIS di atas menggunakan aplikasi e-wallet atau mobile banking.</p>

                                <button type="button" class="btn btn-primary w-100 py-8 mb-2 rounded-2" wire:click="checkPayment" wire:loading.attr="disabled">
                                    <span wire:loading.remove><i class="ti ti-refresh me-1"></i> Cek Pembayaran</span>
                                    <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> Memeriksa...</span>
                                </button>

                                <p class="text-muted mt-3 mb-0" style="font-size: 0.75rem;">
                                    <i class="ti ti-info-circle me-1"></i> Halaman ini otomatis mengecek pembayaran. Klik tombol di atas untuk mengecek manual.
                                </p>

                                @error('payment') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                            </div>
                        @else
                            {{-- Registration Form --}}
                            <h2 class="mb-1 fs-7 fw-bold">{{ get_setting('app_name', 'BARIS') }}</h2>
                            <p class="mb-4">Daftar akun eventner baru</p>

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show rounded-2 mb-4" role="alert">
                                    <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form wire:submit="save">
                                {{-- Plan Selection --}}
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Pilih Paket</label>
                                    <div class="d-flex gap-2">
                                        @php $fee = (int) \App\Models\Setting::get('eventner_registration_fee', 50000); @endphp

                                        <label wire:click="$set('plan', 'free')" role="button"
                                            class="flex-fill border rounded-2 p-3 text-center {{ $plan === 'free' ? 'border-primary bg-primary-subtle' : 'border-secondary' }}"
                                            style="cursor: pointer;">
                                            <i class="ti ti-gift fs-4 {{ $plan === 'free' ? 'text-primary' : 'text-muted' }}"></i>
                                            <div class="fw-bold mt-1 mb-0 small {{ $plan === 'free' ? 'text-primary' : '' }}">Gratis</div>
                                            <div class="small fw-semibold {{ $plan === 'free' ? 'text-primary' : 'text-muted' }}">Rp 0</div>
                                            <div class="text-muted mt-1" style="font-size: 0.65rem;">Trial 3 hari, fitur terbatas</div>
                                        </label>

                                        <label wire:click="$set('plan', 'paid')" role="button"
                                            class="flex-fill border rounded-2 p-3 text-center {{ $plan === 'paid' ? 'border-primary bg-primary-subtle' : 'border-secondary' }}"
                                            style="cursor: pointer;">
                                            <i class="ti ti-crown fs-4 {{ $plan === 'paid' ? 'text-primary' : 'text-muted' }}"></i>
                                            <div class="fw-bold mt-1 mb-0 small {{ $plan === 'paid' ? 'text-primary' : '' }}">Berbayar</div>
                                            <div class="small fw-semibold {{ $plan === 'paid' ? 'text-primary' : 'text-muted' }}">Rp {{ number_format($fee, 0, ',', '.') }}</div>
                                            <div class="text-muted mt-1" style="font-size: 0.65rem;">Bayar sekali, akses semua fitur</div>
                                        </label>
                                    </div>
                                    @error('plan') <div class="text-danger small mt-1"><i class="ti ti-alert-circle"></i> {{ $message }}</div> @enderror
                                </div>

                                {{-- Info Akun --}}
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" wire:model.blur="name" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Nama penyelenggara">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" wire:model.blur="username" class="form-control @error('username') is-invalid @enderror" id="username" placeholder="contoh: eventku2024">
                                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" wire:model.blur="email" class="form-control @error('email') is-invalid @enderror" id="email" placeholder="email@contoh.com">
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" wire:model.blur="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Min. 8 karakter">
                                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                                        <input type="password" wire:model.blur="password_confirmation" class="form-control" id="password_confirmation" placeholder="Ulangi password">
                                    </div>
                                </div>

                                {{-- Info Event --}}
                                <div class="mb-3">
                                    <label for="nama_event" class="form-label">Nama Event <span class="text-danger">*</span></label>
                                    <input type="text" wire:model.blur="nama_event" class="form-control @error('nama_event') is-invalid @enderror" id="nama_event" placeholder="Misal: Lomba PBB Tingkat Kabupaten 2026">
                                    @error('nama_event') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="lokasi" class="form-label">Lokasi Event <span class="text-danger">*</span></label>
                                    <input type="text" wire:model.blur="lokasi" class="form-control @error('lokasi') is-invalid @enderror" id="lokasi" placeholder="Misal: Lapangan Upacara SMAN 1 Sukaresmi">
                                    @error('lokasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                {{-- Syarat & Ketentuan --}}
                                <div class="form-check mb-4">
                                    <input type="checkbox" class="form-check-input @error('agreeTerms') is-invalid @enderror"
                                        id="agreeTerms" wire:model="agreeTerms">
                                    <label class="form-check-label small" for="agreeTerms">
                                        Saya menyetujui
                                        <a href="{{ route('terms') }}" target="_blank" class="text-primary text-decoration-none fw-semibold">syarat & ketentuan</a>
                                        dan
                                        <a href="{{ route('privacy') }}" target="_blank" class="text-primary text-decoration-none fw-semibold">kebijakan privasi</a>
                                        yang berlaku.
                                    </label>
                                    @error('agreeTerms') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-8 mb-3 rounded-2" wire:loading.attr="disabled">
                                    <span wire:loading.remove>Daftar Sekarang</span>
                                    <span wire:loading>Memproses...</span>
                                </button>

                                <div class="text-center">
                                    <small class="text-muted">Sudah punya akun?
                                        <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-none">Masuk</a>
                                    </small>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
