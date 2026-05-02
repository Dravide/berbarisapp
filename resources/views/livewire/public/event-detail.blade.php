<div>
    {{-- Banner / Hero Section --}}
    <section class="bg-primary-subtle pt-7 py-lg-10 py-7">
        <div class="custom-container">
            <div class="row align-items-center">
                <div class="col-lg-6 text-center text-lg-start mb-5 mb-lg-0">
                    <span class="badge bg-primary text-white mb-3 fs-3 px-3 py-2">Informasi Event</span>
                    <h1 class="text-dark fw-semibold fs-13 mb-3">
                        {{ $eventner->nama_event }}
                    </h1>
                    <p class="fs-5 text-muted mb-4 pb-2">
                        Penyelenggara: <strong>{{ $eventner->diselenggarakan_oleh }}</strong>
                    </p>
                    
                    <div class="d-flex align-items-center justify-content-center justify-content-lg-start gap-3 flex-wrap">
                        @if($eventner->tanggal_pendaftaran)
                        <div class="bg-white px-4 py-2 d-flex align-items-center gap-2 border">
                            <i class="ti ti-calendar-event text-primary fs-6"></i>
                            <span class="fw-semibold text-dark">Daftar: {{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d F Y') }}</span>
                        </div>
                        @endif
                        
                        @if($eventner->tanggal)
                        <div class="bg-white px-4 py-2 d-flex align-items-center gap-2 border">
                            <i class="ti ti-flag text-danger fs-6"></i>
                            <span class="fw-semibold text-dark">Lomba: {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                        <a href="{{ route('event.participant', $eventner->slug) }}" class="btn btn-primary px-4">
                            <i class="ti ti-list-check me-1"></i> Lihat Daftar Peserta
                        </a>
                        <a href="{{ route('event.vote', $eventner->slug) }}" class="btn btn-warning px-4 text-white">
                            <i class="ti ti-heart me-1"></i> Vote Online
                        </a>
                        @if($eventner->ticket_active && $eventner->ticket_price)
                            <a href="{{ route('event.ticket', $eventner->slug) }}" class="btn btn-success px-4">
                                <i class="ti ti-ticket me-1"></i> Beli Tiket
                            </a>
                        @endif
                        <a href="{{ route('event.register', $eventner->slug) }}" class="btn btn-dark px-4">
                            <i class="ti ti-clipboard-plus me-1"></i> Booking Pendaftaran
                        </a>
                    </div>
                </div>

                <div class="col-lg-6 text-center">
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="img-fluid border border-3 border-white" style="max-height: 400px; object-fit: cover;" alt="Logo {{ $eventner->nama_event }}">
                    @else
                        <img src="{{ asset('templates/assets/images/frontend-pages/banner-top-right.svg') }}" class="img-fluid" alt="banner-top-right">
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Detail Section --}}
    <section class="py-5 py-md-10">
        <div class="custom-container">
            <div class="row">
                {{-- Main Info --}}
                <div class="col-lg-8 mb-5 mb-lg-0">
                    @if($eventner->deskripsi)
                    <div class="card w-100 mb-4">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="fs-7 fw-semibold text-dark mb-3 border-bottom pb-3">Tentang Acara</h3>
                            <p class="fs-4 text-dark mb-0 lh-lg" style="white-space: pre-line;">{{ $eventner->deskripsi }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="card w-100 h-100">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="fs-7 fw-semibold text-dark mb-4 border-bottom pb-3">Informasi Lengkap Acara</h3>
                            
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-4">
                                <li class="d-flex align-items-start gap-3">
                                    <div class="bg-primary-subtle rounded-circle p-3 text-primary d-flex align-items-center justify-content-center">
                                        <i class="ti ti-map-pin fs-6"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fs-5 fw-semibold mb-1">Lokasi</h5>
                                        <p class="mb-2 fs-4 text-muted">{{ $eventner->lokasi }}</p>
                                        
                                        @if($eventner->latitude && $eventner->longitude)
                                            <div class="mt-3 overflow-hidden border">
                                                <iframe 
                                                    width="100%" 
                                                    height="250" 
                                                    style="border:0" 
                                                    loading="lazy" 
                                                    allowfullscreen 
                                                    src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
                                                </iframe>
                                            </div>
                                        @endif
                                    </div>
                                </li>

                                @if($eventner->venue)
                                <li class="d-flex align-items-start gap-3">
                                    <div class="bg-warning-subtle rounded-circle p-3 text-warning d-flex align-items-center justify-content-center">
                                        <i class="ti ti-building-monument fs-6"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-5 fw-semibold mb-1">Venue Pelaksanaan</h5>
                                        <p class="mb-0 fs-4 text-muted">{{ $eventner->venue }}</p>
                                    </div>
                                </li>
                                @endif

                                @if($eventner->technical_meeting)
                                <li class="d-flex align-items-start gap-3">
                                    <div class="bg-success-subtle rounded-circle p-3 text-success d-flex align-items-center justify-content-center">
                                        <i class="ti ti-clock fs-6"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-5 fw-semibold mb-1">Technical Meeting</h5>
                                        <p class="mb-0 fs-4 text-muted">{{ \Carbon\Carbon::parse($eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB</p>
                                    </div>
                                </li>
                                @endif

                                @if($eventner->tingkat_perlombaan)
                                <li class="d-flex align-items-start gap-3">
                                    <div class="bg-info-subtle rounded-circle p-3 text-info d-flex align-items-center justify-content-center">
                                        <i class="ti ti-trophy fs-6"></i>
                                    </div>
                                    <div>
                                        <h5 class="fs-5 fw-semibold mb-1">Radius Peserta</h5>
                                        <p class="mb-0 fs-4 text-muted">{{ $eventner->tingkat_perlombaan }}</p>
                                    </div>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Right Sidebar --}}
                <div class="col-lg-4">
                    <div class="d-flex flex-column gap-4">
                        
                        {{-- Social Media Card --}}
                        <div class="card w-100 mb-0">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-link me-2"></i>Tautan & Narahubung</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="d-flex flex-column gap-3">
                                    @if($eventner->link_whatsapp)
                                    <a href="{{ Str::startsWith($eventner->link_whatsapp, ['http://', 'https://']) ? $eventner->link_whatsapp : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="btn btn-outline-success d-flex align-items-center gap-2 w-100 justify-content-center">
                                        <i class="ti ti-brand-whatsapp fs-6"></i> Hubungi WhatsApp
                                    </a>
                                    @endif

                                    @if($eventner->link_instagram)
                                    <a href="{{ $eventner->link_instagram }}" target="_blank" class="btn btn-outline-danger d-flex align-items-center gap-2 w-100 justify-content-center">
                                        <i class="ti ti-brand-instagram fs-6"></i> Kunjungi Instagram
                                    </a>
                                    @endif

                                    @if($eventner->link_tiktok)
                                    <a href="{{ $eventner->link_tiktok }}" target="_blank" class="btn btn-outline-dark d-flex align-items-center gap-2 w-100 justify-content-center">
                                        <i class="ti ti-brand-tiktok fs-6"></i> Lihat TikTok
                                    </a>
                                    @endif

                                    @if($eventner->link_livestreaming)
                                    <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="btn btn-danger d-flex align-items-center gap-2 w-100 justify-content-center mt-2">
                                        <i class="ti ti-video fs-6"></i> Tonton Live Streaming
                                    </a>
                                    @endif
                                    
                                    @if(!$eventner->link_whatsapp && !$eventner->link_instagram && !$eventner->link_tiktok && !$eventner->link_livestreaming)
                                        <p class="mb-0 text-muted text-center">Belum ada tautan yang ditambahkan penyelenggara.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Categories Card --}}
                        <div class="card w-100 mb-0">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-medal me-2"></i>Tingkat Perlombaan</h5>
                            </div>
                            <div class="card-body p-4">
                                @if($eventner->competitionCategories->count() > 0)
                                    <ul class="list-group list-group-flush">
                                        @foreach($eventner->competitionCategories as $cat)
                                            <li class="list-group-item px-0 d-flex align-items-center gap-3">
                                                <i class="ti ti-medal text-warning fs-5"></i>
                                                <span class="fs-4 text-dark fw-semibold">{{ $cat->name }}</span>
                                                <span class="badge bg-primary-subtle text-primary ms-auto">{{ $cat->registrations->count() }} peserta</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="text-center py-3">
                                        <p class="text-muted mb-0">Tingkat perlombaan belum diatur.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
