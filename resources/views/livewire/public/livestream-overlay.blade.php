<div class="overlay-container flex flex-col overflow-hidden"
     style="background: #080b14;"
     x-data="clock"
     x-init="init()">
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('cFade', (items) => ({
            items, idx: 0,
            init() { if(this.items.length>1) setInterval(()=>{ this.idx=(this.idx+1)%this.items.length }, 5000); }
        }));
        Alpine.data('clock', () => ({
            time: '', date: '',
            init() {
                this.tick();
                setInterval(() => this.tick(), 1000);
            },
            tick() {
                const d = new Date();
                this.time = [d.getHours(), d.getMinutes(), d.getSeconds()]
                    .map(n => String(n).padStart(2,'0')).join(':');
                const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                this.date = days[d.getDay()] + ', ' + d.toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'});
            }
        }));
    });
</script>

<style>
    @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    @keyframes glowPulse { 0%, 100% { opacity: 0.4; } 50% { opacity: 0.8; } }
    @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
    .animate-glow { animation: glowPulse 2s ease-in-out infinite; }
    .shimmer-effect::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.03) 50%, transparent 100%);
        animation: shimmer 3s ease-in-out infinite;
    }
    .leaderboard-scroll::-webkit-scrollbar { width: 3px; }
    .leaderboard-scroll::-webkit-scrollbar-track { background: transparent; }
    .leaderboard-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 3px; }
</style>

    {{-- ============================================================ --}}
    {{-- HEADER --}}
    {{-- ============================================================ --}}
    <header class="shrink-0 flex items-center gap-5 px-10 h-[70px] relative overflow-hidden"
            style="background: #090c17; border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="absolute top-0 inset-x-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(var(--color-primary-rgb),0.3), transparent);"></div>
        <div class="absolute inset-0 opacity-20" style="background: radial-gradient(ellipse at 25% 50%, rgba(var(--color-primary-rgb),0.1) 0%, transparent 60%);"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="relative z-10 h-10 w-10 rounded-xl object-cover border border-white/10 shrink-0" style="box-shadow: 0 2px 12px rgba(0,0,0,0.5);">
        @else
            <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-xl text-white/35 border border-white/6 shrink-0" style="background: rgba(255,255,255,0.03);">
                <i class="ti ti-calendar-event text-lg"></i>
            </span>
        @endif

        <div class="relative z-10 flex-1 min-w-0">
            <h1 class="text-[17px] font-extrabold text-white leading-tight tracking-tight truncate" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue)
                <p class="text-[10px] text-white/30 font-medium truncate flex items-center gap-1">
                    <i class="ti ti-map-pin-filled text-[9px]" style="color: #ef4444;"></i> {{ $eventner->venue }}
                </p>
            @endif
        </div>

        <div class="relative z-10 flex items-center gap-5 shrink-0">
            <div class="flex items-center gap-2 rounded-full px-4 py-1.5 text-[10px] font-bold uppercase tracking-[0.15em]"
                 style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2);">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 animate-ping"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                </span>
                <span style="color: #f87171;">Live</span>
            </div>
            <div class="h-8 w-px" style="background: rgba(255,255,255,0.06);"></div>
            <div class="text-right">
                <div class="font-mono text-[22px] font-bold text-white tabular-nums leading-none tracking-tight" x-text="time"></div>
                <div class="text-[9px] text-white/20 font-medium leading-tight" x-text="date"></div>
            </div>
        </div>
    </header>

    {{-- ============================================================ --}}
    {{-- GREENSCREEN MODE --}}
    {{-- ============================================================ --}}
    @if($mode === 'greenscreen')
        <main class="flex-1 bg-[#00FF00] relative overflow-hidden">
            <div class="absolute inset-0 opacity-[0.02]" style="background-image: linear-gradient(rgba(255,255,255,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.3) 1px, transparent 1px); background-size: 80px 80px;"></div>
            <div class="absolute top-8 left-8 w-16 h-16 border-t-2 border-l-2 border-white/8 rounded-tl-2xl"></div>
            <div class="absolute top-8 right-8 w-16 h-16 border-t-2 border-r-2 border-white/8 rounded-tr-2xl"></div>
            <div class="absolute bottom-8 left-8 w-16 h-16 border-b-2 border-l-2 border-white/8 rounded-bl-2xl"></div>
            <div class="absolute bottom-8 right-8 w-16 h-16 border-b-2 border-r-2 border-white/8 rounded-br-2xl"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="inline-flex items-center gap-3 px-8 py-4 rounded-2xl" style="background: rgba(0,0,0,0.2); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.05);">
                    <i class="ti ti-camera text-xl" style="color: rgba(255,255,255,0.2);"></i>
                    <span class="font-display text-sm font-semibold tracking-[0.15em] uppercase" style="color: rgba(255,255,255,0.2);">Chroma Key</span>
                </div>
            </div>
        </main>

    {{-- ============================================================ --}}
    {{-- CUSTOM MODE --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'custom')
        <main class="flex-1 flex flex-col overflow-hidden"
              style="background: #0a0d1a; background-image: radial-gradient(ellipse at 50% 0%, rgba(var(--color-primary-rgb),0.06) 0%, transparent 60%);">

            <div class="flex-1 flex overflow-hidden">
                @if($overlaySetting?->show_vote_leaderboard)
                <div class="flex-1 flex flex-col p-8 overflow-hidden" wire:poll.10s="refreshVoteData">
                    {{-- Stats bar --}}
                    <div class="flex items-center justify-center gap-10 mb-8">
                        <div class="text-center px-8 py-4 rounded-2xl" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                            <span class="font-display text-2xl font-extrabold text-white leading-none">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                            <span class="text-[9px] font-bold text-white/25 uppercase tracking-[0.15em] block mt-1">Total Vote</span>
                        </div>
                        <div class="text-center px-8 py-4 rounded-2xl" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                            <span class="font-display text-2xl font-extrabold text-white leading-none">{{ count($topVoteData) }}</span>
                            <span class="text-[9px] font-bold text-white/25 uppercase tracking-[0.15em] block mt-1">Kontingen</span>
                        </div>
                    </div>

                    @if(count($topVoteData) > 0)
                        @php $maxV = $topVoteData[0]['total_votes'] ?? 1; @endphp
                        {{-- Top 3 podium --}}
                        <div class="flex items-end justify-center gap-8 flex-1 max-w-3xl mx-auto w-full mb-6">
                            @foreach(array_slice($topVoteData, 0, 3) as $i => $r)
                                @php $votes = $r['total_votes'] ?? 0; @endphp
                                <div class="flex flex-col items-center flex-1 {{ $i === 0 ? 'max-w-[200px]' : 'max-w-[170px]' }}">
                                    <div class="flex flex-col items-center">
                                        @if($i === 0)
                                            <div class="relative mb-2">
                                                <i class="ti ti-crown-filled text-xl absolute -top-2 left-1/2 -translate-x-1/2 z-10" style="color: #f59e0b; filter: drop-shadow(0 0 10px rgba(245,158,11,0.5));"></i>
                                                @if($r['logo_sekolah'])
                                                    <img src="{{ asset('storage/' . $r['logo_sekolah']) }}" class="h-20 w-20 rounded-full object-cover border-2 border-amber-400/40" style="box-shadow: 0 0 30px rgba(245,158,11,0.2);">
                                                @else
                                                    <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-amber-400/40 text-white/30" style="background: rgba(255,255,255,0.04); box-shadow: 0 0 30px rgba(245,158,11,0.2);"><i class="ti ti-school text-3xl"></i></span>
                                                @endif
                                            </div>
                                        @else
                                            @if($r['logo_sekolah'])
                                                <img src="{{ asset('storage/' . $r['logo_sekolah']) }}" class="h-14 w-14 rounded-full object-cover border-2 border-white/10 mb-2">
                                            @else
                                                <span class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-white/10 text-white/30 mb-2" style="background: rgba(255,255,255,0.04);"><i class="ti ti-school text-2xl"></i></span>
                                            @endif
                                        @endif
                                        <h3 class="text-xs font-bold text-white/80 text-center leading-tight line-clamp-2 mb-1">{{ $r['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-sm" style="color: {{ ['#f59e0b','#94a3b8','#38bdf8'][$i] }};">{{ number_format($votes, 0, ',', '.') }}</span>
                                        <span class="text-[9px] font-medium text-white/20">suara</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Rank 4+ list --}}
                        @if(count($topVoteData) > 3)
                        <div class="rounded-xl overflow-hidden max-w-3xl mx-auto w-full" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                            @foreach(array_slice($topVoteData, 3) as $i => $r)
                                @php $barPct = $maxV > 0 ? min((($r['total_votes'] ?? 0) / $maxV) * 100, 100) : 0; @endphp
                                <div class="relative flex items-center gap-3 px-5 py-2.5 overflow-hidden">
                                    <div class="absolute inset-0 opacity-30 pointer-events-none"><div class="h-full" style="width:{{ $barPct }}%; background:linear-gradient(90deg, rgba(var(--color-primary-rgb),0.12), transparent); transition: width 1s ease-out;"></div></div>
                                    <span class="relative text-xs font-bold text-white/25 w-6 text-center tabular-nums">{{ $i + 4 }}</span>
                                    <h4 class="relative text-xs font-bold text-white/65 flex-1 truncate">{{ $r['nama_sekolah'] }}</h4>
                                    <span class="relative text-xs font-bold" style="color: var(--color-primary);">{{ number_format($r['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    @else
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl mx-auto mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.04);">
                                    <i class="ti ti-heart-off text-xl text-white/20"></i>
                                </div>
                                <p class="text-sm text-white/25 font-medium">Belum ada data vote.</p>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                @if($overlaySetting?->show_kegiatan)
                <div class="w-[420px] shrink-0 flex flex-col p-6 overflow-hidden" style="border-left: 1px solid rgba(255,255,255,0.05);">
                    <div class="flex items-center gap-2 mb-5">
                        <span class="h-1 w-1 rounded-full" style="background: var(--color-primary);"></span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/25">Kegiatan</span>
                    </div>
                    <div class="flex flex-col gap-2 flex-1 overflow-y-auto leaderboard-scroll">
                        @forelse($categoriesData as $cat)
                            <div class="flex items-center justify-between px-4 py-3 rounded-xl" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                                <span class="text-xs font-medium text-white/60 truncate">{{ $cat->full_name }}</span>
                                <span class="text-xs font-bold shrink-0 ml-2" style="color: var(--color-primary);">{{ $cat->registrations_count ?? 0 }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-white/15">—</p>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

            @if($overlaySetting?->marquee_text)
            <div class="shrink-0 py-2.5 px-6 overflow-hidden relative" style="background: rgba(0,0,0,0.25); border-top: 1px solid rgba(255,255,255,0.04);">
                <div class="h-px absolute top-0 inset-x-0" style="background: linear-gradient(90deg, transparent, rgba(var(--color-primary-rgb),0.2), transparent);"></div>
                <div class="text-xs font-medium tracking-wide whitespace-nowrap" style="color: rgba(255,255,255,0.4); animation: marquee 25s linear infinite;">
                    {{ $overlaySetting->marquee_text }} &nbsp;&nbsp;✦&nbsp;&nbsp; {{ $overlaySetting->marquee_text }} &nbsp;&nbsp;✦&nbsp;&nbsp; {{ $overlaySetting->marquee_text }}
                </div>
            </div>
            @endif

            @if(count($overlayComments) > 0)
            <div class="shrink-0 flex items-center gap-3 h-[52px] px-6 relative" style="background: rgba(0,0,0,0.35); border-top: 1px solid rgba(255,255,255,0.04);"
                 x-data="cFade({{ json_encode(array_map(fn($c) => ['n'=>$c['voter_name'],'t'=>$c['comment'],'v'=>$c['votes_earned']??0], $overlayComments)) }})" x-init="init()">
                <span class="shrink-0 text-[9px] font-bold uppercase tracking-[0.15em] text-white/25 flex items-center gap-1.5">
                    <span class="h-1 w-1 rounded-full" style="background: #f59e0b;"></span> Pesan
                </span>
                <div class="flex-1 relative h-full overflow-hidden">
                    <template x-for="(c,i) in items" :key="i">
                        <div x-show="idx===i"
                             x-transition:enter="transition ease-out duration-600"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-600"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-4"
                             class="absolute inset-0 flex items-center gap-2 text-xs text-white/60">
                            <span class="font-bold text-white/85 truncate" x-text="c.n"></span>
                            <span class="text-white/20">—</span>
                            <span class="truncate italic" x-text="'“'+c.t+'”'"></span>
                            <span class="shrink-0 font-bold ml-auto" style="color: #f59e0b;" x-text="'♥ '+Number(c.v).toLocaleString('id-ID')"></span>
                        </div>
                    </template>
                </div>
            </div>
            @endif
        </main>

    {{-- ============================================================ --}}
    {{-- VOTE FULLSCREEN MODE --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'vote')
        <main class="flex-1 flex flex-col px-16 py-8 overflow-hidden" wire:poll.10s="refreshVoteData"
              style="background: #0a0d1a; background-image: radial-gradient(ellipse at 50% 0%, rgba(var(--color-primary-rgb),0.06) 0%, transparent 60%);">

            {{-- Stats bar --}}
            <div class="flex items-center justify-center gap-16 mb-8">
                <div class="text-center">
                    <span class="font-display text-4xl font-extrabold text-white leading-none">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    <span class="text-[10px] font-bold text-white/25 uppercase tracking-[0.15em] block mt-1">Total Vote</span>
                </div>
                <div class="h-14 w-px" style="background: rgba(255,255,255,0.06);"></div>
                <div class="text-center">
                    <span class="font-display text-4xl font-extrabold text-white leading-none">{{ count($topVoteData) }}</span>
                    <span class="text-[10px] font-bold text-white/25 uppercase tracking-[0.15em] block mt-1">Kontingen</span>
                </div>
                <div class="h-14 w-px" style="background: rgba(255,255,255,0.06);"></div>
                <div class="text-center">
                    <span class="font-display text-4xl font-extrabold text-white leading-none">{{ number_format($totalParticipants) }}</span>
                    <span class="text-[10px] font-bold text-white/25 uppercase tracking-[0.15em] block mt-1">Total Peserta</span>
                </div>
            </div>

            @if(count($topVoteData) > 0)
                @php $maxV = $topVoteData[0]['total_votes'] ?? 1; @endphp

                {{-- Podium --}}
                <div class="rounded-2xl mb-8 max-w-[1300px] mx-auto w-full overflow-hidden" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                    <div class="flex items-center justify-center gap-3 py-4 relative" style="background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <span class="h-1 w-1 rounded-full" style="background: #f59e0b;"></span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/35">Klasemen Vote</span>
                        <span class="h-1 w-1 rounded-full" style="background: #f59e0b;"></span>
                    </div>
                    <div class="px-12 py-8">
                        <div class="flex items-end justify-center gap-10 max-w-2xl mx-auto">
                            {{-- 2nd Place --}}
                            <div class="flex flex-col items-center flex-1 max-w-[190px]">
                                @if(isset($topVoteData[1]))
                                    @php $r2 = $topVoteData[1]; @endphp
                                    <div class="flex flex-col items-center">
                                        @if($r2['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $r2['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-white/10 mb-2" style="box-shadow: 0 0 30px rgba(148,163,184,0.12);">
                                        @else
                                            <span class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/10 text-white/25 mb-2" style="background: rgba(255,255,255,0.03);"><i class="ti ti-school text-2xl"></i></span>
                                        @endif
                                        <h3 class="text-xs font-bold text-white/80 text-center leading-tight line-clamp-2 mb-1.5 max-w-[160px]">{{ $r2['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-lg text-white/50 mb-2">{{ number_format($r2['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <div class="w-full rounded-t-xl flex items-center justify-center" style="height: 90px; background: linear-gradient(180deg, rgba(148,163,184,0.15) 0%, rgba(148,163,184,0.03) 100%); border: 1px solid rgba(148,163,184,0.08);">
                                            <span class="font-display text-4xl font-extrabold text-white/12">2</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- 1st Place --}}
                            <div class="flex flex-col items-center flex-1 max-w-[220px]">
                                @if(isset($topVoteData[0]))
                                    @php $r1 = $topVoteData[0]; @endphp
                                    <div class="flex flex-col items-center">
                                        <div class="relative mb-2">
                                            <i class="ti ti-crown-filled text-2xl absolute -top-4 left-1/2 -translate-x-1/2 z-10 animate-glow" style="color: #f59e0b; filter: drop-shadow(0 0 12px rgba(245,158,11,0.6));"></i>
                                            @if($r1['logo_sekolah'])
                                                <img src="{{ asset('storage/' . $r1['logo_sekolah']) }}" class="h-24 w-24 rounded-full object-cover border-2 border-amber-400/40" style="box-shadow: 0 0 40px rgba(245,158,11,0.2);">
                                            @else
                                                <span class="flex h-24 w-24 items-center justify-center rounded-full border-2 border-amber-400/40 text-white/25" style="background: rgba(255,255,255,0.03); box-shadow: 0 0 40px rgba(245,158,11,0.2);"><i class="ti ti-school text-3xl"></i></span>
                                            @endif
                                        </div>
                                        <h3 class="text-sm font-bold text-white text-center leading-tight line-clamp-2 mb-1.5 max-w-[200px]">{{ $r1['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-xl text-amber-400 mb-2">{{ number_format($r1['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <div class="w-full rounded-t-xl flex items-center justify-center" style="height: 130px; background: linear-gradient(180deg, rgba(245,158,11,0.2) 0%, rgba(245,158,11,0.04) 100%); border: 1px solid rgba(245,158,11,0.12);">
                                            <span class="font-display text-5xl font-extrabold text-amber-400/25">1</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- 3rd Place --}}
                            <div class="flex flex-col items-center flex-1 max-w-[190px]">
                                @if(isset($topVoteData[2]))
                                    @php $r3 = $topVoteData[2]; @endphp
                                    <div class="flex flex-col items-center">
                                        @if($r3['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $r3['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-white/10 mb-2" style="box-shadow: 0 0 30px rgba(56,189,248,0.12);">
                                        @else
                                            <span class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/10 text-white/25 mb-2" style="background: rgba(255,255,255,0.03);"><i class="ti ti-school text-2xl"></i></span>
                                        @endif
                                        <h3 class="text-xs font-bold text-white/80 text-center leading-tight line-clamp-2 mb-1.5 max-w-[160px]">{{ $r3['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-lg text-white/50 mb-2">{{ number_format($r3['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <div class="w-full rounded-t-xl flex items-center justify-center" style="height: 70px; background: linear-gradient(180deg, rgba(56,189,248,0.15) 0%, rgba(56,189,248,0.03) 100%); border: 1px solid rgba(56,189,248,0.08);">
                                            <span class="font-display text-4xl font-extrabold text-white/12">3</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rank 4+ --}}
                @php $rest = array_slice($topVoteData, 3); @endphp
                @if(count($rest) > 0)
                    <div class="rounded-2xl max-w-[1300px] mx-auto w-full flex-1 overflow-hidden" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                        <div class="divide-y leaderboard-scroll overflow-y-auto" style="border-color: rgba(255,255,255,0.03);">
                            @foreach($rest as $i => $reg)
                                @php $barPct = $maxV > 0 ? min((($reg['total_votes'] ?? 0) / $maxV) * 100, 100) : 0; @endphp
                                <div class="relative flex items-center gap-5 px-8 py-4 transition overflow-hidden" style="transition: background 0.3s;">
                                    <div class="absolute inset-0 opacity-25 pointer-events-none"><div class="h-full" style="width:{{ $barPct }}%; background:linear-gradient(90deg, rgba(var(--color-primary-rgb),0.1), transparent);"></div></div>
                                    <span class="relative shrink-0 w-10 text-center">
                                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full text-sm font-bold" style="background: rgba(255,255,255,0.03); color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.05);">{{ $i + 4 }}</span>
                                    </span>
                                    @if($reg['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="relative h-11 w-11 rounded-xl object-cover border border-white/5 shrink-0">
                                    @else
                                        <span class="relative flex h-11 w-11 items-center justify-center rounded-xl text-white/20 shrink-0" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);"><i class="ti ti-school text-lg"></i></span>
                                    @endif
                                    <div class="relative flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-white/75 leading-tight truncate">{{ $reg['nama_sekolah'] }}</h4>
                                    </div>
                                    <div class="relative shrink-0 text-right">
                                        <span class="font-display font-extrabold text-lg" style="color: var(--color-primary);">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <span class="text-[9px] font-bold text-white/20 uppercase tracking-wider block">Vote</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-2xl mx-auto mb-5" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.04);">
                            <i class="ti ti-heart-off text-3xl text-white/15"></i>
                        </div>
                        <h3 class="font-display text-xl font-bold text-white/40 mb-2">Belum Ada Vote</h3>
                        <p class="text-sm text-white/20">Data vote akan muncul saat pemilih mulai memberikan dukungan.</p>
                    </div>
                </div>
            @endif
        </main>

    {{-- ============================================================ --}}
    {{-- KEGIATAN MODE --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'kegiatan')
        <main class="flex-1 flex flex-col items-center justify-center px-20"
              style="background: #0a0d1a; background-image: radial-gradient(ellipse at 50% 40%, rgba(var(--color-primary-rgb),0.05) 0%, transparent 60%);">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full mb-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                    <span class="h-1.5 w-1.5 rounded-full" style="background: var(--color-primary);"></span>
                    <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/30">Data Kegiatan</span>
                </div>
            </div>

            @if(count($categoriesData) > 0)
                <div class="grid grid-cols-2 gap-5 w-full max-w-[1500px] mb-10">
                    @foreach($categoriesData as $cat)
                        <div class="relative flex items-center gap-6 px-8 py-6 rounded-2xl overflow-hidden shimmer-effect"
                             style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                            <div class="relative flex-1 min-w-0">
                                <h3 class="font-display text-xl font-bold text-white/85 truncate">{{ $cat->full_name }}</h3>
                                @if($cat->parent)
                                    <p class="text-[11px] font-medium text-white/20 mt-0.5 truncate">{{ $cat->parent->name }}</p>
                                @endif
                            </div>
                            <div class="relative shrink-0 text-right">
                                <span class="font-display text-3xl font-extrabold" style="color: var(--color-primary);">{{ number_format($cat->registrations_count ?? 0) }}</span>
                                <span class="text-[9px] font-bold text-white/25 uppercase tracking-widest block mt-0.5">Kontingen</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="inline-flex items-center gap-5 px-12 py-6 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                    <span class="flex h-12 w-12 items-center justify-center rounded-xl" style="background: rgba(var(--color-primary-rgb),0.1);">
                        <i class="ti ti-users text-xl" style="color: var(--color-primary);"></i>
                    </span>
                    <div>
                        <span class="font-display font-extrabold text-4xl text-white leading-none">{{ number_format($totalParticipants) }}</span>
                        <span class="text-xs font-semibold text-white/25 block mt-1">Total Peserta</span>
                    </div>
                </div>
            @else
                <div class="text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl mx-auto mb-5" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.04);">
                        <i class="ti ti-folder-off text-3xl text-white/15"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold text-white/40 mb-2">Belum Ada Data</h3>
                    <p class="text-sm text-white/20">Kategori lomba belum tersedia.</p>
                </div>
            @endif
        </main>

    {{-- ============================================================ --}}
    {{-- FULL MODE (default) --}}
    {{-- ============================================================ --}}
    @else
        <main class="flex-1 flex overflow-hidden" style="background: #090c14;">
            {{-- LEFT: Greenscreen Area --}}
            <div class="flex-1 bg-[#00FF00] relative overflow-hidden">
                <div class="absolute inset-0 opacity-[0.025]" style="background-image: linear-gradient(rgba(255,255,255,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.3) 1px, transparent 1px); background-size: 60px 60px;"></div>

                {{-- Corner accents --}}
                <div class="absolute top-6 left-6 w-14 h-14 border-t-2 border-l-2 border-white/8 rounded-tl-xl"></div>
                <div class="absolute top-6 right-6 w-14 h-14 border-t-2 border-r-2 border-white/8 rounded-tr-xl"></div>
                <div class="absolute bottom-6 left-6 w-14 h-14 border-b-2 border-l-2 border-white/8 rounded-bl-xl"></div>
                <div class="absolute bottom-6 right-6 w-14 h-14 border-b-2 border-r-2 border-white/8 rounded-br-xl"></div>

                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <div class="inline-flex items-center gap-3 px-8 py-4 rounded-2xl mb-3" style="background: rgba(0,0,0,0.22); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.05);">
                            <i class="ti ti-camera text-xl" style="color: rgba(255,255,255,0.2);"></i>
                            <span class="font-display text-sm font-semibold tracking-[0.15em] uppercase" style="color: rgba(255,255,255,0.2);">Chroma Key Area</span>
                        </div>
                        @if($eventner->logo_event)
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-12 w-12 mx-auto opacity-15 rounded-xl object-cover">
                        @endif
                    </div>
                </div>
            </div>

            {{-- RIGHT: Leaderboard Panel --}}
            <aside class="w-[580px] shrink-0 flex flex-col overflow-hidden relative"
                    style="background: #0b0f1c; border-left: 1px solid rgba(255,255,255,0.05);"
                    wire:poll.10s="refreshVoteData">

                <div class="absolute inset-y-0 left-0 w-px" style="background: linear-gradient(180deg, transparent, rgba(var(--color-primary-rgb),0.25), transparent);"></div>

                {{-- Panel Header --}}
                <div class="shrink-0 px-6 py-5 relative overflow-hidden"
                     style="background: linear-gradient(180deg, rgba(17,24,39,1) 0%, rgba(17,24,39,0.5) 100%); border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div class="absolute inset-0 opacity-15" style="background: radial-gradient(circle at 15% 50%, rgba(var(--color-primary-rgb),0.12) 0%, transparent 50%);"></div>
                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl"
                                 style="background: linear-gradient(135deg, #f472b6, #ec4899); box-shadow: 0 4px 18px rgba(244,114,182,0.2);">
                                <i class="ti ti-heart-filled text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-display text-base font-extrabold text-white leading-tight tracking-tight">Pimpinan Vote</h3>
                                <p class="text-[10px] font-medium text-white/25 tracking-[0.1em] uppercase">{{ count($topVoteData) }} Kontingen</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-display text-2xl font-extrabold text-white leading-none tabular-nums" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                                {{ number_format($totalVoteCount, 0, ',', '.') }}
                            </span>
                            <span class="text-[9px] font-bold text-white/25 uppercase tracking-[0.15em] block mt-0.5">Total Suara</span>
                        </div>
                    </div>
                </div>

                {{-- Leaderboard List --}}
                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-1.5 leaderboard-scroll"
                     x-data="{ highlight: -1 }"
                     x-init="setInterval(() => { highlight = (highlight + 1) % {{ max(count($topVoteData), 1) }} }, 4000)">
                    @php $maxVotes = $topVoteData[0]['total_votes'] ?? 1; @endphp
                    @forelse($topVoteData as $i => $reg)
                        @php $rank = $i + 1; $votes = $reg['total_votes'] ?? 0; $barPct = $maxVotes > 0 ? min(($votes / $maxVotes) * 100, 100) : 0; @endphp
                        <div class="relative flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-700"
                             :class="highlight === {{ $i }} ? 'scale-[1.015]' : ''"
                             :style="highlight === {{ $i }}
                                ? 'background: rgba(var(--color-primary-rgb),0.08); border: 1px solid rgba(var(--color-primary-rgb),0.12); box-shadow: 0 0 20px rgba(var(--color-primary-rgb),0.04);'
                                : 'background: rgba(255,255,255,0.015); border: 1px solid transparent;'">

                            {{-- Progress bar --}}
                            <div class="absolute inset-0 rounded-xl overflow-hidden opacity-25 pointer-events-none" x-cloak>
                                <div class="h-full rounded-xl transition-all duration-1000 ease-out"
                                     :style="highlight === {{ $i }}
                                        ? 'width: {{ $barPct }}%; background: linear-gradient(90deg, rgba(var(--color-primary-rgb),0.12), rgba(var(--color-primary-rgb),0.03));'
                                        : 'width: {{ $barPct }}%; background: linear-gradient(90deg, rgba(255,255,255,0.025), transparent);'"></div>
                            </div>

                            {{-- Rank badge --}}
                            <div class="relative shrink-0 w-[34px] h-[34px] flex items-center justify-center">
                                @if($rank === 1)
                                    <div class="absolute inset-0 rounded-full animate-pulse" style="background: rgba(245,158,11,0.08); box-shadow: 0 0 12px rgba(245,158,11,0.1);"></div>
                                    <span class="relative flex h-full w-full items-center justify-center rounded-full text-xs font-extrabold"
                                          style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 2px 12px rgba(245,158,11,0.3);">
                                        <i class="ti ti-crown-filled text-[11px]"></i>
                                    </span>
                                @elseif($rank === 2)
                                    <span class="flex h-full w-full items-center justify-center rounded-full text-xs font-bold"
                                          style="background: linear-gradient(135deg, #94a3b8, #64748b); color: #fff; box-shadow: 0 2px 8px rgba(148,163,184,0.2);">{{ $rank }}</span>
                                @elseif($rank === 3)
                                    <span class="flex h-full w-full items-center justify-center rounded-full text-xs font-bold"
                                          style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); color: #fff; box-shadow: 0 2px 8px rgba(56,189,248,0.2);">{{ $rank }}</span>
                                @else
                                    <span class="flex h-full w-full items-center justify-center rounded-full text-[11px] font-bold"
                                          style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.05);">{{ $rank }}</span>
                                @endif
                            </div>

                            {{-- Logo --}}
                            <div class="relative shrink-0">
                                @if($reg['logo_sekolah'])
                                    <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="h-9 w-9 rounded-lg object-cover border border-white/8">
                                @else
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg text-white/25" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.05);">
                                        <i class="ti ti-school text-sm"></i>
                                    </span>
                                @endif
                            </div>

                            {{-- Name --}}
                            <div class="flex-1 min-w-0 relative">
                                <p class="text-[13px] font-bold text-white/85 truncate leading-tight">{{ $reg['nama_sekolah'] }}</p>
                            </div>

                            {{-- Votes --}}
                            <div class="relative shrink-0 min-w-[72px] text-right">
                                <span class="font-display text-sm font-extrabold tabular-nums leading-none"
                                      style="color: {{ $rank === 1 ? '#f59e0b' : ($rank === 2 ? '#94a3b8' : ($rank === 3 ? '#38bdf8' : 'var(--color-primary)')) }};">
                                    {{ number_format($votes, 0, ',', '.') }}
                                </span>
                                <span class="text-[8px] font-medium text-white/20 block leading-tight">suara</span>
                            </div>
                        </div>
                    @empty
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center py-16">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl mx-auto mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.03);">
                                    <i class="ti ti-heart-off text-2xl text-white/15"></i>
                                </div>
                                <p class="text-xs font-semibold text-white/20">Belum ada data vote</p>
                                <p class="text-[10px] text-white/12 mt-1">Menunggu dukungan masuk</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Kegiatan Footer --}}
                <div class="shrink-0 relative overflow-hidden" style="background: #080c15; border-top: 1px solid rgba(255,255,255,0.05);">
                    <div class="h-px" style="background: linear-gradient(90deg, transparent, rgba(var(--color-primary-rgb),0.35), transparent);"></div>
                    <div class="px-5 py-3">
                        <div class="flex items-center gap-2 text-[9px] font-bold uppercase tracking-[0.15em] text-white/20 mb-2">
                            <span class="h-1 w-1 rounded-full" style="background: var(--color-primary);"></span>
                            Kategori Kegiatan
                        </div>
                        <div class="flex flex-wrap gap-x-3 gap-y-1">
                            @forelse($categoriesData as $cat)
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-medium"
                                      style="background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.04); color: rgba(255,255,255,0.45);">
                                    {{ $cat->full_name }}
                                    <span class="font-bold" style="color: var(--color-primary);">{{ $cat->registrations_count ?? 0 }}</span>
                                </span>
                            @empty
                                <span class="text-[10px] text-white/15 font-medium">Belum ada kategori</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>
        </main>
    @endif

    {{-- ============================================================ --}}
    {{-- FOOTER --}}
    {{-- ============================================================ --}}
    <footer class="shrink-0 flex items-center justify-center h-[30px] relative" style="background: #060912; border-top: 1px solid rgba(255,255,255,0.03);">
        <div class="absolute top-0 inset-x-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(var(--color-primary-rgb),0.15), transparent);"></div>
        <span class="text-[9px] font-medium tracking-[0.1em]" style="color: rgba(255,255,255,0.15);">Powered by <strong class="font-bold" style="color: rgba(255,255,255,0.3);">BARIS APP</strong></span>
    </footer>
</div>
