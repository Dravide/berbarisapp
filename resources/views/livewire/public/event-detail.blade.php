<div class="premium-event-page">

    {{-- ========== HERO ========== --}}
    <div class="pm-hero">
        <div class="pm-hero-content">
            @if($eventner->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="pm-hero-logo mb-3" alt="{{ $eventner->nama_event }}">
            @endif

            @if($eventner->tingkat_perlombaan)
                <div class="pm-event-badge">
                    <i class="fa fa-medal"></i>
                    {{ $eventner->tingkat_perlombaan }}
                </div>
            @endif

            <h1 class="pm-event-title">{{ $eventner->nama_event }}</h1>
            <p class="pm-event-org">{{ $eventner->diselenggarakan_oleh }}</p>

            <div class="pm-date-chips">
                @if($eventner->tanggal_pendaftaran)
                    <div class="pm-chip">
                        <i class="fa fa-calendar-check-o"></i>
                        <span>Pendaftaran:</span>
                        <strong>{{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d M Y') }}</strong>
                    </div>
                @endif
                @if($eventner->tanggal)
                    <div class="pm-chip">
                        <i class="fa fa-flag-checkered"></i>
                        <strong>{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M Y') }}</strong>
                    </div>
                @endif
                @if($eventner->venue)
                    <div class="pm-chip">
                        <i class="fa fa-map-marker-alt"></i>
                        <strong>{{ $eventner->venue }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========== TENTANG ========== --}}
    @if($eventner->deskripsi)
    <div class="pm-section">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-info-circle"></i></div>
                <h3>Tentang Acara</h3>
            </div>
            <div class="pm-card-body">
                <p style="white-space: pre-line; font-size: 14px; line-height: 1.8; color: var(--pm-text-secondary);">{{ $eventner->deskripsi }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ========== STATS ========== --}}
    @php
        $totalReg = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
        $totalVerified = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'Terverifikasi')->count());
    @endphp
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-stats-grid">
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: var(--pm-primary);">{{ $eventner->competitionCategories->count() }}</div>
                <div class="pm-stat-label">Kategori</div>
            </div>
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: #10b981;">{{ $totalReg }}</div>
                <div class="pm-stat-label">Kontingen</div>
            </div>
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: var(--pm-primary);">{{ $totalVerified }}</div>
                <div class="pm-stat-label">Terverifikasi</div>
            </div>
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: #f59e0b;">
                    {{ $eventner->competitionCategories->sum(fn($c) => $c->registrations->sum(fn($r) => $r->participants->count())) }}
                </div>
                <div class="pm-stat-label">Peserta</div>
            </div>
        </div>
    </div>

    {{-- ========== INFO ========== --}}
    @if($eventner->lokasi || $eventner->technical_meeting || $eventner->tingkat_perlombaan)
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-list-alt"></i></div>
                <h3>Informasi</h3>
            </div>
            <div class="pm-card-body">
                <div class="pm-info-list">
                    @if($eventner->lokasi)
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><i class="fa fa-map-marker-alt"></i></div>
                        <div class="pm-info-content">
                            <div class="pm-info-label">Lokasi</div>
                            <div class="pm-info-value">{{ $eventner->lokasi }}</div>
                            @if($eventner->latitude && $eventner->longitude)
                                <a href="https://www.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}" target="_blank">Lihat di Maps →</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($eventner->technical_meeting)
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><i class="fa fa-users"></i></div>
                        <div class="pm-info-content">
                            <div class="pm-info-label">Technical Meeting</div>
                            <div class="pm-info-value">{{ \Carbon\Carbon::parse($eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB</div>
                        </div>
                    </div>
                    @endif

                    @if($eventner->tingkat_perlombaan)
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><i class="fa fa-trophy"></i></div>
                        <div class="pm-info-content">
                            <div class="pm-info-label">Tingkat</div>
                            <div class="pm-info-value">{{ $eventner->tingkat_perlombaan }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($eventner->latitude && $eventner->longitude)
        <div class="pm-map mt-3">
            <iframe
                width="100%"
                height="200"
                style="border:0"
                loading="lazy"
                allowfullscreen
                src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
            </iframe>
        </div>
        @endif
    </div>
    @endif

    {{-- ========== KATEGORI LOMBA ========== --}}
    @if($eventner->competitionCategories->count() > 0)
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-tags"></i></div>
                <h3>Kategori Lomba</h3>
            </div>
            @foreach($eventner->competitionCategories as $cat)
            <div class="pm-category-card">
                <div class="pm-cat-icon">
                    <i class="fa fa-trophy"></i>
                </div>
                <div class="pm-cat-info">
                    <div class="pm-cat-name">{{ $cat->name }}</div>
                    <div class="pm-cat-meta">
                        {{ $cat->registrations->count() }} kontingen
                        @if($cat->tanggal_pelaksanaan)
                            · {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
                        @endif
                    </div>
                    @php
                        $topReg = $cat->registrations->sortByDesc('total_votes')->first();
                    @endphp
                    @if($topReg && $topReg->total_votes > 0)
                    <div class="pm-top-vote">
                        <div class="pm-top-vote-label">Peringkat 1 Vote</div>
                        <div class="pm-top-vote-school">
                            @if($topReg->logo_sekolah)
                                <img src="{{ asset('storage/' . $topReg->logo_sekolah) }}" class="pm-top-vote-logo" alt="" loading="lazy">
                            @endif
                            <span class="pm-top-vote-name">{{ $topReg->nama_sekolah }}</span>
                            <span class="pm-vote-badge">
                                <i class="fa fa-heart"></i> {{ number_format($topReg->total_votes, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
                <i class="fa fa-chevron-right pm-cat-arrow"></i>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ========== DEWAN JURI ========== --}}
    @if($eventner->judges->count() > 0)
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-user-tie"></i></div>
                <h3>Dewan Juri</h3>
            </div>
            <div class="pm-judge-grid">
                @foreach($eventner->judges as $judge)
                <div class="pm-judge-card">
                    <div class="pm-judge-avatar">{{ strtoupper(substr($judge->name, 0, 1)) }}</div>
                    <div class="pm-judge-name">{{ $judge->name }}</div>
                    <div class="pm-judge-tags">
                        @foreach($judge->assessmentCategories as $cat)
                            <span class="pm-judge-tag">{{ $cat->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ========== SPONSOR ========== --}}
    @if($eventner->sponsors->count() > 0)
    @php
        $sGrouped = $eventner->sponsors->groupBy('type');
        $typeOrders = ['sponsor', 'gold', 'silver', 'bronze', 'medpart', 'partner', 'supporting'];
        $typeLabels = [
            'sponsor' => 'Sponsor Utama',
            'gold' => 'Sponsor Gold',
            'silver' => 'Sponsor Silver',
            'bronze' => 'Sponsor Bronze',
            'medpart' => 'Media Partner',
            'partner' => 'Event Partner',
            'supporting' => 'Supporting'
        ];
    @endphp
    <div class="pm-section" style="padding-top: 0;">
        @foreach($typeOrders as $t)
            @if(isset($sGrouped[$t]) && $sGrouped[$t]->count() > 0)
            <div class="pm-card">
                <div class="pm-card-header">
                    <div class="pm-card-header-icon"><i class="fa fa-handshake-o"></i></div>
                    <h3>{{ $typeLabels[$t] ?? $t }}</h3>
                </div>
                <div class="pm-sponsor-grid">
                    @foreach($sGrouped[$t] as $sponsor)
                        @if($sponsor->link)
                            <a href="{{ $sponsor->link }}" target="_blank">
                        @endif
                        @if($sponsor->logo)
                            <img src="{{ asset('storage/' . $sponsor->logo) }}" class="pm-sponsor-logo" alt="{{ $sponsor->name }}" loading="lazy">
                        @else
                            <div class="pm-sponsor-name">{{ $sponsor->name }}</div>
                        @endif
                        @if($sponsor->link)
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- ========== TENANT / BAZAAR ========== --}}
    @if($eventner->tenants->count() > 0)
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-store"></i></div>
                <h3>Tenant & Bazaar</h3>
            </div>
            <div class="pm-tenant-grid">
                @foreach($eventner->tenants as $tenant)
                <div class="pm-tenant-card">
                    @if($tenant->logo)
                        <img src="{{ asset('storage/' . $tenant->logo) }}" class="pm-tenant-logo" alt="{{ $tenant->name }}" loading="lazy">
                    @endif
                    <div class="pm-tenant-name">{{ $tenant->name }}</div>
                    <div class="pm-tenant-type">
                        @if($tenant->type === 'culinary') Kuliner
                        @elseif($tenant->type === 'beverage') Minuman
                        @elseif($tenant->type === 'souvenir') Souvenir
                        @elseif($tenant->type === 'bazaar') Bazaar
                        @else Lainnya @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ========== HUBUNGI PENYELENGGARA ========== --}}
    @if($eventner->link_whatsapp || $eventner->link_instagram || $eventner->link_tiktok || $eventner->link_livestreaming)
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-share-alt"></i></div>
                <h3>Hubungi Penyelenggara</h3>
            </div>
            <div class="pm-social-grid">
                @if($eventner->link_whatsapp)
                    <a href="{{ Str::startsWith($eventner->link_whatsapp, ['http://', 'https://']) ? $eventner->link_whatsapp : 'https://wa.me/' . preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="pm-social-btn whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                @endif
                @if($eventner->link_instagram)
                    <a href="{{ $eventner->link_instagram }}" target="_blank" class="pm-social-btn instagram">
                        <i class="fab fa-instagram"></i> Instagram
                    </a>
                @endif
                @if($eventner->link_tiktok)
                    <a href="{{ $eventner->link_tiktok }}" target="_blank" class="pm-social-btn tiktok">
                        <i class="fab fa-tiktok"></i> TikTok
                    </a>
                @endif
                @if($eventner->link_livestreaming)
                    <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="pm-social-btn live">
                        <i class="fa fa-video-camera"></i> Live
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ========== FLOATING REGISTER CTA ========== --}}
    @if(($eventner->registration_status ?? 'open') != 'closed')
    <div class="pm-fab">
        <a href="{{ route('event.register', $eventner->slug) }}" class="pm-btn pm-btn-primary">
            <i class="fa fa-clipboard"></i>
            {{ ($eventner->registration_status ?? 'open') == 'booking' ? 'Booking Slot Sekarang' : 'Daftar Sekarang' }}
        </a>
    </div>
    @endif

    {{-- ========== BOTTOM NAVIGATION ========== --}}
    <nav class="pm-bottom-nav">
        <a href="{{ route('event.detail', $eventner->slug) }}" class="pm-nav-item {{ request()->routeIs('event.detail') ? 'active' : '' }}">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('event.participant', $eventner->slug) }}" class="pm-nav-item {{ request()->routeIs('event.participant') ? 'active' : '' }}">
            <i class="fa fa-users"></i>
            <span>Peserta</span>
        </a>
        @if($eventner->vote_active)
        <a href="{{ route('event.vote', $eventner->slug) }}" class="pm-nav-item {{ request()->routeIs('event.vote') ? 'active' : '' }}">
            <i class="fa fa-heart"></i>
            <span>Vote</span>
        </a>
        @endif
        @if($eventner->ticket_active && $eventner->ticket_price)
        <a href="{{ route('event.ticket', $eventner->slug) }}" class="pm-nav-item {{ request()->routeIs('event.ticket') ? 'active' : '' }}">
            <i class="fa fa-ticket"></i>
            <span>Tiket</span>
        </a>
        @endif
        @if(($eventner->registration_status ?? 'open') != 'closed')
        <a href="{{ route('event.register', $eventner->slug) }}" class="pm-nav-item {{ request()->routeIs('event.register') ? 'active' : '' }}" style="color: var(--pm-primary);">
            <i class="fa fa-edit"></i>
            <span>Daftar</span>
        </a>
        @endif
    </nav>

</div>
