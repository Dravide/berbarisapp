<div class="min-h-screen bg-surface">

    {{-- ========== HEADER BANNER — landscape wide image, separate from A4 poster ========== --}}
    <div class="container-landing pt-6">
        @if($eventner->header_banner)
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-md border border-outline-variant/30 bg-black/5">
                <img src="{{ asset('storage/' . $eventner->header_banner) }}" alt="Banner {{ $eventner->nama_event }}" class="w-full h-full object-cover">
            </div>
        @elseif($eventner->poster)
            {{-- Fallback: pakai poster A4 — masih bisa diklik buka asli --}}
            <div class="relative w-full aspect-[21/9] md:aspect-[3/1] rounded-2xl overflow-hidden shadow-md border border-outline-variant/30 bg-black/5">
                <a href="{{ asset('storage/' . $eventner->poster) }}" target="_blank">
                    <img src="{{ asset('storage/' . $eventner->poster) }}" alt="Poster {{ $eventner->nama_event }}" class="w-full h-full object-cover">
                </a>
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
                        <i class="ti ti-building-skyscraper text-primary me-1"></i> Diselenggarakan oleh: <span class="text-primary font-bold">{{ $eventner->diselenggarakan_oleh }}</span>
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        @if($eventner->link_instagram)
                            <a href="{{ $eventner->link_instagram }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-pink-500/10 px-3 py-1 text-[11px] font-bold text-pink-600 border border-pink-500/20 hover:bg-pink-500/20 transition text-decoration-none">
                                <i class="ti ti-brand-instagram"></i> Instagram
                            </a>
                        @endif
                        @if($eventner->link_tiktok)
                            <a href="{{ $eventner->link_tiktok }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-dark/10 px-3 py-1 text-[11px] font-bold text-dark border border-dark/20 hover:bg-dark/10 transition text-decoration-none">
                                <i class="ti ti-brand-tiktok"></i> TikTok
                            </a>
                        @endif
                        @if($eventner->link_whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $eventner->link_whatsapp) }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-600 border border-emerald-500/20 hover:bg-emerald-500/20 transition text-decoration-none">
                                <i class="ti ti-brand-whatsapp"></i> WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Countdown Timer --}}
            @if($eventner->tanggal && \Carbon\Carbon::parse($eventner->tanggal_akhir ?? $eventner->tanggal)->isFuture())
                <div class="md:col-span-1" x-data="countdown('{{ \Carbon\Carbon::parse($eventner->tanggal_akhir ?? $eventner->tanggal)->toIso8601String() }}')">
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
                            <span class="text-sm font-bold text-deep-slate">
                                @if($eventner->tanggal_akhir)
                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d M') }} - {{ \Carbon\Carbon::parse($eventner->tanggal_akhir)->translatedFormat('d F Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}
                                @endif
                            </span>
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

    {{-- ========== TOP VOTER PER KATEGORI (FADE CYCLING) ========== --}}
    @php $leaderboardData = collect($this->voteLeaderboard())->filter(fn($item) => $item['top']->isNotEmpty())->values(); @endphp
    @if($leaderboardData->isNotEmpty())
        <div class="container-landing pt-6" x-data="{ li: 0 }" x-init="setInterval(() => { li = (li + 1) % {{ $leaderboardData->count() }} }, 5000)">
            <div class="surface-card p-6 overflow-hidden relative">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2">
                        <i class="ti ti-heart-filled text-primary"></i>
                        Voter Tertinggi per Kategori
                    </h3>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider" x-text="'{{ $leaderboardData->count() }} kategori'"></span>
                </div>

                <div class="relative" style="min-height: 120px;">
                    @foreach($leaderboardData as $idx => $item)
                        @php $cat = $item['category']; $topReg = $item['top']->first(); @endphp
                        <div x-show="li === {{ $idx }}"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-300"
                             x-transition:leave-start="opacity-100 translate-x-0"
                             x-transition:leave-end="opacity-0 -translate-x-4"
                             class="absolute inset-0">
                            @if($topReg)
                                <div class="flex items-center gap-4 p-4 rounded-xl bg-primary/5 border border-primary/10">
                                    <div class="shrink-0 relative">
                                        @if($topReg['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $topReg['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-primary/20 shadow-sm">
                                        @else
                                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary border-2 border-primary/20"><i class="ti ti-school text-2xl"></i></span>
                                        @endif
                                        <span class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400 text-white text-[10px] font-extrabold shadow-sm" style="text-shadow: 0 1px 2px rgba(0,0,0,0.15);">
                                            <i class="ti ti-crown-filled text-[11px]"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block mb-0.5">{{ $cat->full_name ?? $cat->name }}</span>
                                        <h4 class="text-base font-extrabold text-deep-slate truncate">{{ $topReg['nama_sekolah'] }}</h4>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="font-display text-lg font-extrabold text-primary">{{ number_format($topReg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                            <span class="text-[11px] font-semibold text-on-surface-variant">suara</span>
                                        </div>
                                        <a href="{{ event_url($eventner, 'vote', ['selectedCategoryId' => $cat->id]) }}" class="btn-primary mt-3 py-2 px-3 text-xs font-bold inline-flex items-center justify-center gap-1.5 text-decoration-none w-full sm:w-auto">
                                            <i class="ti ti-heart-filled"></i> Vote Kategori Ini
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Dot indicators --}}
                <div class="flex justify-center gap-1.5 mt-3">
                    @foreach($leaderboardData as $idx => $item)
                        <button type="button" class="h-1.5 rounded-full transition-all duration-300 border-0 cursor-pointer"
                                :class="li === {{ $idx }} ? 'w-4 bg-primary' : 'w-1.5 bg-outline-variant'"
                                @click="li = {{ $idx }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ========== TAB CONTENT: INFO ========== --}}
    <div class="container-landing py-8">
        <div class="grid gap-8 md:grid-cols-3">
            {{-- Main Column --}}
            <div class="md:col-span-2 flex flex-col gap-8">
                {{-- Kuota Kategori --}}
                @if($eventner->competitionCategories->count() > 0)
                    @php
                        $parents = $eventner->competitionCategories->whereNull('parent_id')->sortBy('sort_order');
                        $children = $eventner->competitionCategories->whereNotNull('parent_id');
                    @endphp
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-chart-bar text-primary"></i>
                            Kuota Pendaftaran Kategori
                        </h3>
                        <div class="flex flex-col gap-4">
                            @foreach($parents as $parent)
                                <div class="border border-outline-variant/30 rounded-xl p-3">
                                    <div class="text-sm font-extrabold text-deep-slate mb-3">{{ $parent->name }}</div>
                                    @if($parent->children->isEmpty())
                                        {{-- Old flat data — parent tanpa child --}}
                                        @php $pct = $parent->kuota ? min(100, round($parent->registrations->count() / $parent->kuota * 100)) : 0; @endphp
                                        <div class="flex justify-between items-center mb-1.5 text-xs font-semibold">
                                            <span class="text-on-surface-variant">{{ $parent->name }}</span>
                                            <span class="text-on-surface-variant">{{ $parent->registrations->count() }} / {{ $parent->kuota ?? '∞' }}</span>
                                        </div>
                                        <div class="h-2 bg-surface-container rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-primary') }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    @else
                                        @foreach($parent->children->sortBy('sort_order') as $child)
                                            @php $pct = $child->kuota ? min(100, round($child->registrations->count() / $child->kuota * 100)) : 0; @endphp
                                            <div class="mb-2 last:mb-0">
                                                <div class="flex justify-between items-center mb-1 text-xs font-semibold">
                                                    <span class="text-on-surface-variant">{{ $child->name }}</span>
                                                    <span class="text-on-surface-variant">{{ $child->registrations->count() }} / {{ $child->kuota ?? '∞' }}</span>
                                                </div>
                                                @if($child->kuota)
                                                <div class="h-1.5 bg-surface-container rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-500 {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-primary') }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach

                            {{-- Orphan children (data lama tanpa parent) --}}
                            @php $orphans = $children->whereNotIn('parent_id', $parents->pluck('id')); @endphp
                            @foreach($orphans as $orphan)
                                @php $pct = $orphan->kuota ? min(100, round($orphan->registrations->count() / $orphan->kuota * 100)) : 0; @endphp
                                <div>
                                    <div class="flex justify-between items-center mb-1.5 text-sm font-semibold">
                                        <span class="text-deep-slate">{{ $orphan->name }}</span>
                                        <span class="text-on-surface-variant">{{ $orphan->registrations->count() }} / {{ $orphan->kuota ?? '∞' }}</span>
                                    </div>
                                    @if($orphan->kuota)
                                    <div class="h-2.5 bg-surface-container rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-primary') }}" style="width: {{ $pct }}%"></div>
                                    </div>
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

                {{-- Galeri Foto --}}
                @php $galleries = \App\Models\EventGallery::where('eventner_id', $eventner->id)->orderBy('sort_order')->latest()->get(); @endphp

                @if($galleries->isNotEmpty())
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-photo text-primary"></i>
                            Galeri Foto
                        </h3>
                        <div class="grid gap-2 grid-cols-3">
                            @foreach($galleries->take(6) as $gal)
                                <a href="{{ asset('storage/' . $gal->image) }}" target="_blank" class="rounded-lg overflow-hidden border border-outline-variant/30 hover:opacity-90 transition">
                                    <img src="{{ asset('storage/' . $gal->image) }}" class="w-full h-24 object-cover" alt="{{ $gal->caption ?? 'Foto Event' }}" loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- FAQ --}}
                @php $faqs = \App\Models\EventFaq::where('eventner_id', $eventner->id)->orderBy('sort_order')->get(); @endphp
                @if($faqs->isNotEmpty())
                    <div class="surface-card p-6">
                        <h3 class="font-display text-lg font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-help-circle text-primary"></i>
                            Tanya Jawab (FAQ)
                        </h3>
                        <div class="flex flex-col gap-2">
                            @foreach($faqs as $faq)
                                <details class="group border border-outline-variant/30 rounded-xl p-4 hover:border-primary/30 transition">
                                    <summary class="text-sm font-bold text-deep-slate cursor-pointer list-none flex justify-between items-center">
                                        {{ $faq->question }}
                                        <i class="ti ti-chevron-down text-on-surface-variant group-open:hidden"></i>
                                        <i class="ti ti-chevron-up text-primary hidden group-open:inline"></i>
                                    </summary>
                                    <p class="text-sm text-on-surface-variant mt-3 leading-relaxed">{{ $faq->answer }}</p>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif

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
                {{-- Poster Acara Card — small, sidebar, klik buka ViewerJS --}}
                @if($eventner->poster)
                    <div class="surface-card p-4">
                        <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 mb-3">
                            <i class="ti ti-file-text text-primary"></i>
                            Poster Acara
                        </h3>
                        <div class="flex justify-center">
                            <a href="{{ asset('storage/' . $eventner->poster) }}" target="_blank">
                                <img src="{{ asset('storage/' . $eventner->poster) }}" alt="Poster {{ $eventner->nama_event }}"
                                     class="w-full h-auto rounded-lg border border-outline-variant/30 shadow-sm hover:shadow-md transition object-contain"
                                     style="max-height: 280px;"
                                     loading="lazy">
                            </a>
                        </div>
                        <p class="text-[10px] text-on-surface-variant font-medium text-center mt-2">Klik untuk lihat full</p>
                    </div>
                @endif

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
                                {{-- Countdown pendaftaran --}}
                                <div class="mt-3 rounded-lg bg-red-500/5 border border-red-500/20 p-3" x-data="countdown('{{ \Carbon\Carbon::parse($eventner->tanggal_pendaftaran)->endOfDay()->toIso8601String() }}')">
                                    <span class="text-[10px] text-red-500 font-bold uppercase tracking-wider block text-center mb-2">Pendaftaran Ditutup Dalam</span>
                                    <div class="grid grid-cols-4 gap-1.5">
                                        <div class="bg-red-500/10 rounded-lg p-1 text-center">
                                            <span class="text-sm font-extrabold text-red-500 block leading-tight" x-text="days"></span>
                                            <span class="text-[8px] text-red-400 font-bold uppercase">Hari</span>
                                        </div>
                                        <div class="bg-red-500/10 rounded-lg p-1 text-center">
                                            <span class="text-sm font-extrabold text-red-500 block leading-tight" x-text="hours"></span>
                                            <span class="text-[8px] text-red-400 font-bold uppercase">Jam</span>
                                        </div>
                                        <div class="bg-red-500/10 rounded-lg p-1 text-center">
                                            <span class="text-sm font-extrabold text-red-500 block leading-tight" x-text="minutes"></span>
                                            <span class="text-[8px] text-red-400 font-bold uppercase">Mnt</span>
                                        </div>
                                        <div class="bg-red-500/10 rounded-lg p-1 text-center">
                                            <span class="text-sm font-extrabold text-red-500 block leading-tight" x-text="seconds"></span>
                                            <span class="text-[8px] text-red-400 font-bold uppercase">Det</span>
                                        </div>
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

                {{-- Rundown Acara --}}
                @php $rundowns = \App\Models\EventRundown::with('sourceCategory.parent')->where('eventner_id', $eventner->id)->orderBy('sort_order')->take(3)->get(); @endphp
                @if($rundowns->isNotEmpty())
                    <div class="surface-card p-6">
                        <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                            <i class="ti ti-list-details text-primary"></i>
                            Rundown Acara
                        </h3>
                        <div class="flex flex-col gap-3">
                            @foreach($rundowns as $item)
                                <div class="flex gap-3 items-start border-b border-outline-variant/30 pb-3 last:border-0 last:pb-0">
                                    <div class="shrink-0 rounded-lg bg-primary/10 text-primary px-3 py-1.5 font-mono text-xs font-extrabold leading-normal text-center min-w-[92px]">
                                        {{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }}
                                        @if($item->end_time)
                                            <span class="text-primary/50">–</span> {{ \Carbon\Carbon::parse($item->end_time)->format('H:i') }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-deep-slate text-sm leading-normal">{{ $item->title }}</span>
                                        @if($item->description)
                                            <p class="text-xs text-on-surface-variant leading-normal mt-0.5">{{ $item->description }}</p>
                                        @endif
                                        @if($item->sourceCategory)
                                            <span class="inline-flex items-center rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 border border-amber-500/20 mt-1">
                                                <i class="ti ti-arrows-shuffle"></i> {{ $item->sourceCategory->parent?->name ? $item->sourceCategory->parent->name . ' — ' . $item->sourceCategory->name : $item->sourceCategory->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ event_url($eventner, 'rundown') }}"
                            class="mt-4 w-full py-2.5 px-4 rounded-xl bg-primary/10 hover:bg-primary/15 border border-primary/20 text-primary text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none transition">
                            <i class="ti ti-list-details"></i> Lihat Rundown Lengkap
                            <i class="ti ti-chevron-right"></i>
                        </a>
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
                        <a href="https://wa.me/?text={{ urlencode($eventner->nama_event . ' - ' . event_url($eventner, 'detail')) }}"
                            target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none !text-emerald-600 !border-emerald-500/30 hover:!bg-emerald-50">
                            <i class="ti ti-brand-whatsapp text-base"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(event_url($eventner, 'detail')) }}"
                            target="_blank" class="btn-ghost flex-1 py-2 px-3 text-xs font-bold leading-normal inline-flex items-center justify-center gap-1.5 text-decoration-none !text-blue-600 !border-blue-500/30 hover:!bg-blue-50">
                            <i class="ti ti-brand-facebook text-base"></i> Facebook
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sponsor & Media Partner (1 card, tiered logo size) --}}
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
                $tierSizes = [
                    'sponsor' => 'h-20 md:h-24',
                    'gold' => 'h-16 md:h-20',
                    'silver' => 'h-14 md:h-16',
                    'bronze' => 'h-12 md:h-14',
                    'medpart' => 'h-10 md:h-12',
                    'partner' => 'h-10 md:h-12',
                    'supporting' => 'h-8 md:h-10',
                ];
            @endphp
            <div class="mt-8">
                <div class="surface-card p-8">
                    <div class="text-center mb-8">
                        <h3 class="font-display text-lg font-bold text-deep-slate">Sponsor & Media Partner</h3>
                        <p class="text-sm text-on-surface-variant mt-1">Terimakasih kepada para sponsor dan media partner yang telah mendukung acara ini.</p>
                    </div>
                    <div class="flex flex-col items-center gap-8">
                        @foreach($typeOrders as $t)
                            @if(isset($sGrouped[$t]) && $sGrouped[$t]->count() > 0)
                                <div class="w-full">
                                    <span class="overline block text-center mb-4">{{ $typeLabels[$t] ?? $t }}</span>
                                    <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
                                        @foreach($sGrouped[$t] as $sponsor)
                                            @if($sponsor->link)
                                                <a href="{{ $sponsor->link }}" target="_blank" class="transition hover:opacity-80">
                                            @endif
                                            @if($sponsor->logo)
                                                <img src="{{ asset('storage/' . $sponsor->logo) }}" class="{{ $tierSizes[$t] ?? 'h-12' }} w-auto object-contain max-w-[200px]" alt="{{ $sponsor->name }}" loading="lazy">
                                            @else
                                                <span class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">{{ $sponsor->name }}</span>
                                            @endif
                                            @if($sponsor->link)
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="w-24 border-outline-variant/30">
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>


    {{-- ========== FLOATING REGISTER CTA (Fixed Bottom) ========== --}}
    @if(($eventner->registration_status ?? 'open') != 'closed')
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 max-w-sm w-[calc(100%-2rem)]">
            <a href="{{ event_url($eventner, 'register') }}" class="btn-primary py-3.5 px-6 font-bold text-sm w-full text-center shadow-lg hover:shadow-xl inline-flex justify-center text-decoration-none">
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
</div>
