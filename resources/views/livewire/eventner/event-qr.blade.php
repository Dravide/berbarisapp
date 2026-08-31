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
        @foreach($qrs as $key => $qr)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-semibold mb-0">
                            <i class="{{ $icons[$key] }} me-2 text-primary"></i>{{ $qr['label'] }}
                        </h5>
                        <a href="{{ $qr['url'] }}" target="_blank" class="btn btn-sm btn-light">
                            <i class="ti ti-external-link"></i>
                        </a>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted small mb-4">Scan QR untuk buka {{ strtolower($qr['label']) }}</p>

                        @if($qr['dataUri'])
                            <div class="d-inline-block p-2 rounded-3 border bg-light">
                                <div class="bg-white p-2 rounded-2 border">
                                    <img src="{{ $qr['dataUri'] }}" alt="QR {{ $qr['label'] }} {{ $eventner->nama_event }}" class="img-fluid d-block mx-auto" style="max-width: 200px;">
                                </div>
                            </div>

                            {{-- URL Info --}}
                            <div class="mt-3 p-3 bg-light rounded-3 text-start">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="ti ti-link text-primary"></i>
                                    <label class="small fw-semibold text-muted mb-0">URL:</label>
                                </div>
                                <code class="d-block text-break small bg-white p-2 rounded border">{{ $qr['url'] }}</code>
                            </div>

                            {{-- Action buttons row --}}
                            <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                                <a href="{{ $qr['dataUri'] }}" download="qr-{{ $key }}-{{ $eventner->slug ?? 'event' }}.png"
                                   class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                                    <i class="ti ti-download"></i> Download
                                </a>
                                <button class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" onclick="window.print()">
                                    <i class="ti ti-printer"></i> Cetak
                                </button>
                                <button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2" onclick="copyUrl(this, '{{ $qr['url'] }}')">
                                    <i class="ti ti-copy"></i> Salin Link
                                </button>
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
        @endforeach
    </div>
</div>

@push('scripts')
<script>
function copyUrl(btn, url) {
    navigator.clipboard.writeText(url).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i> Tersalin';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
}
</script>
@endpush
