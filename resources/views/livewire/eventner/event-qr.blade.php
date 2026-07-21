<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">QR Link Event</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">QR Link Event</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <h5 class="card-title fw-semibold mb-2">QR Code Halaman Event</h5>
                    <p class="text-muted mb-4">
                        Scan untuk membuka halaman publik
                        <a href="{{ $eventner->publicUrl('detail') }}" target="_blank" class="text-decoration-none">
                            {{ $eventner->subdomain ? $eventner->subdomain . '.' . parse_url(config('app.url'), PHP_URL_HOST) : $eventner->nama_event }}
                            <i class="ti ti-external-link"></i>
                        </a>
                    </p>

                    @if($qrDataUri)
                        <div class="d-inline-block bg-white p-4 rounded-3 border shadow-sm">
                            <img src="{{ $qrDataUri }}" alt="QR Event {{ $eventner->nama_event }}" class="img-fluid" style="max-width: 300px;">
                        </div>

                        <div class="mt-4 d-flex justify-content-center gap-2">
                            <a href="{{ $qrDataUri }}" download="qr-{{ $eventner->slug ?? 'event' }}.png" class="btn btn-primary">
                                <i class="ti ti-download"></i> Download QR
                            </a>
                            <button class="btn btn-light" onclick="window.print()">
                                <i class="ti ti-printer"></i> Cetak
                            </button>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded-3 text-start">
                            <label class="text-muted small fw-semibold mb-1">URL:</label>
                            <code class="d-block text-break">{{ $eventner->publicUrl('detail') }}</code>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="ti ti-square-rounded-x fs-9 d-block mb-2"></i>
                            Gagal generate QR. Periksa konfigurasi logo aplikasi.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
