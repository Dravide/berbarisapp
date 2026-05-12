<div>
    {{-- Hero / Banner Section --}}
    <div style="background: linear-gradient(135deg, var(--event-primary, #0072FF) 0%, var(--event-accent, #00D4AA) 100%); padding: 140px 0 40px; position: relative; overflow: hidden;">
        @if($eventner->poster)
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('{{ asset('storage/' . $eventner->poster) }}') center/cover no-repeat; opacity: 0.1;"></div>
        @endif
        <div style="position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
        <div style="position: absolute; bottom: -30%; left: -10%; width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="wow fadeInLeft">
                        @if($eventner->tingkat_perlombaan)
                        <span style="display:inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">{{ $eventner->tingkat_perlombaan }}</span>
                        @endif
                        <h1 style="color: #fff; font-size: clamp(28px, 5vw, 42px); line-height: 1.2; word-wrap: break-word; margin-bottom: 12px;">{{ $eventner->nama_event }}</h1>
                        <p style="font-size: 17px; color: rgba(255,255,255,0.9); margin-bottom: 20px;">
                            Penyelenggara: <strong style="color: #fff;">{{ $eventner->diselenggarakan_oleh }}</strong>
                        </p>

                        {{-- Date badges (horizontal scroll on mobile) --}}
                        <div class="d-flex flex-nowrap flex-md-wrap overflow-auto gap-2 mb-3 pb-2 pb-md-0" style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                            @if($eventner->tanggal_pendaftaran)
                            <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px); padding: 10px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 8px; white-space: nowrap; min-width: fit-content; color: #fff;">
                                <i class="fa fa-calendar"></i>
                                <div>
                                    <span style="font-size: 11px; color: rgba(255,255,255,0.7); display: block;">Batas Pendaftaran</span>
                                    <strong style="font-size: 13px;">{{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d M Y') }}</strong>
                                </div>
                            </div>
                            @endif
                            @if($eventner->tanggal)
                            <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px); padding: 10px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 8px; white-space: nowrap; min-width: fit-content; color: #fff;">
                                <i class="fa fa-flag" style="color: #fca5a5;"></i>
                                <div>
                                    <span style="font-size: 11px; color: rgba(255,255,255,0.7); display: block;">Hari Perlombaan</span>
                                    <strong style="font-size: 13px;">{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M Y') }}</strong>
                                </div>
                            </div>
                            @endif
                            @if($eventner->venue)
                            <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px); padding: 10px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 8px; white-space: nowrap; min-width: fit-content; color: #fff;">
                                <i class="fa fa-map-marker-alt" style="color: #6ee7b7;"></i>
                                <div>
                                    <span style="font-size: 11px; color: rgba(255,255,255,0.7); display: block;">Venue</span>
                                    <strong style="font-size: 13px;">{{ $eventner->venue }}</strong>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- CTA Buttons --}}
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @if($eventner->vote_active)
                            <a href="{{ route('event.vote', $eventner->slug) }}" style="background: #f59e0b; color: #fff; border-radius: 30px; padding: 10px 20px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa fa-heart"></i> Vote Online
                            </a>
                            @endif
                            <a href="{{ route('event.participant', $eventner->slug) }}" style="background: #fff; color: var(--event-primary, #0072FF); border-radius: 30px; padding: 10px 20px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa fa-users"></i> Peserta
                            </a>
                            @if($eventner->ticket_active && $eventner->ticket_price)
                            <a href="{{ route('event.ticket', $eventner->slug) }}" style="background: #10b981; color: #fff; border-radius: 30px; padding: 10px 20px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa fa-ticket"></i> Beli Tiket
                            </a>
                            @endif
                            @if(($eventner->registration_status ?? 'open') != 'closed')
                            <a href="{{ route('event.register', $eventner->slug) }}" style="background: #111827; color: #fff; border-radius: 30px; padding: 10px 20px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa fa-clipboard"></i> {{ ($eventner->registration_status ?? 'open') == 'booking' ? 'Booking Slot' : 'Daftar Sekarang' }}
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="wow fadeInRight text-center">
                        @if($eventner->logo_event)
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" style="border-radius: 20px; max-height: 320px; object-fit: contain; max-width: 100%; border: 4px solid rgba(255,255,255,0.2); padding: 8px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/v1/mocup01.png') }}" alt="" style="max-width: 100%;">
                        @endif
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
    <div class="section zubuz-section-padding3" style="background: #f8fafc;">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Informasi Lengkap</h2>
            </div>
            <div class="row g-3">
                @if($eventner->lokasi)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff;">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon3.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Lokasi</h3>
                            <p>{{ $eventner->lokasi }}</p>
                            @if($eventner->latitude && $eventner->longitude)
                                <a href="https://www.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}" target="_blank" style="color: var(--event-primary, #0072FF); font-weight: 600; font-size: 14px;">
                                    Lihat di Maps <i class="fa fa-external-link-alt" style="font-size: 11px;"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($eventner->venue)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff;">
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

                @if($eventner->technical_meeting)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff;">
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

                @if($eventner->tanggal_pendaftaran)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff;">
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

                @if($eventner->tanggal)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff;">
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

                @if($eventner->tingkat_perlombaan)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap" style="border-radius: 16px; border: 1px solid #e5e7eb; background: #fff;">
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
                            height="250"
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

    {{-- Judges Section --}}
    @if($eventner->judges->count() > 0)
    <div class="section zubuz-section-padding3" style="background: #fff;">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Dewan Juri</h2>
                <p>Kenali para pakar yang akan memberikan penilaian profesional pada acara ini.</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach($eventner->judges as $judge)
                <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp">
                    <div style="background: #f8fafc; border-radius: 20px; padding: 24px; text-align: center; border: 1px solid #e2e8f0; height: 100%; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div style="width: 80px; height: 80px; background: var(--event-primary, #0072FF); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 32px; font-weight: 700;">
                            {{ strtoupper(substr($judge->name, 0, 1)) }}
                        </div>
                        <h4 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">{{ $judge->name }}</h4>
                        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 6px;">
                            @foreach($judge->assessmentCategories as $category)
                            <span style="background: #fff; color: #64748b; border: 1px solid #e2e8f0; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                {{ $category->name }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Categories Section --}}
    @if($eventner->competitionCategories->count() > 0)
    <div class="section zubuz-section-padding3">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Kategori Lomba</h2>
            </div>
            <div class="row g-3">
                @foreach($eventner->competitionCategories as $cat)
                <div class="col-xl-4 col-md-6 wow fadeInUp">
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--event-primary, #0072FF)'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.06)';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa fa-trophy" style="color: var(--event-primary, #0072FF); font-size: 18px;"></i>
                            </div>
                            <div style="min-width: 0;">
                                <h5 style="margin: 0 0 4px; font-weight: 600; font-size: 16px;">{{ $cat->name }}</h5>
                                <p style="margin: 0; color: #6b7280; font-size: 14px;">{{ $cat->registrations->count() }} peserta terdaftar</p>
                                @if($cat->tanggal_pelaksanaan)
                                <span style="font-size: 13px; color: var(--event-primary, #0072FF); font-weight: 600;">
                                    <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
                                </span>
                                @endif
                            </div>
                        </div>

                        @php
                            $topRegistration = $cat->registrations->sortByDesc('total_votes')->first();
                        @endphp
                        @if($topRegistration && $topRegistration->total_votes > 0)
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px dashed #e5e7eb;">
                            <p style="font-size: 11px; font-weight: 700; color: #9ca3af; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Vote Tertinggi Sementara</p>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                @if($topRegistration->logo_sekolah)
                                    <img src="{{ asset('storage/' . $topRegistration->logo_sekolah) }}" style="width: 36px; height: 36px; border-radius: 8px; object-fit: contain; border: 1px solid #e5e7eb; padding: 2px;" alt="">
                                @else
                                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border: 1px solid #e5e7eb;">
                                        <i class="fa fa-school" style="color: #9ca3af; font-size: 14px;"></i>
                                    </div>
                                @endif
                                <div style="min-width: 0; flex-grow: 1;">
                                    <p style="margin: 0; font-size: 14px; font-weight: 700; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $topRegistration->nama_sekolah }}">{{ $topRegistration->nama_sekolah }}</p>
                                    <span style="font-size: 13px; font-weight: 700; color: #f59e0b; display: inline-flex; align-items: center; background: rgba(245, 158, 11, 0.1); padding: 2px 8px; border-radius: 12px;">
                                        <i class="fa fa-fire" style="margin-right: 4px;"></i>{{ number_format($topRegistration->total_votes, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif
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
                    <div class="d-flex flex-wrap justify-content-center gap-2">
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
    <div class="zubuz-cta-section" style="background: var(--event-primary, #0072FF);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 order-lg-2 d-flex align-items-center">
                    <div class="zubuz-default-content light">
                        <h2>Siap Bergabung?</h2>
                        <p>Daftarkan kontingen Anda sekarang dan jadilah bagian dari kompetisi ini!</p>
                        <div class="zubuz-extara-mt">
                            <a class="zubuz-default-btn" href="{{ route('event.register', $eventner->slug) }}" style="background: #fff; color: var(--event-primary, #0072FF);">
                                <span><i class="fa fa-clipboard"></i> Booking Pendaftaran</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="zubuz-cta-thumb">
                        @if($eventner->poster)
                            <img src="{{ asset('storage/' . $eventner->poster) }}" alt="{{ $eventner->nama_event }}" style="border-radius: 16px; max-height: 300px; object-fit: cover; width: 100%;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/v1/cta-mocup.png') }}" alt="" style="max-width: 100%;">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
