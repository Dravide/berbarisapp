<div>
    {{-- Hero / Banner Section --}}
    <div class="section zubuz-hero-section" style="background-image: url({{ asset('templates/zubaz/assets/images/v1/hero-shape1.png') }})">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="zubuz-default-content wow fadeInLeft">
                        @if($eventner->tingkat_perlombaan)
                        <span class="font-semibold" style="display:inline-block; background: rgba(0,114,255,0.1); color: #0072FF; padding: 4px 16px; border-radius: 20px; font-size: 13px; margin-bottom: 12px;">{{ $eventner->tingkat_perlombaan }}</span>
                        @endif
                        <h1 style="font-size: 42px; line-height: 1.2;">{{ $eventner->nama_event }}</h1>
                        <p style="font-size: 17px; color: #6b7280; margin-bottom: 20px;">
                            Penyelenggara: <strong style="color: #111827;">{{ $eventner->diselenggarakan_oleh }}</strong>
                        </p>

                        {{-- Date badges --}}
                        <div class="d-flex flex-wrap gap-3 mb-4">
                            @if($eventner->tanggal_pendaftaran)
                            <div style="background: #fff; padding: 10px 20px; border-radius: 10px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px;">
                                <i class="fa fa-calendar" style="color: #0072FF;"></i>
                                <div>
                                    <span style="font-size: 11px; color: #9ca3af; display: block;">Batas Pendaftaran</span>
                                    <strong style="font-size: 14px;">{{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d F Y') }}</strong>
                                </div>
                            </div>
                            @endif

                            @if($eventner->tanggal)
                            <div style="background: #fff; padding: 10px 20px; border-radius: 10px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px;">
                                <i class="fa fa-flag" style="color: #ef4444;"></i>
                                <div>
                                    <span style="font-size: 11px; color: #9ca3af; display: block;">Hari Perlombaan</span>
                                    <strong style="font-size: 14px;">{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</strong>
                                </div>
                            </div>
                            @endif

                            @if($eventner->venue)
                            <div style="background: #fff; padding: 10px 20px; border-radius: 10px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px;">
                                <i class="fa fa-map-marker-alt" style="color: #10b981;"></i>
                                <div>
                                    <span style="font-size: 11px; color: #9ca3af; display: block;">Venue</span>
                                    <strong style="font-size: 14px;">{{ $eventner->venue }}</strong>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- CTA Buttons --}}
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('event.participant', $eventner->slug) }}" class="zubuz-default-btn">
                                <span><i class="fa fa-users"></i> Lihat Peserta</span>
                            </a>
                            <a href="{{ route('event.vote', $eventner->slug) }}" class="zubuz-default-btn" style="background: #f59e0b;">
                                <span><i class="fa fa-heart"></i> Vote Online</span>
                            </a>
                            @if($eventner->ticket_active && $eventner->ticket_price)
                            <a href="{{ route('event.ticket', $eventner->slug) }}" class="zubuz-default-btn" style="background: #10b981;">
                                <span><i class="fa fa-ticket"></i> Beli Tiket</span>
                            </a>
                            @endif
                            <a href="{{ route('event.register', $eventner->slug) }}" class="zubuz-default-btn" style="background: #111827;">
                                <span><i class="fa fa-clipboard"></i> Booking Pendaftaran</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="zubuz-thumb thumb-pr wow fadeInRight">
                        @if($eventner->logo_event)
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" style="border-radius: 20px; max-height: 400px; object-fit: contain;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/v1/mocup01.png') }}" alt="">
                        @endif
                        <div class="zubuz-thumb-card">
                            @if($eventner->tingkat_perlombaan)
                            <div style="background: #fff; padding: 12px 16px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                                <span style="font-size: 11px; color: #9ca3af;">Tingkat</span>
                                <strong style="display: block; color: #0072FF;">{{ $eventner->tingkat_perlombaan }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- About / Description Section --}}
    @if($eventner->deskripsi)
    <div class="section zubuz-section-padding2">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Tentang Acara</h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="zubuz-default-content center wow fadeInUp">
                        <p style="white-space: pre-line; font-size: 16px; line-height: 1.8; color: #4b5563;">{{ $eventner->deskripsi }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Info Grid Section --}}
    <div class="section zubuz-section-padding3 bg-light">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Informasi Lengkap</h2>
            </div>
            <div class="row">
                {{-- Location --}}
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon3.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Lokasi</h3>
                            <p>{{ $eventner->lokasi }}</p>
                            @if($eventner->latitude && $eventner->longitude)
                                <a href="https://www.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}" target="_blank" style="color: #0072FF; font-weight: 600; font-size: 14px;">
                                    Lihat di Maps <i class="fa fa-external-link-alt" style="font-size: 11px;"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Venue --}}
                @if($eventner->venue)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon4.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Venue</h3>
                            <p>{{ $eventner->venue }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Technical Meeting --}}
                @if($eventner->technical_meeting)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon5.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Technical Meeting</h3>
                            <p>{{ \Carbon\Carbon::parse($eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Registration Deadline --}}
                @if($eventner->tanggal_pendaftaran)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon6.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Batas Pendaftaran</h3>
                            <p>{{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Event Date --}}
                @if($eventner->tanggal)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon7.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Hari Perlombaan</h3>
                            <p>{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Level --}}
                @if($eventner->tingkat_perlombaan)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon8.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Tingkat Perlombaan</h3>
                            <p>{{ $eventner->tingkat_perlombaan }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Map Embed --}}
            @if($eventner->latitude && $eventner->longitude)
            <div class="row justify-content-center mt-4">
                <div class="col-lg-10 wow fadeInUp">
                    <div style="border-radius: 16px; overflow: hidden; border: 1px solid #e5e7eb;">
                        <iframe
                            width="100%"
                            height="300"
                            style="border:0"
                            loading="lazy"
                            allowfullscreen
                            src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
                        </iframe>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Categories Section --}}
    @if($eventner->competitionCategories->count() > 0)
    <div class="section zubuz-section-padding3">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Kategori Lomba</h2>
            </div>
            <div class="row">
                @foreach($eventner->competitionCategories as $cat)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon3.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>{{ $cat->name }}</h3>
                            <p>{{ $cat->registrations->count() }} peserta terdaftar</p>
                            @if($cat->tanggal_pelaksanaan)
                            <span style="font-size: 13px; color: #0072FF; font-weight: 600;">
                                <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Social Media & Links Section --}}
    <div class="section dark-bg zubuz-section-padding4">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2 style="color: #fff;">Hubungi Penyelenggara</h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        @if($eventner->link_whatsapp)
                        <a href="{{ Str::startsWith($eventner->link_whatsapp, ['http://', 'https://']) ? $eventner->link_whatsapp : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="zubuz-default-btn" style="background: #25D366;">
                            <span><i class="fab fa-whatsapp"></i> WhatsApp</span>
                        </a>
                        @endif

                        @if($eventner->link_instagram)
                        <a href="{{ $eventner->link_instagram }}" target="_blank" class="zubuz-default-btn" style="background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);">
                            <span><i class="fab fa-instagram"></i> Instagram</span>
                        </a>
                        @endif

                        @if($eventner->link_tiktok)
                        <a href="{{ $eventner->link_tiktok }}" target="_blank" class="zubuz-default-btn" style="background: #111827;">
                            <span><i class="fab fa-tiktok"></i> TikTok</span>
                        </a>
                        @endif

                        @if($eventner->link_livestreaming)
                        <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="zubuz-default-btn" style="background: #ef4444;">
                            <span><i class="fa fa-video"></i> Live Streaming</span>
                        </a>
                        @endif

                        @if(!$eventner->link_whatsapp && !$eventner->link_instagram && !$eventner->link_tiktok && !$eventner->link_livestreaming)
                        <p style="color: rgba(255,255,255,0.5);">Belum ada tautan yang ditambahkan penyelenggara.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA Register Section --}}
    <div class="zubuz-cta-section blue-bg">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 order-lg-2 d-flex align-items-center">
                    <div class="zubuz-default-content light">
                        <h2>Siap Bergabung?</h2>
                        <p>Daftarkan kontingen Anda sekarang dan jadilah bagian dari kompetisi ini!</p>
                        <div class="zubuz-extara-mt">
                            <a class="zubuz-default-btn" href="{{ route('event.register', $eventner->slug) }}" style="background: #fff; color: #0072FF;">
                                <span><i class="fa fa-clipboard"></i> Booking Pendaftaran</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="zubuz-cta-thumb">
                        @if($eventner->poster)
                            <img src="{{ asset('storage/' . $eventner->poster) }}" alt="{{ $eventner->nama_event }}" style="border-radius: 16px; max-height: 350px; object-fit: cover;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/v1/cta-mocup.png') }}" alt="">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
