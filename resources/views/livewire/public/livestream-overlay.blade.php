<div class="overlay-container flex flex-col overflow-hidden overlay-root"
     style="background: #f6f5f1; color: #15171c;"
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
    /* ====== Tema editorial terang (satu keluarga dengan videotron):
               kertas gading + tinta + aksen tema, panel garis rambut. ====== */
    .overlay-root {
        --ov-ink: #15171c;
        --ov-paper: #f6f5f1;
        --ov-line: #dcd9ce;
        --ov-muted: #6d7178;
        --ov-red: #c8102e;
        --ov-amber: #b45309;
    }

    @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    @keyframes beat { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
    @keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes bar { from { transform: scaleX(0); } to { transform: scaleX(1); } }

    .ov-beat { animation: beat 1.6s ease-in-out infinite; }
    .ov-rise { animation: rise .55s cubic-bezier(.2,.8,.2,1) both; }
    .ov-bar { transform-origin: left; animation: bar 1s cubic-bezier(.2,.8,.2,1) both; }
    .ov-stagger > * { animation: rise .5s cubic-bezier(.2,.8,.2,1) both; animation-delay: calc(var(--i, 0) * 80ms); }

    .ov-kicker {
        text-transform: uppercase;
        letter-spacing: .28em;
        font-weight: 700;
    }
    .ov-num { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric: tabular-nums; }

    /* Panel kertas: putih, garis rambut, sudut hampir tajam. */
    .ov-panel { background: #fff; border: 1px solid var(--ov-line); border-radius: 10px; }

    .leaderboard-scroll::-webkit-scrollbar { width: 4px; }
    .leaderboard-scroll::-webkit-scrollbar-track { background: transparent; }
    .leaderboard-scroll::-webkit-scrollbar-thumb { background: rgba(21,23,28,.2); border-radius: 8px; }
</style>

    {{-- ============================================================ --}}
    {{-- HEADER (full mode: bar kertas dengan garis aksen; lainnya idem) --}}
    {{-- ============================================================ --}}
    @if($mode === 'full')
    <header class="shrink-0 flex items-center gap-5 px-10 h-[80px] relative overflow-hidden bg-white" style="border-bottom: 1px solid var(--ov-line);">
        <div class="absolute top-0 inset-x-0 h-[3px]" style="background: var(--color-primary);"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-12 w-12 rounded-lg object-cover shrink-0" style="border: 1px solid var(--ov-ink);">
        @else
            <span class="flex h-12 w-12 items-center justify-center rounded-lg shrink-0" style="background: #fff; border: 1px solid var(--ov-ink); color: var(--ov-ink);">
                <i class="ti ti-calendar-event text-xl"></i>
            </span>
        @endif

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="ov-kicker text-[10px]" style="color: var(--color-primary);">Live Streaming</span>
                @if($eventner->diselenggarakan_oleh)
                    <span class="text-[10px] font-medium" style="color: var(--ov-muted);">— {{ \Illuminate\Support\Str::limit($eventner->diselenggarakan_oleh, 40) }}</span>
                @endif
            </div>
            <h1 class="font-display text-xl font-extrabold tracking-tight truncate leading-tight" style="color: var(--ov-ink);">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue || $eventner->tanggal)
                <p class="text-[11px] font-medium truncate flex items-center gap-3" style="color: var(--ov-muted);">
                    @if($eventner->venue)
                        <span class="inline-flex items-center gap-1"><i class="ti ti-map-pin text-[11px]" style="color: var(--color-primary);"></i>{{ \Illuminate\Support\Str::limit($eventner->venue, 50) }}</span>
                    @endif
                    @if($eventner->tanggal)
                        <span class="inline-flex items-center gap-1"><i class="ti ti-calendar text-[11px]" style="color: var(--color-primary);"></i>{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</span>
                    @endif
                </p>
            @endif
        </div>

        <div class="flex items-center gap-5 shrink-0">
            <div class="flex items-center gap-2 px-4 py-1.5 text-white">
                <span class="h-2 w-2 bg-white ov-beat"></span>
                <span class="ov-kicker text-[10px]">Live</span>
            </div>
            <div class="h-7 w-px" style="background: var(--ov-line);"></div>
            <div class="text-right">
                <div class="ov-num text-[24px] font-bold leading-none tracking-tight" style="color: var(--ov-ink);" x-text="time"></div>
                <div class="text-[10px] font-medium leading-tight mt-1" style="color: var(--ov-muted);" x-text="date"></div>
            </div>
        </div>
    </header>
    @else
    <header class="shrink-0 flex items-center gap-5 px-10 h-[74px] relative overflow-hidden bg-white" style="border-bottom: 1px solid var(--ov-line);">
        <div class="absolute top-0 inset-x-0 h-[3px]" style="background: var(--color-primary);"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="relative z-10 h-11 w-11 rounded-lg object-cover shrink-0" style="border: 1px solid var(--ov-ink);">
        @else
            <span class="relative z-10 flex h-11 w-11 items-center justify-center rounded-lg shrink-0" style="background: var(--ov-paper); color: var(--ov-muted); border: 1px solid var(--ov-line);">
                <i class="ti ti-calendar-event text-lg"></i>
            </span>
        @endif

        <div class="relative z-10 flex-1 min-w-0">
            <h1 class="font-display text-[18px] font-extrabold leading-tight tracking-tight truncate" style="color: var(--ov-ink);">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue)
                <p class="text-[11px] font-medium truncate flex items-center gap-1.5" style="color: var(--ov-muted);">
                    <i class="ti ti-map-pin text-[11px]" style="color: var(--color-primary);"></i> {{ $eventner->venue }}
                </p>
            @endif
        </div>

        <div class="relative z-10 flex items-center gap-5 shrink-0">
            <div class="flex items-center gap-2 px-4 py-1.5 text-white" style="background: var(--ov-red);">
                <span class="h-2 w-2 bg-white ov-beat"></span>
                <span class="ov-kicker text-[10px]">Live</span>
            </div>
            <div class="h-8 w-px" style="background: var(--ov-line);"></div>
            <div class="text-right">
                <div class="ov-num text-[24px] font-bold leading-none tracking-tight" style="color: var(--ov-ink);" x-text="time"></div>
                <div class="text-[10px] font-medium leading-tight mt-1" style="color: var(--ov-muted);" x-text="date"></div>
            </div>
        </div>
    </header>
    @endif

    {{-- ============================================================ --}}
    {{-- GREENSCREEN MODE --}}
    {{-- ============================================================ --}}
    @if($mode === 'greenscreen')
        <main class="flex-1 bg-[#00FF00] relative overflow-hidden">
            <div class="absolute inset-0 opacity-[0.02]" style="background-image: linear-gradient(rgba(255,255,255,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.3) 1px, transparent 1px); background-size: 80px 80px;"></div>
            <div class="absolute top-8 left-8 w-16 h-16 border-t-2 border-l-2 border-white/8"></div>
            <div class="absolute top-8 right-8 w-16 h-16 border-t-2 border-r-2 border-white/8"></div>
            <div class="absolute bottom-8 left-8 w-16 h-16 border-b-2 border-l-2 border-white/8"></div>
            <div class="absolute bottom-8 right-8 w-16 h-16 border-b-2 border-r-2 border-white/8"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="inline-flex items-center gap-3 px-8 py-4" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05);">
                    <i class="ti ti-camera text-xl" style="color: rgba(255,255,255,0.2);"></i>
                    <span class="ov-kicker text-sm" style="color: rgba(255,255,255,0.2);">Chroma Key</span>
                </div>
            </div>
        </main>

    {{-- ============================================================ --}}
    {{-- COMMENTS MODE — komentar vote saja untuk OBS browser source --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'comments')
        <main class="flex-1 flex flex-col justify-end items-stretch overflow-hidden" wire:poll.10s="refreshVoteData"
              style="background: var(--ov-paper);">
            @php $comsC = array_slice($overlayComments ?? [], 0, 6); @endphp
            <div class="flex flex-col-reverse gap-2.5 px-16 pb-10 max-w-[1100px] w-full mx-auto">
                @forelse($comsC as $c)
                    @php $initial = strtoupper(mb_substr(trim($c['voter_name'] ?? '?'), 0, 1)); @endphp
                    <div class="flex items-start gap-3 px-5 py-3 self-start ov-panel ov-rise"
                         style="border-left: 3px solid var(--color-primary); max-width: 720px; box-shadow: 5px 5px 0 rgba(var(--color-primary-rgb),0.85);">
                        <span class="shrink-0 flex items-center justify-center w-9 h-9 ov-num text-sm font-bold" style="background: var(--ov-ink); color: #fff;">{{ $initial }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm font-bold truncate" style="color: var(--ov-ink);">{{ $c['voter_name'] }}</span>
                                <span class="shrink-0 ov-num text-[10px] font-bold px-1.5 py-0.5 text-white" style="background: var(--ov-red);">+{{ number_format($c['votes_earned'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-0.5 text-sm leading-relaxed" style="color: #3d4048;">"{{ $c['comment'] }}"</p>
                        </div>
                    </div>
                @empty
                    <div class="self-start px-5 py-3 ov-panel">
                        <p class="text-sm italic" style="color: var(--ov-muted);">Belum ada komentar dukungan</p>
                    </div>
                @endforelse
            </div>
        </main>

    {{-- ============================================================ --}}
    {{-- CATEGORY MODE — rank vote + komentar per kategori --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'category')
        <main class="flex-1 flex flex-col px-12 py-6 overflow-hidden" wire:poll.10s="refreshVoteData"
              style="background: var(--ov-paper);">

            {{-- Header: nama kategori + total vote kategori --}}
            <div class="shrink-0 flex items-center gap-5 mb-5">
                <span class="flex items-center justify-center w-12 h-12 shrink-0" style="background: var(--ov-ink); color: #fff; border-radius: 8px;">
                    <i class="ti ti-award text-2xl"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <span class="ov-kicker text-[10px] block mb-1" style="color: var(--color-primary);">Klasemen Vote Kategori</span>
                    <h1 class="font-display text-2xl font-extrabold leading-tight truncate" style="color: var(--ov-ink);">
                        {{ $selectedCategory?->parent ? $selectedCategory->parent->name . ' — ' : '' }}{{ $selectedCategory?->name ?? 'Kategori' }}
                    </h1>
                </div>
                <div class="text-right shrink-0">
                    <span class="ov-num text-3xl font-extrabold leading-none" style="color: var(--ov-ink);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    <span class="ov-kicker text-[9px] block mt-1" style="color: var(--ov-muted);">Total Vote</span>
                </div>
            </div>

            <div class="flex-1 flex gap-5 overflow-hidden min-h-0">
                {{-- Kiri: Leaderboard top 10 kategori --}}
                <div class="flex-1 flex flex-col overflow-hidden ov-panel">
                    <div class="shrink-0 flex items-center gap-2.5 px-6 py-3" style="border-bottom: 1px solid var(--ov-line);">
                        <i class="ti ti-trophy text-sm" style="color: var(--ov-amber);"></i>
                        <span class="ov-kicker text-[10px]" style="color: var(--ov-ink);">Peringkat</span>
                        <span class="ov-kicker text-[9px] px-2 py-0.5 ml-auto text-white" style="background: var(--ov-ink);">TOP {{ min(count($topVoteData), 10) }}</span>
                    </div>
                    @php
                        $topCat = array_slice($topVoteData, 0, 10);
                        $maxCatV = max($topCat[0]['total_votes'] ?? 1, 1);
                    @endphp
                    <div class="flex-1 overflow-y-auto divide-y leaderboard-scroll" style="border-color: var(--ov-line);">
                        @forelse($topCat as $i => $reg)
                            @php
                                $rank = $i + 1;
                                $votes = $reg['total_votes'] ?? 0;
                                $barPct = min(($votes / $maxCatV) * 100, 100);
                                $medal = $rank === 1 ? '#b45309' : ($rank === 2 ? '#8a6d1f' : ($rank === 3 ? '#4d6a8a' : null));
                            @endphp
                            <div class="relative flex items-center gap-4 px-6 py-3 overflow-hidden">
                                <div class="absolute inset-y-0 left-0 pointer-events-none ov-bar" style="width: {{ $barPct }}%; background: rgba(var(--color-primary-rgb),0.08);"></div>
                                <span class="relative shrink-0 inline-flex items-center justify-center w-9 h-9 ov-num text-sm font-bold"
                                      style="{{ $medal ? 'background: ' . $medal . '; color: #fff;' : 'background: var(--ov-paper); color: var(--ov-muted); border: 1px solid var(--ov-line);' }}">
                                    @if($rank === 1)<i class="ti ti-crown-filled"></i>@else{{ $rank }}@endif
                                </span>
                                @if($reg['logo_sekolah'])
                                    <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="relative h-10 w-10 rounded-md object-cover shrink-0" style="border: 1px solid var(--ov-line);">
                                @else
                                    <span class="relative flex h-10 w-10 items-center justify-center rounded-md shrink-0" style="color: var(--ov-muted); background: var(--ov-paper); border: 1px solid var(--ov-line);"><i class="ti ti-school text-lg"></i></span>
                                @endif
                                <span class="relative flex-1 text-sm font-bold truncate" style="color: var(--ov-ink);">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</span>
                                <span class="relative shrink-0 ov-num font-extrabold text-base" style="color: {{ $medal ?? 'var(--color-primary)' }};">{{ number_format($votes, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="flex-1 flex items-center justify-center py-12">
                                <p class="text-sm" style="color: var(--ov-muted);">Belum ada vote di kategori ini</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Kanan: Komentar kategori --}}
                <div class="w-[360px] shrink-0 flex flex-col overflow-hidden ov-panel">
                    <div class="shrink-0 flex items-center gap-2.5 px-6 py-3" style="border-bottom: 1px solid var(--ov-line);">
                        <i class="ti ti-message-circle text-sm" style="color: var(--color-primary);"></i>
                        <span class="ov-kicker text-[10px]" style="color: var(--ov-ink);">Dukungan</span>
                    </div>
                    @php $comsCat = array_slice($overlayComments ?? [], 0, 10); @endphp
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 leaderboard-scroll">
                        @forelse($comsCat as $c)
                            @php $initial = strtoupper(mb_substr(trim($c['voter_name'] ?? '?'), 0, 1)); @endphp
                            <div class="flex items-start gap-2.5">
                                <span class="shrink-0 flex items-center justify-center w-8 h-8 ov-num text-[11px] font-bold" style="background: var(--ov-ink); color: #fff;">{{ $initial }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs font-bold truncate" style="color: var(--ov-ink);">{{ $c['voter_name'] }}</span>
                                        <span class="shrink-0 ov-num text-[9px] font-bold px-1.5 py-0.5 text-white" style="background: var(--ov-red);">+{{ number_format($c['votes_earned'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    <p class="mt-0.5 text-[11px] leading-relaxed" style="color: #3d4048;">"{{ $c['comment'] }}"</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex-1 flex items-center justify-center">
                                <p class="text-xs" style="color: var(--ov-muted);">Belum ada komentar</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </main>

    {{-- ============================================================ --}}
    {{-- CUSTOM MODE --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'custom')
        <main class="flex-1 flex flex-col overflow-hidden" style="background: var(--ov-paper);">

            <div class="flex-1 flex overflow-hidden">
                @if($overlaySetting?->show_vote_leaderboard)
                <div class="flex-1 flex flex-col p-8 overflow-hidden" wire:poll.10s="refreshVoteData">
                    {{-- Stats bar --}}
                    <div class="flex items-center justify-center gap-6 mb-8">
                        <div class="text-center px-10 py-4 ov-panel">
                            <span class="ov-num text-2xl font-extrabold block" style="color: var(--ov-ink);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                            <span class="ov-kicker text-[9px] block mt-1" style="color: var(--ov-muted);">Total Vote</span>
                        </div>
                        <div class="text-center px-10 py-4 ov-panel">
                            <span class="ov-num text-2xl font-extrabold block" style="color: var(--ov-ink);">{{ count($topVoteData) }}</span>
                            <span class="ov-kicker text-[9px] block mt-1" style="color: var(--ov-muted);">Kontingen</span>
                        </div>
                    </div>

                    @if(count($topVoteData) > 0)
                        @php $maxV = $topVoteData[0]['total_votes'] ?? 1; @endphp
                        {{-- Top 3: tiga kartu sejajar, peringkat 1 ditonjolkan --}}
                        <div class="flex items-stretch justify-center gap-5 flex-1 max-w-4xl mx-auto w-full mb-6">
                            @foreach(array_slice($topVoteData, 0, 3) as $i => $r)
                                @php $votes = $r['total_votes'] ?? 0; @endphp
                                <div class="flex-1 {{ $i === 0 ? 'max-w-[300px]' : 'max-w-[240px]' }} flex flex-col items-center justify-center p-6 ov-panel ov-rise"
                                     style="animation-delay: {{ $i * 0.12 }}s; {{ $i === 0 ? 'border-top: 5px solid var(--color-primary); box-shadow: 8px 8px 0 rgba(var(--color-primary-rgb),0.85);' : '' }}">
                                    <span class="ov-kicker text-[10px] px-3 py-1 mb-4 text-white" style="{{ $i === 0 ? 'background: var(--color-primary);' : 'background: var(--ov-ink);' }}">
                                        {{ ['Peringkat 1', 'Peringkat 2', 'Peringkat 3'][$i] }}
                                    </span>
                                    @if($r['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $r['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover mb-3" style="border: 2px solid {{ $i === 0 ? 'var(--color-primary)' : 'var(--ov-line)' }};">
                                    @else
                                        <span class="flex h-16 w-16 items-center justify-center rounded-full mb-3" style="background: var(--ov-paper); border: 1px solid var(--ov-line); color: var(--ov-muted);"><i class="ti ti-school text-2xl"></i></span>
                                    @endif
                                    <h3 class="text-sm font-bold text-center leading-tight line-clamp-2 mb-2" style="color: var(--ov-ink);">{{ $r['display_name'] ?? $r['nama_sekolah'] }}</h3>
                                    <span class="ov-num font-extrabold text-2xl" style="color: {{ $i === 0 ? 'var(--color-primary)' : 'var(--ov-ink)' }};">{{ number_format($votes, 0, ',', '.') }}</span>
                                    <span class="ov-kicker text-[9px] mt-1" style="color: var(--ov-muted);">suara</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Rank 4+ list --}}
                        @if(count($topVoteData) > 3)
                        <div class="max-w-4xl mx-auto w-full ov-panel" style="border-left: 3px solid var(--color-primary);">
                            @foreach(array_slice($topVoteData, 3) as $i => $r)
                                @php $barPct = $maxV > 0 ? min((($r['total_votes'] ?? 0) / $maxV) * 100, 100) : 0; @endphp
                                <div class="relative flex items-center gap-3 px-5 py-2.5 overflow-hidden {{ $i > 0 ? 'border-t' : '' }}" style="border-color: var(--ov-line);">
                                    <div class="absolute inset-y-0 left-0 pointer-events-none ov-bar" style="width:{{ $barPct }}%; background: rgba(var(--color-primary-rgb),0.08);"></div>
                                    <span class="relative ov-num text-xs font-bold w-6 text-center" style="color: var(--ov-muted);">{{ $i + 4 }}</span>
                                    <h4 class="relative text-xs font-bold flex-1 truncate" style="color: var(--ov-ink);">{{ $r['display_name'] ?? $r['nama_sekolah'] }}</h4>
                                    <span class="relative ov-num text-xs font-bold" style="color: var(--color-primary);">{{ number_format($r['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    @else
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <div class="flex h-14 w-14 items-center justify-center mx-auto mb-3 ov-panel" style="border-radius: 10px;">
                                    <i class="ti ti-heart-off text-xl" style="color: var(--ov-line);"></i>
                                </div>
                                <p class="text-sm font-medium" style="color: var(--ov-muted);">Belum ada data vote.</p>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                @if($overlaySetting?->show_kegiatan)
                <div class="w-[420px] shrink-0 flex flex-col p-6 overflow-hidden" style="border-left: 1px solid var(--ov-line);">
                    <div class="flex items-center gap-2 mb-5">
                        <span class="h-1 w-4" style="background: var(--color-primary);"></span>
                        <span class="ov-kicker text-[10px]" style="color: var(--ov-muted);">Kegiatan</span>
                    </div>
                    <div class="flex flex-col gap-2 flex-1 overflow-y-auto leaderboard-scroll">
                        @forelse($categoriesData as $cat)
                            <div class="flex items-center justify-between px-4 py-3" style="background: #fff; border: 1px solid var(--ov-line); border-left: 3px solid var(--color-primary); border-radius: 8px;">
                                <span class="text-xs font-medium truncate" style="color: #3d4048;">{{ $cat->full_name }}</span>
                                <span class="ov-num text-xs font-bold shrink-0 ml-2" style="color: var(--color-primary);">{{ $cat->registrations_count ?? 0 }}</span>
                            </div>
                        @empty
                            <p class="text-xs" style="color: var(--ov-muted);">—</p>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>

            @if($overlaySetting?->marquee_text)
            <div class="shrink-0 py-2.5 px-6 overflow-hidden relative" style="background: var(--ov-ink); border-top: 3px solid var(--color-primary);">
                <div class="text-xs font-medium tracking-wide whitespace-nowrap" style="color: rgba(255,255,255,0.7); animation: marquee 25s linear infinite;">
                    {{ $overlaySetting->marquee_text }} &nbsp;&nbsp;✦&nbsp;&nbsp; {{ $overlaySetting->marquee_text }} &nbsp;&nbsp;✦&nbsp;&nbsp; {{ $overlaySetting->marquee_text }}
                </div>
            </div>
            @endif

            @if(count($overlayComments) > 0)
            <div class="shrink-0 flex items-center gap-3 h-[52px] px-6 relative" style="background: #fff; border-top: 1px solid var(--ov-line);"
                 x-data="cFade({{ json_encode(array_map(fn($c) => ['n'=>$c['voter_name'],'t'=>$c['comment'],'v'=>$c['votes_earned']??0], $overlayComments)) }})" x-init="init()">
                <span class="shrink-0 ov-kicker text-[9px] flex items-center gap-1.5" style="color: var(--ov-muted);">
                    <span class="h-1 w-1" style="background: var(--ov-amber);"></span> Pesan
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
                             class="absolute inset-0 flex items-center gap-2 text-xs" style="color: #3d4048;">
                            <span class="font-bold truncate" style="color: var(--ov-ink);" x-text="c.n"></span>
                            <span style="color: var(--ov-muted);">—</span>
                            <span class="truncate italic" x-text="'“'+c.t+'”'"></span>
                            <span class="shrink-0 ov-num font-bold ml-auto" style="color: var(--color-primary);" x-text="'♥ '+Number(c.v).toLocaleString('id-ID')"></span>
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
              style="background: var(--ov-paper);">

            {{-- Stats bar --}}
            <div class="flex items-center justify-center gap-6 mb-8 shrink-0">
                <div class="text-center px-12 py-5 ov-panel">
                    <span class="ov-num text-4xl font-extrabold block leading-none" style="color: var(--ov-ink);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    <span class="ov-kicker text-[10px] block mt-2" style="color: var(--ov-muted);">Total Vote</span>
                </div>
                <div class="text-center px-12 py-5 ov-panel">
                    <span class="ov-num text-4xl font-extrabold block leading-none" style="color: var(--ov-ink);">{{ count($topVoteData) }}</span>
                    <span class="ov-kicker text-[10px] block mt-2" style="color: var(--ov-muted);">Kontingen</span>
                </div>
                <div class="text-center px-12 py-5 ov-panel">
                    <span class="ov-num text-4xl font-extrabold block leading-none" style="color: var(--ov-ink);">{{ number_format($totalParticipants) }}</span>
                    <span class="ov-kicker text-[10px] block mt-2" style="color: var(--ov-muted);">Total Peserta</span>
                </div>
            </div>

            @if(count($topVoteData) > 0)
                @php $maxV = $topVoteData[0]['total_votes'] ?? 1; @endphp

                {{-- Podium: tiga kartu sejajar, peringkat 1 menonjol --}}
                <div class="max-w-[1300px] mx-auto w-full mb-8 shrink-0">
                    <div class="flex items-center gap-2.5 px-6 py-3" style="border-bottom: 1px solid var(--ov-line);">
                        <span class="h-1 w-1" style="background: var(--ov-amber);"></span>
                        <span class="ov-kicker text-[10px]" style="color: var(--ov-ink);">Klasemen Vote</span>
                        <span class="h-1 w-1" style="background: var(--ov-amber);"></span>
                    </div>
                    <div class="flex items-stretch justify-center gap-5 px-6 py-6">
                        @foreach([1, 0, 2] as $posisi)
                            @php
                                $r = $topVoteData[$posisi] ?? null;
                                if (! $r) continue;
                                $aksen = $posisi === 0 ? 'var(--color-primary)' : ($posisi === 1 ? '#8a6d1f' : '#4d6a8a');
                            @endphp
                            <div class="flex-1 max-w-[280px] flex flex-col items-center justify-center p-6 ov-panel ov-rise"
                                 style="animation-delay: {{ $posisi * 0.12 }}s; border-top: 4px solid {{ $aksen }}; {{ $posisi === 0 ? 'box-shadow: 8px 8px 0 rgba(var(--color-primary-rgb),0.85);' : '' }}">
                                <span class="ov-kicker text-[10px] px-3 py-1 mb-4 text-white" style="background: {{ $aksen }};">
                                    {{ ['Peringkat 1', 'Peringkat 2', 'Peringkat 3'][$posisi] }}
                                </span>
                                @if($r['logo_sekolah'])
                                    <img src="{{ asset('storage/' . $r['logo_sekolah']) }}" class="h-20 w-20 rounded-full object-cover mb-3" style="border: 2px solid {{ $i === 0 ? 'var(--color-primary)' : 'var(--ov-line)' }};">
                                @else
                                    <span class="flex h-20 w-20 items-center justify-center rounded-full mb-3" style="background: var(--ov-paper); border: 1px solid var(--ov-line); color: var(--ov-muted);"><i class="ti ti-school text-3xl"></i></span>
                                @endif
                                <h3 class="text-sm font-bold text-center leading-tight line-clamp-2 mb-2" style="color: var(--ov-ink);">{{ $r['display_name'] ?? $r['nama_sekolah'] }}</h3>
                                <span class="ov-num font-extrabold text-2xl" style="color: {{ $aksen }};">{{ number_format($r['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                <span class="ov-kicker text-[9px] mt-1" style="color: var(--ov-muted);">suara</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Rank 4+ --}}
                @php $rest = array_slice($topVoteData, 3); @endphp
                @if(count($rest) > 0)
                    <div class="max-w-[1300px] mx-auto w-full flex-1 min-h-0 flex flex-col ov-panel overflow-hidden">
                        <div class="divide-y leaderboard-scroll overflow-y-auto flex-1" style="border-color: var(--ov-line);">
                            @foreach($rest as $i => $reg)
                                @php $barPct = $maxV > 0 ? min((($reg['total_votes'] ?? 0) / $maxV) * 100, 100) : 0; @endphp
                                <div class="relative flex items-center gap-5 px-8 py-4 overflow-hidden">
                                    <div class="absolute inset-y-0 left-0 pointer-events-none ov-bar" style="width:{{ $barPct }}%; background: rgba(var(--color-primary-rgb),0.08);"></div>
                                    <span class="relative shrink-0">
                                        <span class="inline-flex items-center justify-center h-10 w-10 ov-num text-sm font-bold" style="background: var(--ov-paper); color: var(--ov-muted); border: 1px solid var(--ov-line);">{{ $i + 4 }}</span>
                                    </span>
                                    @if($reg['logo_sekolah'])
                                        <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="relative h-11 w-11 rounded-md object-cover shrink-0" style="border: 1px solid var(--ov-line);">
                                    @else
                                        <span class="relative flex h-11 w-11 items-center justify-center rounded-md shrink-0" style="background: var(--ov-paper); border: 1px solid var(--ov-line); color: var(--ov-muted);"><i class="ti ti-school text-lg"></i></span>
                                    @endif
                                    <div class="relative flex-1 min-w-0">
                                        <h4 class="text-sm font-bold leading-tight truncate" style="color: var(--ov-ink);">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</h4>
                                    </div>
                                    <div class="relative shrink-0 text-right">
                                        <span class="ov-num font-extrabold text-lg" style="color: var(--color-primary);">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                        <span class="ov-kicker text-[9px] block" style="color: var(--ov-muted);">Vote</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="flex h-20 w-20 items-center justify-center mx-auto mb-5 ov-panel" style="border-radius: 10px;">
                            <i class="ti ti-heart-off text-3xl" style="color: var(--ov-line);"></i>
                        </div>
                        <h3 class="font-display text-xl font-bold mb-2" style="color: var(--ov-muted);">Belum Ada Vote</h3>
                        <p class="text-sm" style="color: var(--ov-muted);">Data vote akan muncul saat pemilih mulai memberikan dukungan.</p>
                    </div>
                </div>
            @endif
        </main>

    {{-- ============================================================ --}}
    {{-- KEGIATAN MODE --}}
    {{-- ============================================================ --}}
    @elseif($mode === 'kegiatan')
        <main class="flex-1 flex flex-col items-center justify-center px-20" style="background: var(--ov-paper);">
            <div class="text-center mb-10">
                <div class="inline-flex items-center gap-2 px-5 py-2 bg-white" style="border: 1px solid var(--ov-line); border-radius: 999px;">
                    <span class="h-1.5 w-1.5" style="background: var(--color-primary);"></span>
                    <span class="ov-kicker text-[10px]" style="color: var(--ov-ink);">Data Kegiatan</span>
                </div>
            </div>

            @if(count($categoriesData) > 0)
                <div class="grid grid-cols-2 gap-5 w-full max-w-[1500px] mb-10">
                    @foreach($categoriesData as $cat)
                        <div class="relative flex items-center gap-6 px-8 py-6 bg-white ov-stagger" style="--i: {{ $loop->index % 8 }}; border: 1px solid var(--ov-line); border-radius: 10px;">
                            <div class="relative flex-1 min-w-0">
                                <h3 class="font-display text-xl font-bold truncate" style="color: var(--ov-ink);">{{ $cat->full_name }}</h3>
                                @if($cat->parent)
                                    <p class="text-[11px] font-medium mt-0.5 truncate" style="color: var(--ov-muted);">{{ $cat->parent->name }}</p>
                                @endif
                            </div>
                            <div class="relative shrink-0 text-right">
                                <span class="ov-num text-3xl font-extrabold" style="color: var(--color-primary);">{{ number_format($cat->registrations_count ?? 0) }}</span>
                                <span class="ov-kicker text-[9px] block mt-0.5" style="color: var(--ov-muted);">Kontingen</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="inline-flex items-center gap-5 px-12 py-6" style="background: var(--ov-ink); border-radius: 10px; box-shadow: 8px 8px 0 rgba(var(--color-primary-rgb),0.85);">
                    <span class="flex h-12 w-12 items-center justify-center" style="background: rgba(255,255,255,0.12); color: #fff;">
                        <i class="ti ti-users text-xl"></i>
                    </span>
                    <div>
                        <span class="ov-num font-extrabold text-4xl block leading-none text-white">{{ number_format($totalParticipants) }}</span>
                        <span class="ov-kicker text-[10px] block mt-1" style="color: rgba(255,255,255,0.6);">Total Peserta</span>
                    </div>
                </div>
            @else
                <div class="text-center">
                    <div class="flex h-20 w-20 items-center justify-center mx-auto mb-5 ov-panel" style="border-radius: 10px;">
                        <i class="ti ti-folder-off text-3xl" style="color: var(--ov-line);"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-2" style="color: var(--ov-muted);">Belum Ada Data</h3>
                    <p class="text-sm" style="color: var(--ov-muted);">Kategori lomba belum tersedia.</p>
                </div>
            @endif
        </main>

    {{-- ============================================================ --}}
    {{-- FULL MODE (default) — editorial: leaderboard kiri, chroma tengah, komentar kanan --}}
    @else
        <main class="flex-1 flex overflow-hidden" style="background: var(--ov-paper);" wire:poll.10s="refreshVoteData">

            {{-- LEFT: Leaderboard Sidebar --}}
            <aside class="w-[300px] shrink-0 flex flex-col mr-5 ov-panel">
                <div class="shrink-0 flex items-center gap-2.5 px-5 py-4" style="border-bottom: 1px solid var(--ov-line);">
                    <span class="flex items-center justify-center w-8 h-8" style="background: var(--ov-ink); color: #fff;">
                        <i class="ti ti-trophy text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="font-display text-sm font-bold block leading-tight" style="color: var(--ov-ink);">Leaderboard</span>
                        <span class="text-[9px] font-medium" style="color: var(--ov-muted);">Vote berbayar — real-time</span>
                    </div>
                    <span class="ov-kicker text-[9px] px-2 py-0.5 ml-auto shrink-0 text-white" style="background: var(--ov-ink);">TOP 7</span>
                </div>

                @php
                    $top7 = array_slice($topVoteData, 0, 7);
                    $maxVotes = max($top7[0]['total_votes'] ?? 1, 1);
                @endphp
                <div class="flex-1 overflow-y-auto p-3 gap-1.5 flex flex-col leaderboard-scroll"
                     x-data="{ h: -1 }"
                     x-init="setInterval(() => h = (h + 1) % {{ max(count($top7), 1) }}, 5000)">
                    @forelse($top7 as $i => $reg)
                        @php
                            $rank = $i + 1;
                            $votes = $reg['total_votes'] ?? 0;
                            $pct = min(round(($votes / $maxVotes) * 100), 100);
                            $medal = $rank === 1 ? '#b45309' : ($rank === 2 ? '#8a6d1f' : ($rank === 3 ? '#4d6a8a' : null));
                        @endphp
                        <div class="relative overflow-hidden transition-colors duration-500"
                             :style="h === {{ $i }}
                                ? 'background: rgba(var(--color-primary-rgb), 0.08); border: 1px solid var(--color-primary);'
                                : 'background: {{ $i % 2 === 0 ? '#ffffff' : 'var(--ov-paper)' }}; border: 1px solid var(--ov-line);'">
                            {{-- progress bar vote --}}
                            <div class="absolute bottom-0 left-0 h-[3px]" style="width: {{ $pct }}%; background: {{ $medal ?? 'rgba(var(--color-primary-rgb),0.4)' }}; transition: width 0.8s ease;"></div>
                            <div class="flex items-center gap-3 px-3 py-3">
                                <span class="shrink-0 inline-flex items-center justify-center w-8 h-8 ov-num text-xs font-bold"
                                      style="{{ $medal ? 'background: ' . $medal . '; color: #fff;' : 'background: var(--ov-paper); color: var(--ov-muted); border: 1px solid var(--ov-line);' }}">
                                    @if($rank === 1)<i class="ti ti-crown-filled text-sm"></i>@else{{ $rank }}@endif
                                </span>
                                @if($reg['logo_sekolah'])
                                    <img src="{{ asset('storage/' . $reg['logo_sekolah']) }}" class="h-9 w-9 rounded-md object-cover shrink-0" style="border: 1px solid var(--ov-line);">
                                @else
                                    <span class="flex h-9 w-9 items-center justify-center rounded-md shrink-0" style="background: var(--ov-paper); color: var(--ov-muted); border: 1px solid var(--ov-line);"><i class="ti ti-school text-xs"></i></span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <span class="block text-xs font-bold whitespace-nowrap truncate leading-tight" style="color: var(--ov-ink);">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</span>
                                    @if($medal)
                                        <span class="block text-[9px] font-bold uppercase tracking-wider mt-0.5" style="color: {{ $medal }};">
                                            {{ $rank === 1 ? 'Juara Sementara' : 'Posisi ' . $rank }}
                                        </span>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="block ov-num text-sm font-bold leading-none" style="color: {{ $medal ?? 'var(--color-primary)' }};">{{ number_format($votes, 0, ',', '.') }}</span>
                                    <span class="block text-[9px] font-medium mt-0.5" style="color: var(--ov-muted);">suara</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex-1 flex items-center justify-center py-12"><p class="text-xs font-medium" style="color: var(--ov-muted);">Belum ada data</p></div>
                    @endforelse
                </div>

                {{-- footer total vote --}}
                @if(count($top7) > 0)
                    <div class="shrink-0 flex items-center justify-between px-5 py-3" style="border-top: 1px solid var(--ov-line); background: var(--ov-paper);">
                        <span class="ov-kicker text-[10px]" style="color: var(--ov-muted);">Total Suara</span>
                        <span class="ov-num text-sm font-bold" style="color: var(--color-primary);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    </div>
                @endif
            </aside>

            {{-- CENTER: Greenscreen (chroma key #00FF00 untuk OBS — jangan ganti warna) --}}
            <div class="flex-1 bg-[#00FF00] relative overflow-hidden">
                <div class="absolute top-6 left-6 w-14 h-14 border-t border-l" style="border-color: rgba(255,255,255,0.75);"></div>
                <div class="absolute top-6 right-6 w-14 h-14 border-t border-r" style="border-color: rgba(255,255,255,0.75);"></div>
                <div class="absolute bottom-6 left-6 w-14 h-14 border-b border-l" style="border-color: rgba(255,255,255,0.75);"></div>
                <div class="absolute bottom-6 right-6 w-14 h-14 border-b border-r" style="border-color: rgba(255,255,255,0.75);"></div>
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-20 w-20 opacity-[0.06] rounded-md object-cover">
                @endif
            </div>

            {{-- RIGHT: Comments Panel --}}
            <aside class="w-[320px] shrink-0 flex flex-col ml-5 ov-panel">
                <div class="shrink-0 flex items-center gap-2.5 px-5 py-4" style="border-bottom: 1px solid var(--ov-line);">
                    <span class="flex items-center justify-center w-8 h-8" style="background: var(--color-primary); color: #fff;">
                        <i class="ti ti-message-circle text-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <span class="font-display text-sm font-bold block leading-tight" style="color: var(--ov-ink);">Dukungan</span>
                        <span class="text-[9px] font-medium" style="color: var(--ov-muted);">Komentar & vote terbaru</span>
                    </div>
                    <span class="shrink-0 ov-num text-[10px] font-bold px-2 py-0.5 text-white" style="background: var(--ov-red);">{{ number_format($totalVoteCount, 0, ',', '.') }} suara</span>
                </div>

                @php $coms3 = array_slice($this->overlayComments ?? [], 0, 5); @endphp
                <div class="flex-1 overflow-y-auto p-4 space-y-3 leaderboard-scroll">
                    @forelse($coms3 as $c)
                        @php $initial = strtoupper(mb_substr(trim($c['voter_name'] ?? '?'), 0, 1)); @endphp
                        <div class="flex items-start gap-2.5">
                            <span class="shrink-0 flex items-center justify-center w-7 h-7 ov-num text-[10px] font-bold" style="background: var(--ov-ink); color: #fff;">{{ $initial }}</span>
                            <div class="flex-1 min-w-0 px-3 py-2" style="background: var(--ov-paper); border: 1px solid var(--ov-line); border-radius: 8px;">
                                <span class="block text-xs font-bold leading-tight" style="color: var(--ov-ink);">{{ $c['voter_name'] }}</span>
                                <span class="block mt-0.5 text-[11px] leading-relaxed" style="color: #3d4048;">"{{ $c['comment'] }}"</span>
                            </div>
                            <span class="shrink-0 ov-num text-[10px] font-bold px-1.5 py-0.5 text-white" style="background: var(--ov-red);">+{{ number_format($c['votes_earned'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="flex items-center justify-center py-12"><p class="text-sm" style="color: var(--ov-muted);">Belum ada komentar</p></div>
                    @endforelse
                </div>
            </aside>
        </main>

        {{-- BOTTOM BAR --}}
        <div class="shrink-0 flex items-center h-[48px] px-8 gap-8 bg-white" style="border-top: 1px solid var(--ov-line);">
            @php $comsAll = $this->overlayComments ?? []; @endphp
            @if(count($comsAll) > 0)
            <div class="flex-1 min-w-0 relative h-full overflow-hidden"
                 x-data="cFade({{ json_encode(array_map(fn($c) => ['n'=>$c['voter_name'],'t'=>$c['comment'],'v'=>$c['votes_earned']??0], $comsAll)) }})" x-init="init()">
                <template x-for="(c,i) in items" :key="i">
                    <div x-show="idx===i"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-3"
                         class="absolute inset-0 flex items-center gap-2.5 px-2">
                        <span class="shrink-0 flex items-center justify-center w-5 h-5 ov-num text-[9px] font-bold text-white" style="background: var(--color-primary);" x-text="(c.n||'?').charAt(0).toUpperCase()"></span>
                        <span class="text-xs font-bold truncate max-w-[140px]" style="color: var(--color-primary);" x-text="c.n"></span>
                        <span class="text-xs italic truncate flex-1" style="color: #3d4048;" x-text="'— “'+c.t+'”'"></span>
                        <span class="text-xs shrink-0 ov-num font-bold" style="color: var(--ov-red);" x-text="'+'+Number(c.v).toLocaleString('id-ID')"></span>
                    </div>
                </template>
            </div>
            @else
            <div class="flex-1 flex items-center gap-2">
                <span class="ov-kicker text-[10px] shrink-0" style="color: var(--ov-muted);">Komentar</span>
                <span class="text-xs italic" style="color: var(--ov-muted);">Belum ada dukungan masuk</span>
            </div>
            @endif
            <div class="h-5 w-px shrink-0" style="background: var(--ov-line);"></div>
            <div class="shrink-0 flex items-center gap-3">
                <span class="ov-kicker text-[10px]" style="color: var(--ov-muted);">Kegiatan</span>
                @foreach($categoriesData->take(4) as $cat)
                    <span class="text-[11px] px-2 py-0.5 font-medium" style="background: var(--ov-paper); color: #3d4048; border: 1px solid var(--ov-line); border-radius: 999px;">{{ $cat->full_name }}<span style="color: var(--ov-muted);"> ({{ $cat->registrations_count ?? 0 }})</span></span>
                @endforeach
            </div>
            <div class="h-5 w-px shrink-0" style="background: var(--ov-line);"></div>
            <span class="shrink-0 text-[10px] font-medium" style="color: var(--ov-muted);">Powered by <span class="font-bold" style="color: var(--color-primary);">{{ app_name() }}</span></span>
        </div>
    @endif

    {{-- FOOTER (non-full modes) --}}
    @if($mode !== 'full')
    <footer class="shrink-0 flex items-center justify-center h-[30px] relative bg-white" style="border-top: 1px solid var(--ov-line);">
        <span class="text-[9px] font-medium tracking-[0.1em]" style="color: var(--ov-muted);">Powered by <strong class="font-bold" style="color: var(--ov-ink);">{{ app_name() }}</strong></span>
    </footer>
    @endif
</div>
