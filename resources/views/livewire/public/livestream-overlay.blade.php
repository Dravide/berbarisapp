<div class="w-[1920px] h-[1080px] flex flex-col overflow-hidden"
     style="background: linear-gradient(135deg, #0c0f1e 0%, #1a1f3a 50%, #0c0f1e 100%);"
     x-data="clock"
     x-init="init()">
<script>
    document.addEventListener('alpine:init', () => {
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

    {{-- Header --}}
    <header class="shrink-0 flex items-center gap-5 px-10 h-[68px] relative"
            style="background: linear-gradient(90deg, rgba(0,98,255,0.15) 0%, rgba(0,98,255,0.05) 100%); border-bottom: 1px solid rgba(255,255,255,0.06);">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, transparent 0%, rgba(0,240,255,0.03) 50%, transparent 100%);"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="relative z-10 h-10 w-10 rounded-xl object-cover border border-white/10 shadow-lg shrink-0">
        @else
            <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-xl bg-white/5 text-white/60 border border-white/10 shrink-0">
                <i class="ti ti-calendar-event text-lg"></i>
            </span>
        @endif

        <div class="relative z-10 flex-1 min-w-0">
            <h1 class="text-[17px] font-extrabold text-white leading-tight tracking-tight truncate" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue)
                <p class="text-[11px] text-white/40 font-medium truncate"><i class="ti ti-map-pin text-[9px]"></i> {{ $eventner->venue }}</p>
            @endif
        </div>

        <div class="relative z-10 flex items-center gap-4 shrink-0">
            <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-[11px] font-bold uppercase tracking-widest"
                  style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);">
                <span class="h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                LIVE
            </span>
            <div class="text-right">
                <div class="font-mono text-[22px] font-bold text-white tabular-nums leading-tight" x-text="time"></div>
                <div class="text-[10px] text-white/30 font-medium leading-tight" x-text="date"></div>
            </div>
        </div>
    </header>

    {{-- GREENSCREEN --}}
    @if($mode === 'greenscreen')
        <main class="flex-1 bg-[#00FF00] flex items-center justify-center"></main>

    {{-- VOTE FULLSCREEN --}}
    @elseif($mode === 'vote')
        <main class="flex-1 flex flex-col px-16 py-8 overflow-hidden" wire:poll.10s="refreshVoteData"
              style="background: radial-gradient(ellipse at 50% 0%, rgba(0,98,255,0.08) 0%, transparent 60%);">

            {{-- Stats --}}
            <div class="flex items-center justify-center gap-12 mb-8">
                <div class="text-center px-10 py-5 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                    <span class="font-display text-3xl font-extrabold text-transparent bg-clip-text" style="background-image: linear-gradient(135deg, #60a5fa, #818cf8);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    <span class="text-[11px] font-bold text-white/40 uppercase tracking-widest block mt-1">Total Vote</span>
                </div>
                <div class="w-px h-12" style="background: rgba(255,255,255,0.08);"></div>
                <div class="text-center px-10 py-5 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                    <span class="font-display text-3xl font-extrabold text-transparent bg-clip-text" style="background-image: linear-gradient(135deg, #f472b6, #fb923c);">{{ count($topVoteData) }}</span>
                    <span class="text-[11px] font-bold text-white/40 uppercase tracking-widest block mt-1">Kontingen</span>
                </div>
            </div>

            @if(count($topVoteData) > 0)
                {{-- Podium --}}
                <div class="rounded-2xl mb-8 max-w-[1200px] mx-auto w-full overflow-hidden" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                    <div class="flex items-center justify-center py-4" style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/50"><i class="ti ti-trophy" style="color: #f59e0b;"></i> Pimpinan</span>
                    </div>
                    <div class="px-10 py-8">
                        <div class="flex items-end justify-center gap-8 max-w-2xl mx-auto">
                            {{-- 2nd --}}
                            <div class="flex flex-col items-center flex-1 max-w-[180px]">
                                @if(isset($topVoteData[1]))
                                    @php $r2 = $topVoteData[1]; @endphp
                                    <div class="flex flex-col items-center">
                                        @if($r2['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $r2['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-white/10 shadow-lg mb-2" style="box-shadow: 0 0 30px rgba(148,163,184,0.15);">
                                        @else
                                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-white/40 border-2 border-white/10 mb-2"><i class="ti ti-school text-2xl"></i></span>
                                        @endif
                                        <h3 class="text-xs font-bold text-white/80 text-center leading-tight line-clamp-2 mb-1 max-w-[160px]">{{ $r2['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-white/60 text-sm mb-1">{{ number_format($r2['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <div class="w-full rounded-t-xl mt-2 flex items-center justify-center" style="height: 80px; background: linear-gradient(180deg, rgba(148,163,184,0.2) 0%, rgba(148,163,184,0.05) 100%); border: 1px solid rgba(148,163,184,0.1);">
                                            <span class="font-display text-3xl font-extrabold text-white/20">2</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- 1st --}}
                            <div class="flex flex-col items-center flex-1 max-w-[200px]">
                                @if(isset($topVoteData[0]))
                                    @php $r1 = $topVoteData[0]; @endphp
                                    <div class="flex flex-col items-center">
                                        <div class="relative mb-2">
                                            <i class="ti ti-crown-filled text-2xl absolute -top-3 left-1/2 -translate-x-1/2 z-10" style="color: #f59e0b; filter: drop-shadow(0 0 10px rgba(245,158,11,0.5));"></i>
                                            @if($r1['logo_sekolah'])
                                                <img src="{{ asset('storage/' . $r1['logo_sekolah']) }}" class="h-20 w-20 rounded-full object-cover border-2 border-amber-400/50 shadow-lg mb-2" style="box-shadow: 0 0 40px rgba(245,158,11,0.2);">
                                            @else
                                                <span class="flex h-20 w-20 items-center justify-center rounded-full bg-white/5 text-white/40 border-2 border-amber-400/50 mb-2" style="box-shadow: 0 0 40px rgba(245,158,11,0.2);"><i class="ti ti-school text-3xl"></i></span>
                                            @endif
                                        </div>
                                        <h3 class="text-sm font-bold text-white text-center leading-tight line-clamp-2 mb-1 max-w-[180px]">{{ $r1['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-amber-400 text-base mb-1">{{ number_format($r1['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <div class="w-full rounded-t-xl mt-2 flex items-center justify-center" style="height: 120px; background: linear-gradient(180deg, rgba(245,158,11,0.2) 0%, rgba(245,158,11,0.05) 100%); border: 1px solid rgba(245,158,11,0.15);">
                                            <span class="font-display text-4xl font-extrabold text-amber-400/40">1</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- 3rd --}}
                            <div class="flex flex-col items-center flex-1 max-w-[180px]">
                                @if(isset($topVoteData[2]))
                                    @php $r3 = $topVoteData[2]; @endphp
                                    <div class="flex flex-col items-center">
                                        @if($r3['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $r3['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover border-2 border-white/10 shadow-lg mb-2" style="box-shadow: 0 0 30px rgba(56,189,248,0.15);">
                                        @else
                                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/5 text-white/40 border-2 border-white/10 mb-2"><i class="ti ti-school text-2xl"></i></span>
                                        @endif
                                        <h3 class="text-xs font-bold text-white/80 text-center leading-tight line-clamp-2 mb-1 max-w-[160px]">{{ $r3['nama_sekolah'] }}</h3>
                                        <span class="font-display font-extrabold text-white/60 text-sm mb-1">{{ number_format($r3['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <div class="w-full rounded-t-xl mt-2 flex items-center justify-center" style="height: 60px; background: linear-gradient(180deg, rgba(56,189,248,0.2) 0%, rgba(56,189,248,0.05) 100%); border: 1px solid rgba(56,189,248,0.1);">
                                            <span class="font-display text-3xl font-extrabold text-white/20">3</span>
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
                    <div class="rounded-2xl max-w-[1200px] mx-auto w-full flex-1 overflow-hidden" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                        <div class="divide-y overflow-y-auto" style="border-color: rgba(255,255,255,0.04);">
                            @foreach($rest as $i => $reg)
                                <div class="flex items-center gap-5 px-8 py-4 hover:bg-white/[0.02] transition">
                                    <span class="shrink-0 w-10 text-center">
                                        <span class="inline-flex items-center justify-center h-10 w-10 rounded-full text-sm font-bold" style="background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.3); border: 1px solid rgba(255,255,255,0.06);">{{ $i + 4 }}</span>
                                    </span>
                                    @if($reg['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="h-12 w-12 rounded-xl object-cover border border-white/5 shrink-0">
                                    @else
                                        <span class="flex h-12 w-12 items-center justify-center rounded-xl text-white/30 shrink-0" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);"><i class="ti ti-school text-xl"></i></span>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-white/80 leading-tight truncate">{{ $reg['nama_sekolah'] }}</h4>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="font-display font-extrabold text-lg" style="color: #60a5fa;">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <span class="text-[10px] font-bold text-white/30 uppercase tracking-wider block">Vote</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center px-16 py-16 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <span class="flex h-16 w-16 items-center justify-center rounded-2xl text-white/30 mx-auto mb-4" style="background: rgba(255,255,255,0.05);"><i class="ti ti-heart-off text-3xl"></i></span>
                        <h3 class="font-display text-lg font-bold text-white/60 mb-2">Belum Ada Vote</h3>
                        <p class="text-sm text-white/30">Data vote akan muncul saat pemilih mulai memberikan dukungan.</p>
                    </div>
                </div>
            @endif
        </main>

    {{-- KEGIATAN --}}
    @elseif($mode === 'kegiatan')
        <main class="flex-1 flex flex-col items-center justify-center px-24"
              style="background: radial-gradient(ellipse at 50% 50%, rgba(0,98,255,0.06) 0%, transparent 60%);">
            <div class="text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-[0.25em] text-white/40"><i class="ti ti-calendar-stats"></i> Data Kegiatan</span>
            </div>

            @if(count($categoriesData) > 0)
                <div class="grid grid-cols-2 gap-6 w-full max-w-[1400px] mb-12">
                    @foreach($categoriesData as $cat)
                        <div class="flex items-center gap-6 px-8 py-6 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-display text-xl font-bold text-white/90 truncate">{{ $cat->full_name }}</h3>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="font-display text-3xl font-extrabold text-transparent bg-clip-text" style="background-image: linear-gradient(135deg, #60a5fa, #818cf8);">{{ number_format($cat->registrations_count ?? 0) }}</span>
                                <span class="text-[10px] font-bold text-white/30 uppercase tracking-widest block mt-0.5">Kontingen</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <span class="inline-flex items-center gap-4 px-10 py-5 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                    <i class="ti ti-users text-2xl" style="color: #60a5fa;"></i>
                    <span class="font-display font-extrabold text-3xl text-white">{{ number_format($totalParticipants) }}</span>
                    <span class="text-sm font-semibold text-white/40">Total Peserta</span>
                </span>
            @else
                <div class="text-center px-16 py-16 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl text-white/30 mx-auto mb-4" style="background: rgba(255,255,255,0.05);"><i class="ti ti-folder-off text-3xl"></i></span>
                    <h3 class="font-display text-lg font-bold text-white/60 mb-2">Belum Ada Data</h3>
                    <p class="text-sm text-white/30">Kategori lomba belum tersedia.</p>
                </div>
            @endif
        </main>

    {{-- MODE FULL --}}
    @else
        <main class="flex-1 flex overflow-hidden">
            {{-- LEFT: Greenscreen --}}
            <div class="flex-1 bg-[#00FF00] relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="inline-flex items-center gap-3 px-10 py-5 rounded-full backdrop-blur-sm" style="background: rgba(0,0,0,0.3); color: rgba(255,255,255,0.2);">
                        <i class="ti ti-camera text-2xl"></i>
                        <span class="font-display text-lg font-bold tracking-tight">CHROMA KEY</span>
                    </span>
                </div>
            </div>

            {{-- RIGHT: Panel --}}
            <aside class="w-[600px] shrink-0 flex flex-col overflow-hidden" style="background: linear-gradient(180deg, #0a0e1a 0%, #111827 100%); border-left: 1px solid rgba(255,255,255,0.06);" wire:poll.10s="refreshVoteData">

                {{-- Header Panel --}}
                <div class="shrink-0 px-6 py-5" style="background: linear-gradient(135deg, rgba(96,165,250,0.08), transparent); border-bottom: 1px solid rgba(255,255,255,0.06);">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-base font-bold text-white inline-flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg" style="background: linear-gradient(135deg, #f472b6, #ec4899);">
                                <i class="ti ti-heart-filled text-white text-sm"></i>
                            </span>
                            Pimpinan Vote
                        </h3>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold text-white/50">{{ count($topVoteData) }} kontingen</span>
                            <span class="text-sm font-bold text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">{{ number_format($totalVoteCount, 0, ',', '.') }} <span class="text-xs font-normal text-white/40">suara</span></span>
                        </div>
                    </div>
                </div>

                {{-- Leaderboard --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-2" x-data="{ highlight: 0 }" x-init="setInterval(() => highlight = (highlight + 1) % {{ count($topVoteData) }}, 3000)">
                    @forelse($topVoteData as $i => $reg)
                        @php $rank = $i + 1; @endphp
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-500"
                             :class="highlight === {{ $i }} ? 'scale-[1.02]' : ''"
                             :style="highlight === {{ $i }} ? 'background: rgba(96,165,250,0.12); border: 1px solid rgba(96,165,250,0.2);' : 'background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);'">

                            {{-- Rank Badge --}}
                            <div class="shrink-0 w-[36px] h-[36px] rounded-full flex items-center justify-center text-sm font-bold"
                                 style="{{ $rank === 1 ? 'background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; box-shadow: 0 0 20px rgba(245,158,11,0.3);' : ($rank === 2 ? 'background: linear-gradient(135deg, #94a3b8, #64748b); color: #fff;' : ($rank === 3 ? 'background: linear-gradient(135deg, #38bdf8, #0ea5e9); color: #fff;' : 'background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.4);')) }}">
                                @if($rank === 1)
                                    <i class="ti ti-crown-filled text-sm"></i>
                                @else
                                    {{ $rank }}
                                @endif
                            </div>

                            {{-- Logo / Initial --}}
                            @if($reg['logo_sekolah'])
                                <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="h-9 w-9 rounded-lg object-cover border border-white/10 shrink-0">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg text-white/40 shrink-0" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.06);">
                                    <i class="ti ti-school text-sm"></i>
                                </span>
                            @endif

                            {{-- School name --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white/90 truncate">{{ $reg['nama_sekolah'] }}</p>
                            </div>

                            {{-- Vote count with bar --}}
                            <div class="shrink-0 text-right">
                                <span class="font-display font-extrabold text-base" style="color: {{ $rank === 1 ? '#f59e0b' : ($rank === 2 ? '#94a3b8' : ($rank === 3 ? '#38bdf8' : '#60a5fa')) }};">
                                    {{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center py-12">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl text-white/30 mx-auto mb-3" style="background: rgba(255,255,255,0.05);"><i class="ti ti-heart-off text-xl"></i></span>
                                <p class="text-xs font-medium text-white/30">Belum ada data vote.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- Kegiatan bar --}}
                <div class="shrink-0 px-6 py-4" style="background: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.06);">
                    <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.15em] text-white/30 mb-2.5">
                        <i class="ti ti-list-details"></i> Kegiatan
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                        @forelse($categoriesData as $cat)
                            <span class="text-[11px] font-medium text-white/50 inline-flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full" style="background: #60a5fa;"></span>
                                {{ $cat->full_name }}
                                <span class="text-white/25 text-[10px]">({{ $cat->registrations_count ?? 0 }})</span>
                            </span>
                        @empty
                            <span class="text-[11px] text-white/30">—</span>
                        @endforelse
                    </div>
                </div>
            </aside>
        </main>
    @endif

    {{-- Footer --}}
    <footer class="shrink-0 flex items-center justify-center h-[32px]" style="background: rgba(12,15,30,0.9); border-top: 1px solid rgba(255,255,255,0.04);">
        <span class="text-[10px] font-medium text-white/20">powered by <strong class="text-white/40 font-semibold">BARIS APP</strong></span>
    </footer>
</div>
