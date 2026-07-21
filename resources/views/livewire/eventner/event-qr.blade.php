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
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm overflow-hidden">
                @php
                    $tc = $eventner->theme_config ?? [];
                    $pc = $tc['primary_color'] ?? '#0062ff';
                    $ac = $tc['accent_color'] ?? '#7c3aed';
                @endphp

                {{-- Premium header gradient --}}
                <div class="py-4 text-center position-relative" style="background: linear-gradient(135deg, {{ $pc }} 0%, {{ $ac }} 100%);">
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10" style="background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.3) 0%, transparent 60%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.2) 0%, transparent 50%);"></div>
                    <div class="position-relative">
                        {{-- BARIS APP logo + teks --}}
                        <div class="d-inline-flex align-items-center gap-2 bg-white/15 rounded-pill px-4 py-1.5 backdrop-blur-sm border border-white/10 mb-2">
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}"
                                 alt=""
                                 class="rounded-circle"
                                 style="width: 22px; height: 22px; object-fit: cover;"
                                 onerror="this.style.display='none'">
                            <span class="text-white fw-bold" style="font-size: 13px; letter-spacing: 0.3px;">BARIS APP</span>
                            <span class="text-white/50">|</span>
                            <span class="text-white/90 small fw-medium">{{ $eventner->nama_event }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                            <span class="badge bg-white/20 text-white border-0 fs-2 px-3 py-1 rounded-pill">
                                <i class="ti ti-qrcode me-1"></i> QR Event
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body text-center py-5 px-4">
                    <h5 class="fw-bold mb-1">{{ $eventner->nama_event }}</h5>
                    <p class="text-muted small mb-4">
                        Scan QR untuk buka halaman publik event
                    </p>

                    @if($qrDataUri)
                        {{-- QR Card with decorative frame --}}
                        <div class="d-inline-block p-3 rounded-4 position-relative"
                             style="background: linear-gradient(135deg, {{ $pc }}11 0%, {{ $ac }}11 50%, #fff7ed 100%);">
                            {{-- Decorative corner dots --}}
                            <div class="position-absolute" style="top: 8px; left: 8px; width: 12px; height: 12px; border-top: 3px solid {{ $pc }}; border-left: 3px solid {{ $pc }}; border-radius: 4px 0 0 0;"></div>
                            <div class="position-absolute" style="top: 8px; right: 8px; width: 12px; height: 12px; border-top: 3px solid {{ $ac }}; border-right: 3px solid {{ $ac }}; border-radius: 0 4px 0 0;"></div>
                            <div class="position-absolute" style="bottom: 8px; left: 8px; width: 12px; height: 12px; border-bottom: 3px solid {{ $ac }}; border-left: 3px solid {{ $ac }}; border-radius: 0 0 0 4px;"></div>
                            <div class="position-absolute" style="bottom: 8px; right: 8px; width: 12px; height: 12px; border-bottom: 3px solid {{ $pc }}; border-right: 3px solid {{ $pc }}; border-radius: 0 0 4px 0;"></div>

                            <div class="bg-white p-3 rounded-3 shadow-sm">
                                <img src="{{ $qrDataUri }}" alt="QR {{ $eventner->nama_event }}" class="img-fluid d-block mx-auto" style="max-width: 260px;">
                            </div>

                            {{-- Event name below QR --}}
                            <div class="mt-3 text-center">
                                <div class="d-inline-flex align-items-center gap-2 bg-white/80 rounded-pill px-3 py-1 shadow-sm">
                                    @if($eventner->logo_event)
                                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="rounded-circle" style="width: 20px; height: 20px; object-fit: cover;">
                                    @else
                                        <i class="ti ti-calendar-event" style="color: {{ $pc }};"></i>
                                    @endif
                                    <span class="small fw-semibold text-dark">{{ $eventner->nama_event }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Action buttons row --}}
                        <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ $qrDataUri }}" download="qr-{{ $eventner->slug ?? 'event' }}.png"
                               class="btn d-inline-flex align-items-center gap-2 px-4"
                               style="background: {{ $pc }}; border-color: {{ $pc }}; color: #fff;">
                                <i class="ti ti-download"></i> Download QR
                            </a>
                            <button class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4" onclick="window.print()">
                                <i class="ti ti-printer"></i> Cetak
                            </button>
                            <button class="btn d-inline-flex align-items-center gap-2 px-4"
                                    style="border: 1px solid {{ $pc }}; color: {{ $pc }}; background: transparent;"
                                    onclick="copyUrl()">
                                <i class="ti ti-copy"></i> Salin Link
                            </button>
                        </div>

                        {{-- URL Info --}}
                        <div class="mt-4 p-3 bg-light rounded-3 text-start mx-auto" style="max-width: 400px;">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="ti ti-link" style="color: {{ $pc }};"></i>
                                <label class="small fw-semibold text-muted mb-0">URL Event:</label>
                            </div>
                            <code class="d-block text-break small bg-white p-2 rounded border">{{ $eventner->publicUrl('detail') }}</code>
                        </div>

                        {{-- Powered by --}}
                        <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-center gap-2 text-muted">
                            <span class="small">Powered by</span>
                            <img src="{{ asset('templates/assets/images/logos/light-logo.svg') }}"
                                 alt="BARIS APP" style="height: 18px; filter: brightness(0) saturate(100%) invert(40%) sepia(0%) saturate(0%) hue-rotate(0deg);">
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

@push('scripts')
<script>
function copyUrl() {
    navigator.clipboard.writeText('{{ $eventner->publicUrl('detail') }}').then(function() {
        var btn = event.target.closest('button');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i> Tersalin';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
}
</script>
@endpush
