<div class="w-[1920px] h-[1080px] flex flex-col bg-surface overflow-hidden"
    x-data="clock"
    x-init="init()">
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('clock', () => ({
            time: '',
            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },
            tick() {
                const d = new Date();
                this.time = [d.getHours(), d.getMinutes(), d.getSeconds()]
                    .map(n => String(n).padStart(2,'0')).join(':');
            }
        }));
    });
</script>

    {{-- ================================================================ --}}
    {{-- HEADER (shared)                                                  --}}
    {{-- ================================================================ --}}
    <header class="shrink-0 flex items-center gap-4 px-8 h-[60px] bg-gradient-to-r from-primary via-[#0053da] to-tertiary relative overflow-hidden">
        <div class="absolute -left-12 -top-12 h-32 w-32 rounded-full bg-white/5 blur-2xl"></div>
        <div class="absolute -right-12 -bottom-12 h-32 w-32 rounded-full bg-white/5 blur-2xl"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}"
                 class="relative z-10 h-9 w-9 rounded-lg object-cover border border-white/20 shrink-0">
        @else
            <span class="relative z-10 flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-white shrink-0">
                <i class="ti ti-calendar-event text-lg"></i>
            </span>
        @endif

        <div class="relative z-10 flex-1 min-w-0">
            <h1 class="font-display text-sm font-extrabold text-white leading-tight tracking-[-0.01em] truncate">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue)
                <p class="text-[10px] text-white/65 font-medium leading-tight truncate">
                    <i class="ti ti-map-pin text-[9px]"></i> {{ $eventner->venue }}
                </p>
            @endif
        </div>

        <div class="relative z-10 flex items-center gap-3 shrink-0">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white backdrop-blur border border-white/10">
                <span class="h-2 w-2 rounded-full bg-secondary animate-pulse-dot"></span>
                LIVE
            </span>
            <span class="font-mono text-lg font-bold text-white tabular-nums" x-text="time"></span>
        </div>
    </header>

    {{-- ================================================================ --}}
    {{-- MODE: GREENSCREEN                                                --}}
    {{-- ================================================================ --}}
    @if($mode === 'greenscreen')
        <main class="flex-1 bg-[#00FF00] flex items-center justify-center">
            <span class="inline-flex items-center gap-3 px-8 py-4 rounded-full bg-black/6 backdrop-blur-[2px] text-black/25">
                <i class="ti ti-camera text-3xl"></i>
                <span class="font-display text-lg font-bold tracking-tight">CHROMA KEY — CAMERA INPUT</span>
            </span>
        </main>

    {{-- ================================================================ --}}
    {{-- MODE: VOTE (fullscreen leaderboard)                               --}}
    {{-- ================================================================ --}}
    @elseif($mode === 'vote')
        <main class="flex-1 flex flex-col px-20 py-10 overflow-hidden" wire:poll.10s="refreshVoteData">

            {{-- Stats bar --}}
            <div class="flex items-center justify-center mb-8">
                <div class="surface-card px-10 py-4 inline-flex items-center gap-8">
                    <div class="text-center">
                        <span class="font-display text-2xl font-extrabold text-primary">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Total Vote</span>
                    </div>
                    <div class="w-px h-10 bg-outline-variant/30"></div>
                    <div class="text-center">
                        <span class="font-display text-2xl font-extrabold text-primary">{{ count($topVoteData) }}</span>
                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Kontingen</span>
                    </div>
                </div>
            </div>

            @if(count($topVoteData) > 0)
                {{-- Podium Top 3 --}}
                <div class="surface-card overflow-hidden mb-8 max-w-[1300px] mx-auto w-full">
                    <div class="flex items-center justify-center bg-surface-container px-6 py-3 border-b border-outline-variant/40">
                        <span class="overline text-[11px] justify-center">
                            <i class="ti ti-trophy text-secondary"></i> Pimpinan Klasemen
                        </span>
                    </div>
                    <div class="px-8 pt-8 pb-6">
                        <div class="flex items-end justify-center gap-6 max-w-xl mx-auto">

                            {{-- 2nd --}}
                            <div class="flex flex-col items-center flex-1 max-w-[160px]">
                                @if(isset($topVoteData[1]))
                                    @php $r2 = $topVoteData[1]; @endphp
                                    @if($r2['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $r2['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-slate-300 shadow-md mb-2">
                                    @else
                                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 border-2 border-slate-300 shadow-md mb-2">
                                            <i class="ti ti-school text-2xl"></i>
                                        </span>
                                    @endif
                                    <h3 class="text-xs font-bold text-deep-slate text-center leading-tight line-clamp-2 mb-1">{{ $r2['nama_sekolah'] }}</h3>
                                    <span class="font-display font-extrabold text-primary text-sm mb-1">{{ number_format($r2['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Vote</span>
                                    <div class="w-full bg-gradient-to-t from-slate-200 to-slate-100 border border-slate-200/80 rounded-t-xl mt-3 flex items-center justify-center" style="height: 90px;">
                                        <span class="font-display text-3xl font-extrabold text-slate-400">2</span>
                                    </div>
                                @else
                                    <div class="w-full bg-slate-50 border border-slate-200/50 rounded-t-xl" style="height: 90px;"></div>
                                @endif
                            </div>

                            {{-- 1st --}}
                            <div class="flex flex-col items-center flex-1 max-w-[180px]">
                                @if(isset($topVoteData[0]))
                                    @php $r1 = $topVoteData[0]; @endphp
                                    <div class="relative mb-2">
                                        <i class="ti ti-crown-filled text-amber-400 text-2xl absolute -top-3 left-1/2 -translate-x-1/2 z-10 drop-shadow-sm"></i>
                                        @if($r1['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $r1['logo_sekolah']) }}" class="h-20 w-20 rounded-full object-cover border-3 border-amber-400 shadow-lg ring-4 ring-amber-400/20">
                                        @else
                                            <span class="flex h-20 w-20 items-center justify-center rounded-full bg-amber-50 text-amber-500 border-3 border-amber-400 shadow-lg ring-4 ring-amber-400/20">
                                                <i class="ti ti-school text-3xl"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-sm font-bold text-deep-slate text-center leading-tight line-clamp-2 mb-1">{{ $r1['nama_sekolah'] }}</h3>
                                    <span class="font-display font-extrabold text-primary text-base mb-1">{{ number_format($r1['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Vote</span>
                                    <div class="w-full bg-gradient-to-t from-amber-300 to-amber-200 border border-amber-300/80 rounded-t-xl mt-3 flex items-center justify-center" style="height: 130px;">
                                        <span class="font-display text-4xl font-extrabold text-amber-500/80">1</span>
                                    </div>
                                @endif
                            </div>

                            {{-- 3rd --}}
                            <div class="flex flex-col items-center flex-1 max-w-[160px]">
                                @if(isset($topVoteData[2]))
                                    @php $r3 = $topVoteData[2]; @endphp
                                    @if($r3['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $r3['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-sky-300 shadow-md mb-2">
                                    @else
                                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-sky-50 text-sky-500 border-2 border-sky-300 shadow-md mb-2">
                                            <i class="ti ti-school text-2xl"></i>
                                        </span>
                                    @endif
                                    <h3 class="text-xs font-bold text-deep-slate text-center leading-tight line-clamp-2 mb-1">{{ $r3['nama_sekolah'] }}</h3>
                                    <span class="font-display font-extrabold text-primary text-sm mb-1">{{ number_format($r3['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Vote</span>
                                    <div class="w-full bg-gradient-to-t from-sky-200 to-sky-100 border border-sky-200/80 rounded-t-xl mt-3 flex items-center justify-center" style="height: 70px;">
                                        <span class="font-display text-3xl font-extrabold text-sky-400/80">3</span>
                                    </div>
                                @else
                                    <div class="w-full bg-sky-50 border border-sky-200/50 rounded-t-xl" style="height: 70px;"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rank 4+ --}}
                @php $rest = array_slice($topVoteData, 3); @endphp
                @if(count($rest) > 0)
                    <div class="surface-card overflow-hidden max-w-[1300px] mx-auto w-full flex-1">
                        <div class="divide-y divide-outline-variant/30 overflow-y-auto">
                            @foreach($rest as $i => $reg)
                                <div class="flex items-center gap-4 px-6 py-4">
                                    <span class="shrink-0 w-10 text-center">
                                        <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-surface-container border border-outline-variant/30 font-bold text-sm text-on-surface-variant">{{ $i + 4 }}</span>
                                    </span>
                                    @if($reg['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="h-11 w-11 rounded-xl object-cover border border-outline-variant/30 shadow-sm shrink-0">
                                    @else
                                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/5 text-primary border border-outline-variant/30 shrink-0">
                                            <i class="ti ti-school text-xl"></i>
                                        </span>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-deep-slate leading-tight truncate">{{ $reg['nama_sekolah'] }}</h4>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="font-display font-extrabold text-primary text-lg">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Vote</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            @else
                <div class="surface-card p-16 text-center flex-1 flex flex-col items-center justify-center max-w-[600px] mx-auto w-full">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary mb-4">
                        <i class="ti ti-heart-off text-3xl"></i>
                    </span>
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Belum Ada Vote</h3>
                    <p class="text-sm text-on-surface-variant">Data vote akan muncul saat pemilih mulai memberikan dukungan.</p>
                </div>
            @endif
        </main>

    {{-- ================================================================ --}}
    {{-- MODE: KEGIATAN (fullscreen)                                       --}}
    {{-- ================================================================ --}}
    @elseif($mode === 'kegiatan')
        <main class="flex-1 flex flex-col items-center justify-center px-20">
            <div class="text-center mb-10">
                <span class="overline text-sm justify-center mb-3">
                    <i class="ti ti-calendar-stats"></i> Data Kegiatan
                </span>
            </div>

            @if(count($categoriesData) > 0)
                <div class="grid grid-cols-2 gap-5 w-full max-w-[1500px] mb-10">
                    @foreach($categoriesData as $cat)
                        <div class="surface-card p-6 flex items-center gap-5">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-display text-xl font-bold text-deep-slate truncate">{{ $cat->name }}</h3>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="font-display text-3xl font-extrabold text-primary">{{ number_format($cat->registrations_count ?? 0) }}</span>
                                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block mt-0.5">Kontingen</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <span class="inline-flex items-center gap-3 px-8 py-4 surface-card">
                    <i class="ti ti-users text-primary text-xl"></i>
                    <span class="font-display font-extrabold text-2xl text-deep-slate">{{ number_format($totalParticipants) }}</span>
                    <span class="text-sm font-semibold text-on-surface-variant">Total Peserta</span>
                </span>
            @else
                <div class="surface-card p-16 text-center max-w-[600px] w-full">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary mb-4 mx-auto">
                        <i class="ti ti-folder-off text-3xl"></i>
                    </span>
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Belum Ada Data</h3>
                    <p class="text-sm text-on-surface-variant">Kategori lomba belum tersedia untuk event ini.</p>
                </div>
            @endif
        </main>

    {{-- ================================================================ --}}
    {{-- MODE: FULL (greenscreen kiri + panel kanan)                       --}}
    {{-- ================================================================ --}}
    @else
        <main class="flex-1 flex overflow-hidden">
            {{-- LEFT: Greenscreen --}}
            <div class="flex-1 bg-[#00FF00] flex items-center justify-center">
                <span class="inline-flex items-center gap-3 px-8 py-4 rounded-full bg-black/6 backdrop-blur-[2px] text-black/25">
                    <i class="ti ti-camera text-3xl"></i>
                    <span class="font-display text-lg font-bold tracking-tight">CHROMA KEY</span>
                </span>
            </div>

            {{-- RIGHT: Panel --}}
            <aside class="w-[600px] shrink-0 flex flex-col bg-surface-container-low border-l border-outline-variant/40 overflow-hidden" wire:poll.10s="refreshVoteData">

                {{-- Vote leaderboard --}}
                <div class="flex-1 overflow-y-auto px-5 pt-5 pb-2">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 tracking-[-0.01em]">
                            <i class="ti ti-heart-filled text-secondary text-base"></i> Pimpinan Vote
                        </h3>
                        <span class="text-[11px] font-semibold text-on-surface-variant">{{ number_format($totalVoteCount, 0, ',', '.') }} total</span>
                    </div>

                    @if(count($topVoteData) > 0)
                        @foreach($topVoteData as $i => $reg)
                            @php $rank = $i + 1; @endphp
                            @if($rank <= 3)
                                @php
                                    $styles = [
                                        1 => ['row' => 'bg-amber-500/5 border-amber-400/20', 'icon_bg' => 'bg-amber-500/15 text-amber-600', 'icon' => 'ti-crown-filled'],
                                        2 => ['row' => 'bg-slate-100 border-slate-300/30', 'icon_bg' => 'bg-slate-400/15 text-slate-500', 'icon' => 'ti-medal'],
                                        3 => ['row' => 'bg-sky-50 border-sky-300/30', 'icon_bg' => 'bg-sky-400/15 text-sky-500', 'icon' => 'ti-medal'],
                                    ][$rank];
                                @endphp
                                <div class="flex items-center gap-3 px-4 py-3 rounded-xl border {{ $styles['row'] }} mb-2">
                                    <span class="flex items-center justify-center w-9 h-9 rounded-full {{ $styles['icon_bg'] }} shrink-0">
                                        <i class="ti {{ $styles['icon'] }} text-sm"></i>
                                    </span>
                            @else
                                <div class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-surface-container transition mb-1">
                                    <span class="w-8 text-center text-xs font-bold text-on-surface-variant shrink-0">{{ $rank }}</span>
                            @endif
                                    <span class="flex-1 min-w-0 text-xs font-semibold text-deep-slate truncate">{{ $reg['nama_sekolah'] }}</span>
                                    <span class="font-display font-bold text-xs text-primary shrink-0">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                        @endforeach
                    @else
                        <div class="text-center py-16">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary mb-3 mx-auto">
                                <i class="ti ti-heart-off text-xl"></i>
                            </span>
                            <p class="text-xs font-medium text-on-surface-variant">Belum ada data vote.</p>
                        </div>
                    @endif
                </div>

                {{-- Kegiatan bar --}}
                <div class="shrink-0 border-t border-outline-variant/40 px-5 py-3 bg-white">
                    <span class="overline text-[10px] mb-2">
                        <i class="ti ti-list-details"></i> Kegiatan
                    </span>
                    <div class="flex flex-wrap gap-x-3 gap-y-1">
                        @forelse($categoriesData as $cat)
                            <span class="text-[11px] font-medium text-on-surface inline-flex items-center gap-1">
                                <i class="ti ti-circle-filled text-[5px] text-primary"></i>
                                {{ $cat->name }}&nbsp;<span class="text-on-surface-variant">({{ $cat->registrations_count ?? 0 }})</span>
                            </span>
                        @empty
                            <span class="text-[11px] text-on-surface-variant">—</span>
                        @endforelse
                    </div>
                </div>
            </aside>
        </main>
    @endif

    {{-- ================================================================ --}}
    {{-- FOOTER (shared)                                                  --}}
    {{-- ================================================================ --}}
    <footer class="shrink-0 flex items-center justify-center bg-white border-t border-outline-variant/30 h-[32px]">
        <span class="text-[10px] font-medium text-on-surface-variant/60">
            powered by <strong class="text-primary/70 font-semibold">BARIS APP</strong>
        </span>
    </footer>

</div>
