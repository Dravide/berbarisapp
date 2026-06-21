<div class="min-h-screen bg-surface">

    {{-- ========== COVER BANNER / FALLBACK GRADIENT ========== --}}
    <div class="container-landing pt-6">
        @if($eventner->poster)
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-md border border-outline-variant/30 bg-black/5">
                <img src="{{ asset('storage/' . $eventner->poster) }}" alt="Poster {{ $eventner->nama_event }}" class="w-full h-full object-cover">
            </div>
        @else
            <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary rounded-2xl h-36 md:h-48">
                {{-- Decorative light orbs --}}
                <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
                <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            </div>
        @endif
    </div>

    {{-- ========== HEADER INFO BLOCK ========== --}}
    <div class="container-landing pt-6">
        <div class="grid gap-6 md:grid-cols-3 items-center">
            {{-- Logo & Title --}}
            <div class="md:col-span-2 flex items-start gap-4">
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-16 w-16 md:h-20 md:w-20 rounded-2xl object-cover shadow-sm border border-outline-variant/30 shrink-0" alt="{{ $eventner->nama_event }}">
                @else
                    <div class="flex h-16 w-16 md:h-20 md:w-20 items-center justify-center rounded-2xl bg-primary/10 text-primary border border-outline-variant/30 shrink-0">
                        <i class="ti ti-calendar-event text-3xl"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        @if($eventner->tingkat_perlombaan)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider text-primary border border-primary/20">
                                <i class="ti ti-medal"></i>
                                {{ $eventner->tingkat_perlombaan }}
                            </span>
                        @endif
                        @if($eventner->link_livestreaming)
                            <a href="{{ $eventner->link_livestreaming }}" target="_blank" class="badge-live text-decoration-none transition hover:bg-secondary/25">
                                Live Streaming
                            </a>
                        @endif
                    </div>
                    <h1 class="font-display text-2xl font-extrabold tracking-tight text-deep-slate leading-tight sm:text-3xl">
                        {{ $eventner->nama_event }}
                    </h1>
                    <p class="mt-2 text-sm font-semibold text-on-surface-variant">
                        Diselenggarakan oleh: <span class="text-primary font-bold">{{ $eventner->diselenggarakan_oleh }}</span>
                    </p>
                </div>
            </div>

            {{-- Countdown Timer --}}
            @if($eventner->tanggal && \Carbon\Carbon::parse($eventner->tanggal)->isFuture())
                <div class="md:col-span-1" x-data="countdown('{{ \Carbon\Carbon::parse($eventner->tanggal)->toIso8601String() }}')">
                    <div class="surface-card p-4 border border-outline-variant/40 bg-white">
                        <span class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider block text-center mb-3">Menuju Hari H Perlombaan</span>
                        <div class="grid grid-cols-4 gap-2 max-w-[280px] mx-auto">
                            <div class="flex flex-col bg-primary/5 border border-primary/10 rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-primary leading-tight" x-text="days"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-bold tracking-wider mt-0.5">Hari</span>
                            </div>
                            <div class="flex flex-col bg-primary/5 border border-primary/10 rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-primary leading-tight" x-text="hours"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-bold tracking-wider mt-0.5">Jam</span>
                            </div>
                            <div class="flex flex-col bg-primary/5 border border-primary/10 rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-primary leading-tight" x-text="minutes"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-bold tracking-wider mt-0.5">Mnt</span>
                            </div>
                            <div class="flex flex-col bg-primary/5 border border-primary/10 rounded-xl p-1.5 min-w-0 items-center justify-center">
                                <span class="font-display text-base font-extrabold text-primary leading-tight" x-text="seconds"></span>
                                <span class="text-[8px] text-on-surface-variant uppercase font-bold tracking-wider mt-0.5">Det</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ========== QUICK INFO CARD ========== --}}
    @php
        $totalKuota = $eventner->competitionCategories->sum('kuota');
        $totalReg = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
    @endphp
    <div class="container-landing pt-6">
        <div class="surface-card p-6">
            <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-4">
                @if($eventner->tanggal)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="ti ti-calendar text-2xl"></i>
                        </div>
                        <div>
                            <span class="overline !text-[10px] block">Tanggal</span>
                            <span class="text-sm font-bold text-deep-slate">{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</span>
                        </div>
                    </div>
                @endif

                @if($eventner->lokasi)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="ti ti-map-pin text-2xl"></i>
                        </div>
                        <div>
                            <span class="overline !text-[10px] block">Lokasi</span>
                            <span class="text-sm font-bold text-deep-slate block truncate max-w-[180px]" title="{{ $eventner->lokasi }}">{{ $eventner->lokasi }}</span>
                        </div>
                    </div>
                @endif

                @if($eventner->tingkat_perlombaan)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="ti ti-trophy text-2xl"></i>
                        </div>
                        <div>
                            <span class="overline !text-[10px] block">Tingkat</span>
                            <span class="text-sm font-bold text-deep-slate">{{ $eventner->tingkat_perlombaan }}</span>
                        </div>
                    </div>
                @endif

                @if($totalKuota)
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#5a7d00]/10 text-[#5a7d00]">
                            <i class="ti ti-users text-2xl"></i>
                        </div>
                        <div>
                            <span class="overline !text-[10px] block">Kuota Pendaftar</span>
                            <span class="text-sm font-bold text-deep-slate">{{ $totalReg }} / {{ $totalKuota }} Pasukan</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ========== TAB CONTENT: INFO ========== --}}
    <div class="container-landing py-8">
        <div class="grid gap-8 md:grid-cols-3">
            {{-- Main Column --}}
            <div class="md:col-span-2 flex flex-col gap-8">
                {{-- Kuota Kategori --}}
                @if($eventner->competitionCategories->count() > 0)
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-chart-bar text-primary"></i>
                            Kuota Pendaftaran Kategori
                        </h3>
                        <div class="flex flex-col gap-4">
                            @foreach($eventner->competitionCategories as $cat)
                                <div>
                                    <div class="flex justify-between items-center mb-1.5 text-sm font-semibold">
                                        <span class="text-deep-slate">{{ $cat->name }}</span>
                                        <span class="text-on-surface-variant">{{ $cat->registrations->count() }} / {{ $cat->kuota ?? '∞' }} Pasukan</span>
                                    </div>
                                    @if($cat->kuota)
                                        @php $percent = min(100, round($cat->registrations->count() / $cat->kuota * 100)); @endphp
                                        <div class="h-2.5 bg-surface-container rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $percent >= 100 ? 'bg-red-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-primary') }}" style="width: {{ $percent }}%"></div>
                                        </div>
                                    @else
                                        <div class="h-2.5 bg-surface-container rounded-full"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tentang Acara --}}
                <div class="surface-card p-6">
                    <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                        <i class="ti ti-file-text text-primary"></i>
                        Tentang Acara
                    </h3>
                    <div class="text-sm text-on-surface-variant leading-relaxed whitespace-pre-line font-sans">
                        {{ $eventner->deskripsi ?? 'Detail deskripsi acara belum ditambahkan oleh penyelenggara.' }}
                    </div>
                </div>

                {{-- Dewan Juri --}}
                @if($eventner->judges->count() > 0)
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-6">
                            <i class="ti ti-gavel text-primary"></i>
                            Dewan Juri
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($eventner->judges as $judge)
                                <div class="flex items-center gap-4 p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    @if($judge->photo)
                                        <img src="{{ asset('storage/' . $judge->photo) }}" alt="{{ $judge->name }}" class="h-12 w-12 rounded-full object-cover">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary font-bold text-lg">
                                            {{ strtoupper(substr($judge->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-sm font-bold text-deep-slate leading-tight mb-1">{{ $judge->name }}</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($judge->assessmentCategories->take(2) as $cat)
                                                <span class="chip py-0.5 px-2 !text-[10px]">{{ $cat->name }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Tenant / Bazaar --}}
                @if($eventner->tenants->count() > 0)
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-6">
                            <i class="ti ti-store text-primary"></i>
                            Tenant &amp; Bazaar
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($eventner->tenants as $tenant)
                                <div class="flex items-center gap-4 p-4 rounded-xl bg-surface-container-low border border-outline-variant/30">
                                    @if($tenant->logo)
                                        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="h-12 w-12 rounded-lg object-cover">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            <i class="ti ti-store text-xl"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="text-sm font-bold text-deep-slate mb-0.5">{{ $tenant->name }}</h4>
                                        <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider block">
                                            @if($tenant->type === 'culinary') Kuliner
                                            @elseif($tenant->type === 'beverage') Minuman
                                            @elseif($tenant->type === 'souvenir') Souvenir
                                            @elseif($tenant->type === 'bazaar') Bazaar
                                            @else Lainnya @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar Column --}}
            <div class="flex flex-col gap-8">
                {{-- Informasi Jadwal & TM --}}
                @if($eventner->lokasi || $eventner->technical_meeting)
                    <div class="surface-card p-6">
                        <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-info-square text-primary"></i>
                            Informasi Jadwal
                        </h3>
                        <div class="flex flex-col gap-4 text-sm">
                            @if($eventner->technical_meeting)
                                <div class="flex gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">
                                        <i class="ti ti-presentation text-lg"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-on-surface-variant font-medium block">Technical Meeting</span>
                                        <span class="font-bold text-deep-slate leading-normal">{{ \Carbon\Carbon::parse($eventner->technical_meeting)->translatedFormat('d F Y, H:i') }} WIB</span>
                                    </div>
                                </div>
                            @endif

                            @if($eventner->tanggal_pendaftaran)
                                <div class="flex gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#5a7d00]/10 text-[#5a7d00] shrink-0">
                                        <i class="ti ti-calendar-plus text-lg"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs text-on-surface-variant font-medium block">Batas Pendaftaran</span>
                                        <span class="font-bold text-deep-slate leading-normal">{{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->translatedFormat('d F Y') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Google Maps Embed --}}
                        @if($eventner->latitude && $eventner->longitude)
                            <div class="mt-6 rounded-xl overflow-hidden border border-outline-variant/50">
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

                {{-- Hubungi Penyelenggara --}}
                @if($eventner->link_whatsapp)
                    <div class="surface-card p-6">
                        <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-message-2 text-primary"></i>
                            Hubungi Penyelenggara
                        </h3>
                        @php $waNumber = preg_replace('/[^0-9]/', '', $eventner->link_whatsapp); @endphp
                        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Halo, saya ingin bertanya tentang event ' . $eventner->nama_event) }}"
                            target="_blank" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/20 text-decoration-none group transition">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm transition group-hover:scale-105">
                                <i class="ti ti-brand-whatsapp text-2xl"></i>
                            </div>
                            <div>
                                <span class="font-bold text-deep-slate text-sm block group-hover:text-primary transition">Chat WhatsApp</span>
                                <span class="text-xs text-on-surface-variant block">Tanya langsung ke Admin</span>
                            </div>
                            <i class="ti ti-chevron-right text-on-surface-variant ml-auto transition group-hover:translate-x-1"></i>
                        </a>

                        @if($eventner->link_instagram || $eventner->link_tiktok)
                            <div class="flex gap-2 mt-3">
                                @if($eventner->link_instagram)
                                    <a href="{{ $eventner->link_instagram }}" target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none">
                                        <i class="ti ti-brand-instagram text-base"></i> Instagram
                                    </a>
                                @endif
                                @if($eventner->link_tiktok)
                                    <a href="{{ $eventner->link_tiktok }}" target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none">
                                        <i class="ti ti-brand-tiktok text-base"></i> TikTok
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Bagikan Event --}}
                <div class="surface-card p-6">
                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                        <i class="ti ti-share text-primary"></i>
                        Bagikan Event
                    </h3>
                    <div class="flex gap-2">
                        <a href="https://wa.me/?text={{ urlencode($eventner->nama_event . ' - ' . url('/event/' . $eventner->slug)) }}"
                            target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none !text-emerald-600 !border-emerald-500/30 hover:!bg-emerald-50">
                            <i class="ti ti-brand-whatsapp text-base"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('/event/' . $eventner->slug)) }}"
                            target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none !text-blue-600 !border-blue-500/30 hover:!bg-blue-50">
                            <i class="ti ti-brand-facebook text-base"></i> Facebook
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sponsor grid (Full width di bawah) --}}
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
            <div class="mt-8 flex flex-col gap-6">
                @foreach($typeOrders as $t)
                    @if(isset($sGrouped[$t]) && $sGrouped[$t]->count() > 0)
                        <div class="surface-card p-6">
                            <span class="overline mb-4">{{ $typeLabels[$t] ?? $t }}</span>
                            <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12 mt-2">
                                @foreach($sGrouped[$t] as $sponsor)
                                    @if($sponsor->link)
                                        <a href="{{ $sponsor->link }}" target="_blank" class="transition hover:opacity-85">
                                    @endif
                                    @if($sponsor->logo)
                                        <img src="{{ asset('storage/' . $sponsor->logo) }}" class="h-14 md:h-16 w-auto object-contain max-w-[180px]" alt="{{ $sponsor->name }}" loading="lazy">
                                    @else
                                        <span class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">{{ $sponsor->name }}</span>
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
    </div>


    {{-- ========== FLOATING REGISTER CTA (Fixed Bottom) ========== --}}
    @if(($eventner->registration_status ?? 'open') != 'closed')
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 max-w-sm w-[calc(100%-2rem)]">
            <a href="{{ route('event.register', $eventner->slug) }}" class="btn-primary py-3.5 px-6 font-bold text-sm w-full text-center shadow-lg hover:shadow-xl inline-flex justify-center text-decoration-none">
                <i class="ti ti-clipboard-list text-base"></i>
                {{ ($eventner->registration_status ?? 'open') == 'booking' ? 'Booking Slot Sekarang' : 'Daftar Sekarang' }}
            </a>
        </div>
    @endif

</div>

{{-- Countdown Timer Alpine Component Definition --}}
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
