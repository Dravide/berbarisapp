<div class="premium-event-page">
    {{-- Hero --}}
    <div class="pm-hero">
        <div class="pm-hero-content">
            <div class="d-flex align-items-center gap-3 mb-3">
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="pm-hero-logo" alt="">
                @endif
                <div>
                    <h1 class="pm-event-title">Daftar Peserta</h1>
                    <p class="pm-event-org" style="margin-bottom:0;">{{ $eventner->nama_event }}</p>
                </div>
            </div>
            <div class="pm-date-chips">
                @foreach($eventner->competitionCategories as $cat)
                    <div class="pm-chip"><i class="fa fa-trophy"></i> {{ $cat->name }}</div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $totalKontingen = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
        $totalAnggota = $eventner->competitionCategories->sum(fn($c) => $c->registrations->sum(fn($r) => $r->participants->count()));
        $totalVerified = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'Terverifikasi')->count());
    @endphp
    <div class="pm-section" style="padding-top: 0;">
        <div class="pm-stats-grid">
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: var(--pm-primary);">{{ $eventner->competitionCategories->count() }}</div>
                <div class="pm-stat-label">Kategori</div>
            </div>
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: #10b981;">{{ $totalKontingen }}</div>
                <div class="pm-stat-label">Kontingen</div>
            </div>
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: var(--pm-primary);">{{ $totalVerified }}</div>
                <div class="pm-stat-label">Terverifikasi</div>
            </div>
            <div class="pm-stat-card">
                <div class="pm-stat-value" style="color: #f59e0b;">{{ $totalAnggota }}</div>
                <div class="pm-stat-label">Total Peserta</div>
            </div>
        </div>
    </div>

    {{-- Participants by Category --}}
    <div class="pm-section" style="padding-top: 0;">
        @foreach($eventner->competitionCategories as $cat)
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-header-icon"><i class="fa fa-trophy"></i></div>
                <h3>{{ $cat->name }}</h3>
                <span style="margin-left: auto; font-size: 12px; color: var(--pm-text-secondary);">{{ $cat->registrations->count() }} kontingen</span>
            </div>

            @if($cat->registrations->count() > 0)
                @foreach($cat->registrations as $reg)
                <div style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--pm-border);">
                    @if($reg->logo_sekolah)
                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid var(--pm-border);" alt="" loading="lazy">
                    @else
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(38,101,253,0.08); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
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
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <span style="background: rgba(38,101,253,0.08); color: var(--pm-primary); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                            {{ $reg->participants->count() }} org
                        </span>
                        @if($reg->status_berkas === 'Terverifikasi')
                            <span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                <i class="fa fa-check"></i>
                            </span>
                        @elseif($reg->status_berkas === 'Ditolak')
                            <span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                <i class="fa fa-times"></i>
                            </span>
                        @elseif($reg->status_berkas === 'booking')
                            <span style="background: rgba(107,114,128,0.1); color: var(--pm-text-secondary); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                <i class="fa fa-clock"></i>
                            </span>
                        @else
                            <span style="background: rgba(245,158,11,0.1); color: #f59e0b; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                <i class="fa fa-hourglass-half"></i>
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            @else
                <div class="pm-empty">Belum ada kontingen di kategori ini.</div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Bottom Navigation --}}
    <nav class="pm-bottom-nav">
        <a href="{{ route('event.detail', $eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('event.participant', $eventner->slug) }}" class="pm-nav-item active">
            <i class="fa fa-users"></i>
            <span>Peserta</span>
        </a>
        @if($eventner->vote_active)
        <a href="{{ route('event.vote', $eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-heart"></i>
            <span>Vote</span>
        </a>
        @endif
        @if($eventner->ticket_active && $eventner->ticket_price)
        <a href="{{ route('event.ticket', $eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-ticket"></i>
            <span>Tiket</span>
        </a>
        @endif
        @if(($eventner->registration_status ?? 'open') != 'closed')
        <a href="{{ route('event.register', $eventner->slug) }}" class="pm-nav-item">
            <i class="fa fa-edit"></i>
            <span>Daftar</span>
        </a>
        @endif
    </nav>
</div>
