<div>
    @if($showPayment)
        {{-- Payment Page for Unpaid Paid Plan --}}
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
                            <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="" class="img-fluid" width="500">
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">

                                @if($paymentQrUrl)
                                    {{-- QR already generated --}}
                                    <div class="text-center mb-3">
                                        <div class="bg-info-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                            <i class="ti ti-credit-card fs-2 text-info"></i>
                                        </div>
                                        <h4 class="fw-semibold mb-1">Selesaikan Pembayaran</h4>
                                        <p class="text-muted mb-0" style="font-size: 0.85rem;">Scan QRIS untuk mengaktifkan event Anda</p>
                                    </div>

                                    @if(session('payment_expired'))
                                        <div class="alert alert-warning alert-dismissible fade show rounded-2 small py-2" role="alert">
                                            <i class="ti ti-clock-off me-1"></i> QRIS sebelumnya kadaluarsa.
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    @if(session('payment_error'))
                                        <div class="alert alert-danger alert-dismissible fade show rounded-2 small py-2" role="alert">
                                            <i class="ti ti-alert-circle me-1"></i> {{ session('payment_error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center border rounded-2 p-3 mb-3 bg-light">
                                        <span class="text-muted small">Total</span>
                                        <span class="fw-bold" style="color: #0062ff;">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</span>
                                    </div>

                                    <div class="text-center mb-3 p-2 bg-white border rounded-2 d-inline-block w-100">
                                        <img src="{{ $paymentQrUrl }}" alt="QRIS" style="width: 180px; height: 180px; object-fit: contain;">
                                    </div>

                                    <p class="text-muted text-center mb-3" style="font-size: 0.75rem;">Scan QRIS menggunakan e-wallet atau mobile banking</p>

                                    <button type="button" class="btn btn-primary w-100 py-2 mb-2 rounded-2" wire:click="checkPayment" wire:loading.attr="disabled">
                                        <span wire:loading.remove><i class="ti ti-refresh me-1"></i> Cek Pembayaran</span>
                                        <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> Memeriksa...</span>
                                    </button>

                                    <button type="button" class="btn btn-outline-secondary w-100 py-2 rounded-2" wire:click="generatePayment" wire:loading.attr="disabled">
                                        <span wire:loading.remove><i class="ti ti-qrcode me-1"></i> Buat QRIS Baru</span>
                                        <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> Membuat...</span>
                                    </button>
                                @else
                                    {{-- No QR yet / expired --}}
                                    <div class="text-center mb-3">
                                        <div class="bg-warning-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                            <i class="ti ti-credit-card fs-2 text-warning"></i>
                                        </div>
                                        <h4 class="fw-semibold mb-1">Pembayaran Diperlukan</h4>
                                        <p class="text-muted mb-0" style="font-size: 0.85rem;">Akun Anda belum aktif. Lakukan pembayaran untuk mengaktifkan event Anda.</p>
                                    </div>

                                    @if(session('payment_expired'))
                                        <div class="alert alert-warning alert-dismissible fade show rounded-2 small py-2" role="alert">
                                            <i class="ti ti-clock-off me-1"></i> QRIS sebelumnya kadaluarsa.
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    @if(session('payment_error'))
                                        <div class="alert alert-danger alert-dismissible fade show rounded-2 small py-2" role="alert">
                                            <i class="ti ti-alert-circle me-1"></i> {{ session('payment_error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <div class="d-flex justify-content-between align-items-center border rounded-2 p-3 mb-4 bg-light">
                                        <span class="text-muted small">Total</span>
                                        <span class="fw-bold" style="color: #0062ff;">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</span>
                                    </div>

                                    <button type="button" class="btn btn-primary w-100 py-2 mb-2 rounded-2" wire:click="generatePayment" wire:loading.attr="disabled">
                                        <span wire:loading.remove><i class="ti ti-qrcode me-1"></i> Bayar Sekarang — Rp {{ number_format($paymentAmount, 0, ',', '.') }}</span>
                                        <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span> Membuat QRIS...</span>
                                    </button>
                                @endif

                                <form action="{{ route('logout') }}" method="POST" class="mt-3 text-center">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                                        <i class="ti ti-logout me-1"></i> Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Generic Inactive Page --}}
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
                            <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="" class="img-fluid" width="500">
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4 text-center">
                                <div class="bg-warning-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 64px; height: 64px;">
                                    <i class="ti ti-lock fs-2 text-warning"></i>
                                </div>
                                <h4 class="fw-semibold mb-2">Akun Dinonaktifkan</h4>
                                <p class="text-muted mb-4" style="font-size: 0.85rem;">Akun Anda sedang dinonaktifkan oleh administrator.</p>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary w-100 py-2 rounded-2">
                                        <i class="ti ti-logout me-1"></i> Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
