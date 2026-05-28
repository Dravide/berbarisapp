<div class="premium-event-page" x-data="{ tab: 'info' }" data-theme="{{ ($eventner->theme_config['theme'] ?? 'light') }}">

    {{-- ========== HERO ========== --}}
    <div class="pm-hero">
        <div class="pm-hero-content">
            @if($eventner->logo_event)
                <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="pm-hero-logo mb-3" alt="{{ $eventner->nama_event }}">
            @endif

            @if($eventner->poster)
                <img src="{{ asset('storage/' . $eventner->poster) }}" class="pm-hero-logo mb-3" alt="Poster Event" style="width: 100%; max-height: 120px; object-fit: cover; border-radius: 12px;">
            @endif

            @if($eventner->tingkat_perlombaan)
                <div class="pm-event-badge">
                    <i class="fa fa-medal"></i>
                    {{ $eventner->tingkat_perlombaan }}
                </div>
            @endif

            <h1 class="pm-event-title">{{ $eventner->nama_event }}</h1>
            <p class="pm-event-org">{{ $eventner->diselenggarakan_oleh }}</p>

            {{-- Countdown Timer --}}
            @if($eventner->tanggal && \Carbon\Carbon::parse($eventner->tanggal)->isFuture())
            <div class="mt-3" x-data="countdown('{{ \Carbon\Carbon::parse($eventner->tanggal)->toIso8601String() }}')">
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                    <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 8px 14px; text-align: center; backdrop-filter: blur(4px);">
                        <div style="font-size: 20px; font-weight: 800; color: #fff;" x-text="days"></div>
                        <div style="font-size: 10px; color: rgba(255,255,255,0.8); text-transform: uppercase;">Hari</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 8px 14px; text-align: center; backdrop-filter: blur(4px);">
                        <div style="font-size: 20px; font-weight: 800; color: #fff;" x-text="hours"></div>
                        <div style="font-size: 10px; color: rgba(255,255,255,0.8); text-transform: uppercase;">Jam</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 8px 14px; text-align: center; backdrop-filter: blur(4px);">
                        <div style="font-size: 20px; font-weight: 800; color: #fff;" x-text="minutes"></div>
                        <div style="font-size: 10px; color: rgba(255,255,255,0.8); text-transform: uppercase;">Menit</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 8px 14px; text-align: center; backdrop-filter: blur(4px);">
                        <div style="font-size: 20px; font-weight: 800; color: #fff;" x-text="seconds"></div>
                        <div style="font-size: 10px; color: rgba(255,255,255,0.8); text-transform: uppercase;">Detik</div>
                    </div>
                </div>
                <p style="font-size: 11px; color: rgba(255,255,255,0.7); margin-top: 6px; text-align: center;">Menuju Hari H</p>
            </div>
            @endif

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

    {{-- ========== META INFO CARD (Above Fold) ========== --}}
    @php
        $totalKuota = $eventner->competitionCategories->sum('kuota');
        $totalReg = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
    @endphp
    <div class="pm-section" style="padding-top: 16px;">
        <div class="pm-card">
            <div class="pm-card-body" style="padding: 12px 16px;">
                <div class="pm-info-list">
                    @if($eventner->tanggal)
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><i class="fa fa-calendar"></i></div>
                        <div class="pm-info-content">
                            <div class="pm-info-label">Tanggal Pelaksanaan</div>
                            <div class="pm-info-value">{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</div>
                        </div>
                    </div>
                    @endif
                    @if($eventner->lokasi)
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><i class="fa fa-map-marker-alt"></i></div>
                        <div class="pm-info-content">
                            <div class="pm-info-label">Lokasi</div>
                            <div class="pm-info-value">{{ $eventner->lokasi }}</div>
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
                    @if($totalKuota)
                    <div class="pm-info-item">
                        <div class="pm-info-icon"><i class="fa fa-users"></i></div>
                        <div class="pm-info-content">
                            <div class="pm-info-label">Kuota Total</div>
                            <div class="pm-info-value">{{ $totalReg }} / {{ $totalKuota }} Pasukan</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ========== TAB NAVIGATION ========== --}}
    <div class="pm-section" style="padding: 0 16px; position: sticky; top: 0; z-index: 50; background: var(--pm-bg);">
        <div style="display: flex; gap: 2px; background: var(--pm-surface); border-radius: 12px; padding: 4px; border: 1px solid var(--pm-border);">
            <button @click="tab = 'info'" :class="tab === 'info' ? 'pm-tab-active' : ''" class="pm-tab-btn">
                <i class="fa fa-info-circle"></i> Info
            </button>
            <button @click="tab = 'participants'" :class="tab === 'participants' ? 'pm-tab-active' : ''" class="pm-tab-btn">
                <i class="fa fa-users"></i> Peserta
            </button>
            @if($eventner->vote_active)
            <button @click="tab = 'vote'" :class="tab === 'vote' ? 'pm-tab-active' : ''" class="pm-tab-btn">
                <i class="fa fa-heart"></i> Voting
            </button>
            @endif
            @if($eventner->ticket_active && $eventner->ticket_price)
            <button @click="window.location.href='{{ route('event.ticket', $eventner->slug) }}'" class="pm-tab-btn">
                <i class="fa fa-ticket"></i> Tiket
            </button>
            @endif
        </div>
    </div>

    {{-- ========== TAB CONTENT ========== --}}
    <div x-show="tab === 'info'" x-cloak>

        {{-- ========== QUOTA PROGRESS ========== --}}
        @if($eventner->competitionCategories->count() > 0)
        <div class="pm-section" style="padding-top: 16px;">
            <div class="pm-card">
                <div class="pm-card-header">
                    <div class="pm-card-header-icon"><i class="fa fa-chart-bar"></i></div>
                    <h3>Kuota Kategori</h3>
                </div>
                <div class="pm-card-body" style="padding: 16px 18px;">
                    @foreach($eventner->competitionCategories as $cat)
                    <div style="margin-bottom: 14px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-weight: 600; font-size: 14px;">{{ $cat->name }}</span>
                            <span style="font-size: 13px; color: var(--pm-text-secondary);">{{ $cat->registrations->count() }} / {{ $cat->kuota ?? '∞' }}</span>
                        </div>
                        @if($cat->kuota)
                        <div style="height: 8px; background: var(--pm-bg); border-radius: 4px; overflow: hidden;">
                            @php $percent = min(100, round($cat->registrations->count() / $cat->kuota * 100)); @endphp
                            <div style="height: 100%; width: {{ $percent }}%; background: {{ $percent >= 100 ? '#ef4444' : ($percent >= 80 ? '#f59e0b' : 'var(--pm-primary)') }}; border-radius: 4px; transition: width 0.5s;"></div>
                        </div>
                        @else
                        <div style="height: 8px; background: var(--pm-bg); border-radius: 4px;"></div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ========== TENTANG ========== --}}
        @if($eventner->deskripsi)
        <div class="pm-section" style="padding-top: 0;">
            <div class="pm-card">
                <div class="pm-card-header">
                    <div class="pm-card-header-icon"><i class="fa fa-file-alt"></i></div>
                    <h3>Tentang Acara</h3>
                </div>
                <div class="pm-card-body">
                    <p style="white-space: pre-line; font-size: 14px; line-height: 1.8; color: var(--pm-text-secondary);">{{ $eventner->deskripsi }}</p>
                </div>
            </div>
        </div>
        @endif

        {{-- ========== INFO ========== --}}
        @if($eventner->lokasi || $eventner->technical_meeting)
        <div class="pm-section" style="padding-top: 0;">
            <div class="pm-card">
                <div class="pm-card-header">
                    <div class="pm-card-header-icon"><i class="fa fa-list-alt"></i></div>
                    <h3>Informasi</h3>
                </div>
                <div class="pm-card-body">
                    <div class="pm-info-list">
                        @if($eventner->technical_meeting)
                        <div class="pm-info-item">
                            <div class="pm-info-icon"><i class="fa fa-users"></i></div>
                            <div class="pm-info-content">
                                <div class="pm-info-label">Technical Meeting</div>
                                <div class="pm-info-value">{{ \Carbon\Carbon::parse($eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB</div>
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
                    height="180"
                    style="border:0"
                    loading="lazy"
                    allowfullscreen
                    src="https://maps.google.com/maps?q={{ $eventner->latitude }},{{ $eventner->longitude }}&hl=id&z=15&output=embed">
                </iframe>
            </div>
            @endif
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
                        @if($judge->photo)
                            <img src="{{ asset('storage/' . $judge->photo) }}" alt="{{ $judge->name }}" class="pm-judge-avatar" style="border-radius: 50%; object-fit: cover;">
                        @else
                            <div class="pm-judge-avatar">{{ strtoupper(substr($judge->name, 0, 1)) }}</div>
                        @endif
                        <div class="pm-judge-name">{{ $judge->name }}</div>
                        <div class="pm-judge-tags">
                            @foreach($judge->assessmentCategories->take(2) as $cat)
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

        {{-- ========== SHARE ========== --}}
        <div class="pm-section" style="padding-top: 0;">
            <div class="pm-card">
                <div class="pm-card-header">
                    <div class="pm-card-header-icon"><i class="fa fa-share-alt"></i></div>
                    <h3>Bagikan Event</h3>
                </div>
                <div class="pm-card-body">
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="https://wa.me/?text={{ urlencode($eventner->nama_event . ' - ' . url('/event/' . $eventner->slug)) }}"
                            target="_blank" class="pm-social-btn whatsapp" style="flex: 1; min-width: fit-content;">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('/event/' . $eventner->slug)) }}"
                            target="_blank" class="pm-social-btn instagram" style="background: #1877f2; flex: 1; min-width: fit-content;">
                            <i class="fab fa-facebook"></i> Facebook
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== WHATSAPP CONTACT ========== --}}
        @if($eventner->link_whatsapp)
        <div class="pm-section" style="padding-top: 0;">
            <div class="pm-card">
                <div class="pm-card-header" style="background: #10b981;">
                    <div class="pm-card-header-icon" style="background: rgba(255,255,255,0.2);"><i class="fab fa-whatsapp" style="color: #fff;"></i></div>
                    <h3 style="color: #fff;">Hubungi Penyelenggara</h3>
                </div>
                <div class="pm-card-body">
                    @php $waNumber = preg_replace('/[^0-9]/', '', $eventner->link_whatsapp); @endphp
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo, saya ingin bertanya tentang event ' . $eventner->nama_event) }}"
                        target="_blank" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: rgba(16,185,129,0.08); border-radius: 12px; text-decoration: none; border: 1px solid rgba(16,185,129,0.2);">
                        <div style="width: 48px; height: 48px; background: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fab fa-whatsapp" style="color: #fff; font-size: 24px;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--pm-text); font-size: 15px;">Chat WhatsApp</div>
                            <div style="font-size: 13px; color: var(--pm-text-secondary);">Klik untuk chat langsung</div>
                        </div>
                        <i class="fa fa-chevron-right" style="margin-left: auto; color: var(--pm-text-secondary);"></i>
                    </a>

                    @if($eventner->link_instagram || $eventner->link_tiktok)
                    <div style="display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                        @if($eventner->link_instagram)
                        <a href="{{ $eventner->link_instagram }}" target="_blank" class="pm-social-btn instagram" style="flex: 1; min-width: fit-content;">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        @endif
                        @if($eventner->link_tiktok)
                        <a href="{{ $eventner->link_tiktok }}" target="_blank" class="pm-social-btn tiktok" style="flex: 1; min-width: fit-content;">
                            <i class="fab fa-tiktok"></i> TikTok
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ========== PARTICIPANTS TAB ========== --}}
    <div x-show="tab === 'participants'" x-cloak>
        <div class="pm-section" style="padding-top: 16px;">
            @php $totalKontingen = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count()); @endphp
            <div class="pm-stats-grid" style="margin-bottom: 16px;">
                <div class="pm-stat-card">
                    <div class="pm-stat-value" style="color: var(--pm-primary);">{{ $eventner->competitionCategories->count() }}</div>
                    <div class="pm-stat-label">Kategori</div>
                </div>
                <div class="pm-stat-card">
                    <div class="pm-stat-value" style="color: #10b981;">{{ $totalKontingen }}</div>
                    <div class="pm-stat-label">Kontingen</div>
                </div>
            </div>

            @foreach($eventner->competitionCategories as $cat)
            <div class="pm-card">
                <div class="pm-card-header">
                    <div class="pm-card-header-icon"><i class="fa fa-trophy"></i></div>
                    <h3>{{ $cat->name }}</h3>
                    <span style="margin-left: auto; font-size: 12px; color: var(--pm-text-secondary);">{{ $cat->registrations->count() }} kontingen</span>
                </div>
                @forelse($cat->registrations as $reg)
                <div style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--pm-border);">
                    @if($reg->logo_sekolah)
                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid var(--pm-border);" alt="" loading="lazy">
                    @else
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(38,101,253,0.08); display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-school" style="color: var(--pm-primary); font-size: 16px;"></i>
                        </div>
                    @endif
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 14px; font-weight: 700; color: var(--pm-text);">{{ $reg->nama_sekolah }}</div>
                        <div style="font-size: 12px; color: var(--pm-text-secondary);">
                            @if($reg->urutan_tampil)
                                <span style="background: rgba(245,158,11,0.1); color: #f59e0b; padding: 1px 6px; border-radius: 4px; font-weight: 700; font-size: 11px;">#{{ str_pad($reg->urutan_tampil, 2, '0', STR_PAD_LEFT) }}</span>
                            @endif
                            Pelatih: {{ $reg->nama_pelatih ?? '—' }}
                        </div>
                    </div>
                    <span style="background: rgba(38,101,253,0.08); color: var(--pm-primary); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                        {{ $reg->participants->count() }} org
                    </span>
                </div>
                @empty
                <div class="pm-empty">Belum ada kontingen di kategori ini.</div>
                @endforelse
            </div>
            @endforeach
        </div>
    </div>

    {{-- ========== VOTING TAB ========== --}}
    @if($eventner->vote_active)
    <div x-show="tab === 'vote'" x-cloak>
        <div class="pm-section" style="padding-top: 16px;">
            @if($voteView === 'payment')
                {{-- PAYMENT VIEW --}}
                <div class="pm-card">
                    <div style="background: var(--pm-primary); padding: 20px; text-align: center;">
                        <i class="fa fa-qrcode" style="font-size: 32px; color: #fff;"></i>
                        <h4 style="color: #fff; margin: 8px 0;">Scan & Bayar</h4>
                    </div>
                    <div style="padding: 24px; text-align: center;">
                        <div style="background: #fff; border: 2px solid var(--pm-border); border-radius: 12px; padding: 16px; display: inline-block; margin-bottom: 16px;">
                            <img src="{{ $qrImageUrl }}" alt="QRIS" style="width: 200px; max-width: 100%; display: block;">
                        </div>
                        <div style="background: rgba(16,185,129,0.1); border-radius: 12px; padding: 16px; margin-bottom: 16px; max-width: 280px; margin-left: auto; margin-right: auto;">
                            <h3 style="color: #10b981; margin: 0 0 4px;">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</h3>
                            <p style="color: var(--pm-text-secondary); margin: 0; font-size: 13px;">{{ $voteCount }} vote × Rp {{ number_format($eventner->vote_price ?? 1000, 0, ',', '.') }}</p>
                        </div>
                        <div x-data="{ remaining: '', expired: false, init() { this.updateTimer(); setInterval(() => this.updateTimer(), 1000); }, updateTimer() { const exp = new Date('{{ $expiryTime }}').getTime(); const now = Date.now(); const diff = exp - now; if (diff <= 0) { this.remaining = '00:00'; this.expired = true; return; } const m = Math.floor(diff / 60000); const s = Math.floor((diff % 60000) / 1000); this.remaining = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0'); } }">
                            <span style="font-weight: 600; color: var(--pm-text);">Kedaluwarsa: <span x-text="remaining" style="font-family: monospace; color: #ef4444;"></span></span>
                        </div>
                        <p style="color: var(--pm-text-secondary); font-size: 12px; margin-top: 12px;">Buka aplikasi e-wallet dan scan QR di atas</p>
                        <button wire:click="resetPayment" class="pm-btn pm-btn-outline" style="margin-top: 16px;">
                            <i class="fa fa-arrow-left"></i> Batal & Kembali
                        </button>
                    </div>
                </div>
            @elseif($voteView === 'success')
                {{-- SUCCESS --}}
                <div class="pm-card">
                    <div style="background: #10b981; padding: 30px; text-align: center;">
                        <i class="fa fa-check-circle" style="font-size: 48px; color: #fff;"></i>
                        <h3 style="color: #fff; margin: 12px 0 4px;">Pembayaran Berhasil!</h3>
                        <p style="color: rgba(255,255,255,0.8); margin: 0;">{{ $voteCount }} vote telah ditambahkan.</p>
                    </div>
                    <div style="padding: 24px; text-align: center;">
                        <button wire:click="backToCategories" class="pm-btn pm-btn-primary">
                            <i class="fa fa-arrow-left"></i> Kembali ke Voting
                        </button>
                    </div>
                </div>
            @elseif($voteView === 'categories')
                {{-- CATEGORIES --}}
                <div style="text-align: center; margin-bottom: 16px;">
                    <h4 style="font-weight: 700; color: var(--pm-text);">Pilih Kategori</h4>
                </div>
                <div style="display: grid; gap: 12px;">
                    @foreach($this->voteCategories as $cat)
                    <div wire:click="$set('selectedCategoryId', {{ $cat->id }}); $set('voteView', 'participants')"
                        style="background: var(--pm-surface); border: 1px solid var(--pm-border); border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s;"
                        onmouseover="this.style.borderColor='var(--pm-primary)'; this.style.boxShadow='0 4px 12px rgba(38,101,253,0.15)';"
                        onmouseout="this.style.borderColor='var(--pm-border)'; this.style.boxShadow='none';">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 48px; height: 48px; background: rgba(38,101,253,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-trophy" style="color: var(--pm-primary); font-size: 20px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; font-size: 15px; color: var(--pm-text);">{{ $cat->name }}</div>
                                <div style="font-size: 13px; color: var(--pm-text-secondary);">{{ $cat->registrations_count }} Kontingen</div>
                            </div>
                            <i class="fa fa-chevron-right" style="color: var(--pm-text-secondary);"></i>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                {{-- PARTICIPANTS --}}
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    <button wire:click="backToCategories" style="background: var(--pm-surface); border: 1px solid var(--pm-border); border-radius: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fa fa-arrow-left" style="color: var(--pm-text-secondary);"></i>
                    </button>
                    <div>
                        <div style="font-weight: 600; color: var(--pm-text);">{{ $this->voteParticipants->first()?->competitionCategory->name ?? 'Kategori' }}</div>
                        <div style="font-size: 12px; color: var(--pm-text-secondary);">Pilih kontingen untuk divote</div>
                    </div>
                </div>

                <div style="background: var(--pm-surface); border: 1px solid var(--pm-border); border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                    <i class="fa fa-search" style="color: var(--pm-text-secondary);"></i>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama sekolah..." style="border: none; outline: none; flex: 1; font-size: 15px; background: transparent;">
                </div>

                <div style="display: grid; gap: 10px;">
                    @forelse($this->voteParticipants as $reg)
                    <div wire:click="$set('selectedRegistrationId', {{ $reg->id }})"
                        style="background: var(--pm-surface); border: {{ $selectedRegistrationId == $reg->id ? '2px solid var(--pm-primary)' : '1px solid var(--pm-border)' }}; border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.2s;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            @if($reg->logo_sekolah)
                                <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;" alt="" loading="lazy">
                            @else
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(38,101,253,0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-school" style="color: var(--pm-primary);"></i>
                                </div>
                            @endif
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $reg->nama_sekolah }}</div>
                                <div style="font-size: 12px; color: var(--pm-text-secondary);">Pelatih: {{ $reg->nama_pelatih }}</div>
                            </div>
                            <span style="background: rgba(245,158,11,0.1); color: #f59e0b; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 4px;">
                                <i class="fa fa-heart"></i> {{ number_format($reg->total_votes ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="pm-empty">Tidak ada kontingen ditemukan.</div>
                    @endforelse
                </div>

                {{-- VOTE FORM (shown when team selected) --}}
                @if($selectedRegistrationId)
                <div class="pm-card" style="margin-top: 16px;">
                    <div style="background: var(--pm-primary); padding: 14px 18px;">
                        <h5 style="color: #fff; margin: 0; font-size: 15px;"><i class="fa fa-heart" style="margin-right: 6px;"></i>Form Voting</h5>
                    </div>
                    <div style="padding: 16px;">
                        @php $selectedTeam = \App\Models\Registration::find($selectedRegistrationId); @endphp
                        <div style="background: var(--pm-bg); border-radius: 8px; padding: 12px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                            <span style="font-weight: 700; color: var(--pm-primary);">Vote untuk:</span>
                            <span style="font-weight: 600;">{{ $selectedTeam->nama_sekolah }}</span>
                        </div>

                        <div style="margin-bottom: 14px;">
                            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Jumlah Vote</label>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">
                                @foreach([10, 50, 100, 500] as $val)
                                <button type="button" wire:click="$set('voteCount', {{ $val }})" style="padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; {{ $voteCount == $val ? 'background: var(--pm-primary); color: #fff; border: none;' : 'background: var(--pm-bg); color: var(--pm-primary); border: 1px solid var(--pm-border);' }}">
                                    {{ $val }}
                                </button>
                                @endforeach
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 13px; color: var(--pm-text-secondary);">Custom:</span>
                                <input type="number" wire:model.live.debounce.300ms="voteCount" min="1" max="10000" style="width: 100px; border: 1px solid var(--pm-border); border-radius: 6px; padding: 6px 10px; font-size: 14px; font-weight: 600; text-align: center;">
                            </div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Nama Lengkap</label>
                            <input type="text" wire:model="voterName" style="width: 100%; border: 1px solid var(--pm-border); border-radius: 8px; padding: 10px 12px; font-size: 14px;">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="font-weight: 600; font-size: 13px; display: block; margin-bottom: 6px;">Email</label>
                            <input type="email" wire:model="voterEmail" style="width: 100%; border: 1px solid var(--pm-border); border-radius: 8px; padding: 10px 12px; font-size: 14px;">
                        </div>

                        <div style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); border-radius: 8px; padding: 12px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--pm-text-secondary); font-size: 13px;">Total Bayar</span>
                                <span style="font-weight: 800; color: var(--pm-primary); font-size: 18px;">Rp {{ number_format($voteCount * ($eventner->vote_price ?? 1000), 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <button wire:click="submitVote" class="pm-btn pm-btn-primary" style="width: 100%;" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="fa fa-credit-card"></i> Bayar Sekarang</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                        <p style="text-align: center; color: var(--pm-text-secondary); font-size: 11px; margin-top: 8px;">via QRIS</p>
                    </div>
                </div>
                @endif
            @endif
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

</div>

{{-- Countdown Timer Script --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('countdown', (targetDate) => ({
        days: '00', hours: '00', minutes: '00', seconds: '00',
        init() {
            this.update();
            setInterval(() => this.update(), 1000);
        },
        update() {
            const target = new Date(targetDate).getTime();
            const now = Date.now();
            const diff = target - now;
            if (diff <= 0) {
                this.days = '00'; this.hours = '00'; this.minutes = '00'; this.seconds = '00';
                return;
            }
            this.days = String(Math.floor(diff / 86400000)).padStart(2, '0');
            this.hours = String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0');
            this.minutes = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
            this.seconds = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
        }
    }));
});
</script>