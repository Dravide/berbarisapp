<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-16">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-list-details"></i>
                Susunan Acara
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                Rundown Acara
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Susunan jadwal lengkap event <strong class="text-secondary font-semibold">{{ $eventner->nama_event }}</strong> hari pelaksanaan.
            </p>
            <div class="mt-4">
                <a href="{{ event_url($eventner, 'detail') }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Kembali Ke Detail Event
                </a>
            </div>
        </div>
    </div>

    {{-- ========== QUICK STATS ========== --}}
    @php
        $rundowns = $eventner->eventRundowns->sortBy('sort_order')->values();
        $first = $rundowns->first();
        $last = $rundowns->filter(fn($r) => $r->end_time)->last();
        $totalDuration = $rundowns->sum('duration_minutes');
    @endphp
    <div class="container-landing -mt-8 relative z-20">
        <div class="surface-card p-6">
            <div class="grid gap-6 grid-cols-3">
                <div class="text-center">
                    <span class="text-3xl font-extrabold text-primary font-display block mb-1">{{ $rundowns->count() }}</span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Item</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-emerald-600 font-display block mb-1">
                        {{ $first?->start_time?->format('H:i') ?? '—' }}
                        @if($last?->end_time)
                            <span class="text-on-surface-variant/60 text-xl">–</span> {{ $last->end_time->format('H:i') }}
                        @endif
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Estimasi Waktu</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-amber-500 font-display block mb-1">
                        {{ $totalDuration > 0 ? floor($totalDuration / 60) . 'j ' . ($totalDuration % 60) . 'm' : '—' }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Durasi Tampil</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== JADWAL PERTANDINGAN ========== --}}
    {{-- Hanya render bila panitia mengisi jadwal — event lama tak berubah tampak. --}}
    @if($scheduleDays->isNotEmpty())
        <div class="container-landing pt-8">
            <div class="surface-card overflow-hidden">
                <div class="flex items-center justify-between bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                        <i class="ti ti-calendar-time text-primary text-lg"></i>
                        Jadwal Pertandingan
                    </h3>
                </div>
                @foreach($scheduleDays as $day)
                    <div class="border-b border-outline-variant/30 last:border-b-0">
                        <div class="px-6 py-2.5 bg-surface-container-low flex items-center gap-2">
                            <i class="ti ti-calendar text-primary"></i>
                            <span class="text-xs font-extrabold uppercase tracking-wider text-deep-slate">
                                {{ $day['tanggal']?->translatedFormat('l, d F Y') ?? 'Hari Pelaksanaan' }}
                            </span>
                        </div>
                        @foreach($day['venues'] as $venueGroup)
                            <div class="px-6 py-4">
                                @if($venueGroup['venue'])
                                    <div class="flex items-center gap-1.5 mb-2 text-xs font-bold text-on-surface-variant">
                                        <i class="ti ti-map-pin text-primary"></i> {{ $venueGroup['venue'] }}
                                    </div>
                                @endif
                                <div class="grid gap-2 md:grid-cols-2">
                                    @foreach($venueGroup['items'] as $s)
                                        <div class="flex items-start gap-3 rounded-lg border border-outline-variant/30 px-3 py-2.5 bg-surface-container-lowest">
                                            <span class="shrink-0 rounded bg-primary/10 text-primary px-2 py-1 font-mono text-[11px] font-extrabold leading-normal">
                                                {{ $s->start_time?->format('H:i') }}@if($s->end_time)<span class="text-primary/50">–</span>{{ $s->end_time->format('H:i') }}@endif
                                            </span>
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-deep-slate leading-tight">{{ $s->title ?? $s->category?->name }}</div>
                                                <div class="text-[11px] text-on-surface-variant mt-0.5">
                                                    {{ $s->category?->parent?->name ? $s->category->parent->name . ' — ' . $s->category->name : $s->category?->name }}
                                                    @if($s->group) · {{ $s->group->name }} @endif
                                                    @if($s->round) · {{ $s->round->name }} @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========== LIST RUNDOWN ========== --}} --}}
    <div class="container-landing py-8">
        @if($rundowns->isNotEmpty())
            <div class="surface-card overflow-hidden">
                <div class="flex items-center justify-between bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                        <i class="ti ti-list-details text-primary text-lg"></i>
                        Susunan Acara Lengkap
                    </h3>
                    <span class="chip py-0.5 px-2.5 text-xs font-bold leading-normal bg-primary/10">{{ $rundowns->count() }} item</span>
                </div>
                <div class="divide-y divide-outline-variant/30">
                    @foreach($rundowns as $i => $item)
                        <div class="flex items-start gap-4 px-6 py-4 hover:bg-surface-container-lowest transition duration-150">
                            {{-- Urutan --}}
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary text-xs font-extrabold border border-outline-variant/30 shrink-0">
                                {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            {{-- Badge jam --}}
                            <div class="shrink-0 rounded-lg bg-primary/10 text-primary px-3 py-1.5 font-mono text-xs font-extrabold leading-normal text-center min-w-[100px] border border-primary/10">
                                {{ $item->start_time?->format('H:i') }}
                                @if($item->end_time)
                                    <span class="text-primary/50">–</span> {{ $item->end_time->format('H:i') }}
                                @endif
                            </div>
                            {{-- Isi --}}
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-deep-slate leading-tight mb-0.5 flex items-center gap-2 flex-wrap">
                                    {{ $item->title }}
                                </h4>
                                @if($item->sourceCategory)
                                    <span class="inline-flex items-center rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 border border-amber-500/20">
                                        <i class="ti ti-arrows-shuffle"></i> {{ $item->sourceCategory->parent?->name ? $item->sourceCategory->parent->name . ' — ' . $item->sourceCategory->name : $item->sourceCategory->name }}
                                    </span>
                                @endif
                                @if($item->description)
                                    <p class="text-xs text-on-surface-variant leading-normal mt-1">{{ $item->description }}</p>
                                @endif
                            </div>
                            {{-- Durasi --}}
                            @if($item->duration_minutes)
                                <span class="chip py-1 px-3 !text-[11px] shrink-0">{{ $item->duration_minutes }} mnt</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="surface-card p-10 text-center">
                <i class="ti ti-list-details text-5xl text-on-surface-variant/30 block mb-3"></i>
                <h4 class="font-display text-base font-bold text-deep-slate mb-1">Rundown Belum Tersedia</h4>
                <p class="text-xs text-on-surface-variant">Susunan acara akan segera diperbarui oleh panitia.</p>
            </div>
        @endif
    </div>

</div>
