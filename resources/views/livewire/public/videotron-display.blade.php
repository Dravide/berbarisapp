<div class="videotron-container flex flex-col overflow-hidden"
     style="background: #0a0d1a;"
     x-data="vtclock"
     x-init="init()">
<script>
    document.addEventListener('alpine:init', () => {
        // Jam berjalan — pola sama dengan clock di livestream overlay.
        Alpine.data('vtclock', () => ({
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
    /* ====== Animasi murni CSS — TANPA anime.js. Semua keyframes lokal di sini. ====== */
    @keyframes vt-marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    @keyframes vt-fade-up {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes vt-fade-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes vt-ping-red {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .5; transform: scale(1.15); }
    }
    @keyframes vt-glow {
        0%, 100% { filter: drop-shadow(0 0 10px rgba(245,158,11,.45)); }
        50% { filter: drop-shadow(0 0 22px rgba(245,158,11,.8)); }
    }
    @keyframes vt-bar-grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }
    @keyframes vt-slide-l { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }

    .vt-anim-up { animation: vt-fade-up .7s cubic-bezier(.2,.8,.2,1) both; }
    .vt-anim-in { animation: vt-fade-in .6s ease both; }
    .vt-anim-l { animation: vt-slide-l .7s cubic-bezier(.2,.8,.2,1) both; }
    .vt-anim-bar { transform-origin: left; animation: vt-bar-grow 1.1s cubic-bezier(.2,.8,.2,1) both; }
    .vt-live-dot { animation: vt-ping-red 1.6s ease-in-out infinite; }
    .vt-crown { animation: vt-glow 2.4s ease-in-out infinite; }

    /* Stagger: delay per baris via CSS var */
    .vt-stagger > * { animation: vt-fade-up .6s cubic-bezier(.2,.8,.2,1) both; animation-delay: calc(var(--i, 0) * 90ms); }

    .vt-scroll::-webkit-scrollbar { width: 4px; }
    .vt-scroll::-webkit-scrollbar-track { background: transparent; }
    .vt-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.14); border-radius: 10px; }

    .vt-card {
        background: rgba(255,255,255,.02);
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 20px;
    }
</style>

    {{-- ==================== HEADER (bukan welcome/sponsor) ==================== --}}
    @if(!in_array($mode, ['welcome', 'sponsor']))
    <header class="shrink-0 flex items-center gap-5 px-12 h-[84px] relative overflow-hidden"
            style="background: #090c17; border-bottom: 1px solid rgba(255,255,255,0.06);">
        <div class="absolute top-0 inset-x-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(var(--color-primary-rgb),0.35), transparent);"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="relative z-10 h-12 w-12 rounded-2xl object-cover border border-white/10 shrink-0">
        @else
            <span class="relative z-10 flex h-12 w-12 items-center justify-center rounded-2xl text-white/35 border border-white/10 shrink-0" style="background: rgba(255,255,255,0.03);">
                <i class="ti ti-calendar-event text-xl"></i>
            </span>
        @endif

        <div class="relative z-10 flex-1 min-w-0">
            <h1 class="font-display text-xl font-extrabold text-white leading-tight tracking-tight truncate">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue)
                <p class="text-[12px] text-white/35 font-medium truncate flex items-center gap-1.5">
                    <i class="ti ti-map-pin-filled text-[11px]" style="color: #ef4444;"></i> {{ $eventner->venue }}
                </p>
            @endif
        </div>

        <div class="relative z-10 flex items-center gap-6 shrink-0">
            <div class="flex items-center gap-2 rounded-full px-5 py-2 text-[12px] font-bold uppercase tracking-[0.15em]"
                 style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25);">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 vt-live-dot"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                </span>
                <span style="color: #f87171;">Live</span>
            </div>
            <div class="h-9 w-px" style="background: rgba(255,255,255,0.08);"></div>
            <div class="text-right">
                <div class="font-mono text-[30px] font-bold text-white tabular-nums leading-none tracking-tight" x-text="time"></div>
                <div class="text-[11px] text-white/25 font-medium leading-tight mt-1" x-text="date"></div>
            </div>
        </div>
    </header>
    @endif

    {{-- ==================== MODE: WELCOME ==================== --}}
    @if($mode === 'welcome')
        <main class="flex-1 flex flex-col items-center justify-center relative overflow-hidden vt-anim-in"
              style="background: radial-gradient(ellipse at 50% 30%, rgba(var(--color-primary-rgb),0.10) 0%, transparent 65%), #0a0d1a;">
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.5) 1px, transparent 1px); background-size: 120px 120px;"></div>

            <div class="relative z-10 flex flex-col items-center text-center gap-8">
                @if($eventner->logo_event)
                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt=""
                         class="h-40 w-40 rounded-[32px] object-cover border border-white/10 vt-anim-up"
                         style="box-shadow: 0 20px 70px rgba(var(--color-primary-rgb),0.25);">
                @else
                    <span class="flex h-40 w-40 items-center justify-center rounded-[32px] vt-anim-up" style="background: rgba(var(--color-primary-rgb),0.1); border: 1px solid rgba(var(--color-primary-rgb),0.25);">
                        <i class="ti ti-calendar-event text-6xl" style="color: var(--color-primary);"></i>
                    </span>
                @endif

                <div>
                    <div class="text-[16px] font-bold uppercase tracking-[0.35em] mb-6 vt-anim-up" style="color: var(--color-primary); animation-delay: .15s;">Selamat Datang</div>
                    <h1 class="font-display font-extrabold leading-[1.05] tracking-tight text-white vt-anim-up" style="font-size: 96px; animation-delay: .3s;">
                        {{ $eventner->nama_event }}
                    </h1>
                    @if($eventner->venue || $eventner->tanggal)
                        <p class="mt-7 text-[26px] font-medium text-white/45 flex items-center justify-center gap-6 vt-anim-up" style="animation-delay: .45s;">
                            @if($eventner->venue)
                                <span class="inline-flex items-center gap-2"><i class="ti ti-map-pin-filled" style="color: var(--color-primary);"></i>{{ $eventner->venue }}</span>
                            @endif
                            @if($eventner->venue && $eventner->tanggal)<span class="text-white/20">·</span>@endif
                            @if($eventner->tanggal)
                                <span class="inline-flex items-center gap-2"><i class="ti ti-calendar-filled" style="color: var(--color-primary);"></i>{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <div class="mt-6 font-mono text-[40px] font-bold text-white/70 tabular-nums tracking-tight vt-anim-up" style="animation-delay: .6s;" x-text="time"></div>
            </div>
        </main>

    {{-- ==================== MODE: DRAWING ==================== --}}
    @elseif($mode === 'drawing')
        <main class="flex-1 flex flex-col px-14 py-8 overflow-hidden" wire:poll.15s="refreshData">
            @if($categoryId && count($drawingQueue) > 0)
                @php $berikutnya = $drawingQueue[0] ?? null; @endphp
                <div class="grid grid-cols-3 gap-6 flex-1 min-h-0">
                    {{-- Kolom utama: antrean --}}
                    <div class="col-span-2 vt-card flex flex-col overflow-hidden">
                        <div class="shrink-0 flex items-center gap-3 px-8 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <i class="ti ti-list-numbers text-base" style="color: var(--color-primary);"></i>
                            <span class="text-[13px] font-bold uppercase tracking-[0.2em] text-white/45">Urutan Tampil</span>
                            <span class="ml-auto text-[11px] font-bold px-3 py-1 rounded-full" style="background: rgba(var(--color-primary-rgb),0.15); color: #fff;">{{ count($drawingQueue) }} kontingen menunggu</span>
                        </div>
                        <div class="flex-1 overflow-y-auto vt-scroll p-3 flex flex-col gap-1.5 vt-stagger">
                            @foreach($drawingQueue as $i => $reg)
                                <div class="relative flex items-center gap-4 px-6 py-3.5 rounded-2xl overflow-hidden {{ $i === 0 ? 'vt-anim-up' : '' }}"
                                     style="background: {{ $i === 0 ? 'rgba(var(--color-primary-rgb),0.1); border: 2px solid var(--color-primary)' : 'border: 1px solid transparent' }};">
                                    <span class="shrink-0 inline-flex items-center justify-center h-11 w-11 rounded-xl text-base font-extrabold"
                                          style="{{ $i === 0 ? 'background: var(--color-primary); color: #fff;' : 'background: rgba(255,255,255,.05); color: rgba(255,255,255,.5);' }}">
                                        {{ $reg['no'] }}
                                    </span>
                                    <span class="flex-1 text-[19px] font-bold {{ $i === 0 ? 'text-white' : 'text-white/65' }} truncate">
                                        {{ $reg['nama'] }}
                                    </span>
                                    @if($i === 0)
                                        <span class="shrink-0 text-[11px] font-bold uppercase tracking-[0.18em] px-4 py-1.5 rounded-full" style="background: var(--color-primary); color: #fff;">Berikutnya</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Kolom kanan: sudah tampil --}}
                    <div class="flex flex-col gap-5">
                        <div class="vt-card flex flex-col overflow-hidden flex-1">
                            <div class="shrink-0 flex items-center gap-3 px-7 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <i class="ti ti-check text-base" style="color: #22c55e;"></i>
                                <span class="text-[13px] font-bold uppercase tracking-[0.2em] text-white/45">Sudah Tampil</span>
                            </div>
                            <div class="flex-1 flex flex-col justify-center gap-3 p-5">
                                @forelse(array_reverse($drawingDone) as $done)
                                    <div class="flex items-center gap-4 px-5 py-4 rounded-2xl" style="background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.14);">
                                        <span class="shrink-0 inline-flex items-center justify-center h-10 w-10 rounded-xl text-sm font-extrabold" style="background: rgba(34,197,94,0.15); color: #4ade80;">
                                            {{ $done['no'] }}
                                        </span>
                                        <span class="flex-1 text-[16px] font-bold text-white/70 truncate">{{ $done['nama'] }}</span>
                                        <i class="ti ti-circle-check text-lg" style="color: #22c55e;"></i>
                                    </div>
                                @empty
                                    <p class="text-sm text-white/25 text-center">Belum ada yang tampil</p>
                                @endforelse
                            </div>
                        </div>
                        @if($berikutnya)
                            <div class="vt-card p-7 text-center" style="border-color: rgba(var(--color-primary-rgb),0.35); background: rgba(var(--color-primary-rgb),0.06);">
                                <div class="text-[11px] font-bold uppercase tracking-[0.25em]" style="color: rgba(var(--color-primary-rgb),0.9);">Sedang Bersiap</div>
                                <div class="font-display font-extrabold text-white text-[30px] leading-tight mt-3 vt-anim-up" wire:key="berikut-{{ $berikutnya['id'] }}">{{ $berikutnya['nama'] }}</div>
                                <div class="text-sm text-white/40 mt-2">Nomor {{ $berikutnya['no'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <i class="ti ti-list-numbers text-3xl text-white/15"></i>
                    </div>
                    <p class="text-white/30 text-lg">Belum ada urutan tampil — pilih tingkat lewat URL (?categoryId=...).</p>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: VOTE ==================== --}}
    @elseif($mode === 'vote')
        <main class="flex-1 flex flex-col px-14 py-8 overflow-hidden" wire:poll.10s="refreshData">
            <h2 class="font-display text-[30px] font-extrabold text-white text-center tracking-wide shrink-0 mb-4">Klasemen Vote</h2>
            <div class="flex items-center justify-center gap-14 mb-7 shrink-0">
                <div class="text-center">
                    <span class="font-display text-[56px] font-extrabold text-white leading-none">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    <span class="text-[12px] font-bold text-white/30 uppercase tracking-[0.2em] block mt-2">Total Vote</span>
                </div>
                <div class="h-14 w-px" style="background: rgba(255,255,255,0.08);"></div>
                <div class="text-center">
                    <span class="font-display text-[56px] font-extrabold text-white leading-none">{{ count($topVote) }}</span>
                    <span class="text-[12px] font-bold text-white/30 uppercase tracking-[0.2em] block mt-2">Kontingen</span>
                </div>
            </div>

            @if(count($topVote) > 0)
                @php
                    $top3 = array_slice($topVote, 0, 3);
                    $rest = array_slice($topVote, 3);
                    $maxV = max($topVote[0]['total_votes'] ?? 1, 1);
                @endphp

                <div class="flex-1 flex gap-7 min-h-0">
                    {{-- Podium --}}
                    <div class="flex-1 vt-card flex flex-col p-8">
                        <div class="flex items-end justify-center gap-10 flex-1">
                            @foreach([1, 0, 2] as $posisi)
                                @php
                                    $r = $top3[$posisi] ?? null;
                                    if (! $r) continue;
                                    $tinggi = $posisi === 0 ? '150px' : ($posisi === 1 ? '100px' : '76px');
                                    $warna = $posisi === 0 ? '#f59e0b' : ($posisi === 1 ? '#94a3b8' : '#38bdf8');
                                @endphp
                                <div class="flex flex-col items-center flex-1 max-w-[240px]">
                                    <div class="relative {{ $posisi === 0 ? 'vt-anim-up' : 'vt-anim-in' }}" style="animation-delay: {{ $posisi === 0 ? 0 : $posisi * 0.15 }}s;">
                                        @if($posisi === 0)
                                            <i class="ti ti-crown-filled text-2xl absolute -top-5 left-1/2 -translate-x-1/2 z-10 vt-crown" style="color: #f59e0b;"></i>
                                        @endif
                                        @if($r['logo_sekolah'])
                                            <img src="{{ asset('storage/' . $r['logo_sekolah']) }}" class="h-20 w-20 rounded-full object-cover border-2" style="border-color: {{ $warna }}66; box-shadow: 0 0 34px {{ $warna }}22;">
                                        @else
                                            <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 text-white/30" style="border-color: {{ $warna }}66; background: rgba(255,255,255,.03);"><i class="ti ti-school text-3xl"></i></span>
                                        @endif
                                    </div>
                                    <h3 class="mt-3 text-[15px] font-bold text-white text-center leading-tight line-clamp-2 max-w-[210px]">{{ $r['display_name'] ?? $r['nama_sekolah'] }}</h3>
                                    <span class="font-display font-extrabold text-[22px] mt-1" style="color: {{ $warna }};">{{ number_format($r['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-white/25">suara</span>
                                    <div class="w-full rounded-t-2xl mt-2 flex items-start justify-center vt-anim-in" style="height: {{ $tinggi }}; background: linear-gradient(180deg, {{ $warna }}22 0%, {{ $warna }}05 100%); border: 1px solid {{ $warna }}1e;">
                                        <span class="font-display text-5xl font-extrabold" style="color: {{ $warna }}33;">{{ $posisi + 1 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Rank 4+ --}}
                    @if(count($rest) > 0)
                        <div class="w-[560px] shrink-0 vt-card flex flex-col overflow-hidden">
                            <div class="shrink-0 flex items-center gap-3 px-7 py-4" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <i class="ti ti-trophy text-sm" style="color: #f59e0b;"></i>
                                <span class="text-[12px] font-bold uppercase tracking-[0.2em] text-white/40">Peringkat 4+</span>
                            </div>
                            <div class="flex-1 overflow-y-auto vt-scroll divide-y flex flex-col justify-center" style="border-color: rgba(255,255,255,0.04);">
                                @foreach($rest as $i => $reg)
                                    @php $pct = min((($reg['total_votes'] ?? 0) / $maxV) * 100, 100); @endphp
                                    <div class="relative flex items-center gap-4 px-7 py-3.5 overflow-hidden vt-stagger" style="--i: {{ $i }};">
                                        <div class="absolute inset-y-0 left-0 pointer-events-none vt-anim-bar" style="width: {{ $pct }}%; background: linear-gradient(90deg, rgba(var(--color-primary-rgb),0.12), transparent);"></div>
                                        <span class="relative shrink-0 inline-flex items-center justify-center h-9 w-9 rounded-lg text-sm font-bold" style="background: rgba(255,255,255,.04); color: rgba(255,255,255,.4);">{{ $i + 4 }}</span>
                                        <span class="relative flex-1 text-[16px] font-bold text-white/70 truncate">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</span>
                                        <span class="relative font-display font-extrabold text-[18px]" style="color: var(--color-primary);">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-3xl mx-auto mb-5" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                            <i class="ti ti-heart-off text-3xl text-white/15"></i>
                        </div>
                        <h3 class="font-display text-2xl font-bold text-white/40">Belum Ada Vote</h3>
                    </div>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: CHAMPION ==================== --}}
    @elseif($mode === 'champion')
        <main class="flex-1 flex flex-col items-center justify-center px-16 relative overflow-hidden"
              style="background: radial-gradient(ellipse at 50% 25%, rgba(245,158,11,0.08) 0%, transparent 60%), #0a0d1a;">
            @if($championRanking && count($championRanking) > 0)
                <div class="text-center flex flex-col items-center gap-6 max-w-[1300px]">
                    @if($championName)
                        <div class="text-[14px] font-bold uppercase tracking-[0.3em] vt-anim-up" style="color: #f59e0b;">
                            <i class="ti ti-trophy-filled me-2"></i>{{ $championName }}
                        </div>
                    @endif
                    @if($championTitle)
                        <div class="font-display font-extrabold text-white vt-anim-up" style="font-size: 72px; animation-delay: .1s; letter-spacing: -0.02em;">
                            {{ $championTitle }}
                        </div>
                    @endif
                    <div class="relative vt-anim-up" style="animation-delay: .25s;">
                        <i class="ti ti-crown-filled text-3xl absolute -top-7 left-1/2 -translate-x-1/2 z-10 vt-crown" style="color: #f59e0b;"></i>
                        @if($championRanking[0]['nama'])
                            <div class="font-display font-extrabold text-white leading-tight" style="font-size: 52px;">
                                {{ $championRanking[0]['nama'] }}
                            </div>
                        @endif
                        <div class="font-mono text-[22px] font-bold mt-3" style="color: #f59e0b;">
                            Nilai {{ number_format($championRanking[0]['total'], 2, ',', '.') }}
                        </div>
                    </div>

                    {{-- Runner-up --}}
                    @if(count($championRanking) > 1)
                        <div class="flex gap-6 mt-8 w-full justify-center">
                            @foreach(array_slice($championRanking, 1) as $i => $ps)
                                @php $warna = $i === 0 ? '#94a3b8' : '#38bdf8'; @endphp
                                <div class="vt-card px-10 py-5 flex items-center gap-5 vt-anim-up" style="animation-delay: {{ 0.4 + $i * 0.15 }}s; min-width: 380px;">
                                    <span class="shrink-0 inline-flex items-center justify-center h-12 w-12 rounded-2xl text-lg font-extrabold" style="background: {{ $warna }}1f; color: {{ $warna }};">
                                        {{ $ps['rank'] }}
                                    </span>
                                    <div class="text-left flex-1 min-w-0">
                                        @if($ps['title'])
                                            <div class="text-[11px] font-bold uppercase tracking-widest" style="color: {{ $warna }};">{{ $ps['title'] }}</div>
                                        @endif
                                        <div class="text-[20px] font-bold text-white/80 truncate">{{ $ps['nama'] }}</div>
                                    </div>
                                    <span class="font-display font-extrabold text-[22px]" style="color: {{ $warna }};">{{ number_format($ps['total'], 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl mx-auto mb-5" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <i class="ti ti-trophy text-3xl text-white/15"></i>
                    </div>
                    <h3 class="font-display text-2xl font-bold text-white/40">Belum Ada Juara</h3>
                    <p class="text-white/25 mt-2">Hasil akan tampil setelah kategori juara dipublikasikan.</p>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: RUNDOWN ==================== --}}
    @elseif($mode === 'rundown')
        <main class="flex-1 flex flex-col items-center justify-center px-20 overflow-hidden" wire:poll.30s="refreshData">
            <div class="w-full max-w-[1400px]">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-3 px-6 py-2.5 rounded-full mb-5" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <span class="h-2 w-2 rounded-full" style="background: var(--color-primary);"></span>
                        <span class="text-[13px] font-bold uppercase tracking-[0.25em] text-white/40">Agenda <span x-text="date"></span></span>
                    </div>
                </div>

                @if(count($rundowns) > 0)
                    <div class="flex flex-col gap-3 vt-stagger">
                        @foreach($rundowns as $r)
                            @php $aktif = $rundownSekarang !== null && $r['id'] == $rundownSekarang; @endphp
                            <div class="flex items-center gap-8 px-10 py-6 rounded-3xl transition-all"
                                 style="{{ $aktif
                                    ? 'background: rgba(var(--color-primary-rgb),0.1); border: 2px solid var(--color-primary); box-shadow: 0 10px 50px rgba(var(--color-primary-rgb),0.15);'
                                    : 'background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);' }}">
                                <span class="font-mono text-[28px] font-bold tabular-nums shrink-0" style="{{ $aktif ? 'color: var(--color-primary);' : 'color: rgba(255,255,255,.4);' }}">
                                    {{ $r['start'] }}
                                </span>
                                <div class="h-10 w-px" style="background: rgba(255,255,255,0.08);"></div>
                                <span class="flex-1 text-[26px] font-bold {{ $aktif ? 'text-white' : 'text-white/60' }}">{{ $r['title'] }}</span>
                                @if($aktif)
                                    <span class="shrink-0 inline-flex items-center gap-2.5 px-5 py-2 rounded-full text-[12px] font-bold uppercase tracking-[0.18em]" style="background: var(--color-primary); color: #fff;">
                                        <span class="h-2 w-2 rounded-full bg-white vt-live-dot"></span> Sekarang
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center gap-4 py-16">
                        <div class="flex h-20 w-20 items-center justify-center rounded-3xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                            <i class="ti ti-folder-off text-3xl text-white/15"></i>
                        </div>
                        <p class="text-white/30 text-lg">Rundown belum tersedia.</p>
                    </div>
                @endif
            </div>
        </main>

    {{-- ==================== MODE: SPONSOR ==================== --}}
    @elseif($mode === 'sponsor')
        <main class="flex-1 flex flex-col relative overflow-hidden"
              style="background: radial-gradient(ellipse at 50% 40%, rgba(var(--color-primary-rgb),0.06) 0%, transparent 65%), #0a0d1a;">
            <div class="text-center pt-14 pb-8 shrink-0">
                <span class="text-[15px] font-bold uppercase tracking-[0.35em] text-white/35">Didukung Oleh</span>
            </div>
            @if(count($sponsorLogos) > 0)
                <div class="flex-1 flex flex-col justify-center px-20 pb-14">
                    <div class="grid grid-cols-3 gap-8 vt-stagger">
                        @foreach($sponsorLogos as $i => $sp)
                            <div class="vt-card flex items-center justify-center p-10 min-h-[200px]" style="--i: {{ $i % 6 }};">
                                @if(!empty($sp['logo']))
                                    <img src="{{ asset('storage/' . $sp['logo']) }}" alt="{{ $sp['name'] }}" class="max-h-[120px] max-w-full object-contain">
                                @else
                                    <span class="font-display text-[26px] font-extrabold text-white/55 text-center">{{ $sp['name'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center gap-4">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <i class="ti ti-speartwo text-3xl text-white/15"></i>
                    </div>
                    <p class="text-white/30 text-lg">Belum ada sponsor &amp; media partner.</p>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: LOOP ==================== --}}
    @elseif($mode === 'loop')
        <main class="flex-1 relative overflow-hidden" x-data="{ s: 0 }"
              x-init="setInterval(() => s = (s + 1) % 4, 30000)">
            {{-- Slide tiap 30 dtk: welcome/vote/rundown/sponsor diputar --}}

            <div x-show="s === 0" class="absolute inset-0 flex flex-col" style="background: #0a0d1a;">
                <div class="flex-1 flex flex-col items-center justify-center gap-7">
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-28 w-28 rounded-3xl object-cover border border-white/10">
                    @endif
                    <h1 class="font-display font-extrabold text-white text-[64px] tracking-tight">{{ $eventner->nama_event }}</h1>
                    <div class="font-mono text-3xl font-bold text-white/60" x-text="time"></div>
                </div>
            </div>

            <div x-show="s === 1" x-cloak class="absolute inset-0 flex flex-col" style="background: #0a0d1a;" wire:poll.15s="refreshData">
                <div class="flex-1 flex items-center justify-center gap-16 flex-col px-20">
                    <div class="text-center">
                        <span class="text-[13px] font-bold uppercase tracking-[0.3em] text-white/35 block mb-4">Klasemen Vote</span>
                        <span class="font-display font-extrabold text-white text-[96px] leading-none">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                        <span class="text-[13px] font-bold text-white/30 uppercase tracking-[0.2em] block mt-3">Total Suara</span>
                    </div>
                    <div class="w-full max-w-[1200px] flex flex-col gap-2.5">
                        @foreach(array_slice($topVote, 0, 5) as $i => $reg)
                            <div class="flex items-center gap-5 px-8 py-4 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                <span class="shrink-0 inline-flex items-center justify-center h-11 w-11 rounded-xl text-base font-extrabold {{ $i === 0 ? '' : '' }}"
                                      style="{{ $i === 0 ? 'background: #f59e0b; color: #231a02;' : 'background: rgba(255,255,255,.05); color: rgba(255,255,255,.5);' }}">
                                    {{ $i + 1 }}
                                </span>
                                <span class="flex-1 text-[20px] font-bold text-white/80 truncate">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</span>
                                <span class="font-display font-extrabold text-[24px]" style="color: var(--color-primary);">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div x-show="s === 2" x-cloak class="absolute inset-0 flex flex-col" style="background: #0a0d1a;">
                <div class="flex-1 flex flex-col justify-center px-24 gap-3">
                    <span class="text-[13px] font-bold uppercase tracking-[0.3em] text-white/35 text-center mb-6">Agenda</span>
                    @foreach(array_slice($rundowns, 0, 6) as $r)
                        <div class="flex items-center gap-6 px-8 py-4 rounded-2xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                            <span class="font-mono text-xl font-bold shrink-0" style="color: var(--color-primary);">{{ $r['start'] }}</span>
                            <span class="text-[20px] font-bold text-white/70">{{ $r['title'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div x-show="s === 3" x-cloak class="absolute inset-0 flex flex-col" style="background: #0a0d1a;">
                <div class="flex-1 flex items-center justify-center px-20">
                    <div class="grid grid-cols-2 gap-7 w-full">
                        @foreach(array_slice($sponsorLogos, 0, 4) as $sp)
                            <div class="vt-card flex items-center justify-center p-10 min-h-[180px]">
                                @if(!empty($sp['logo']))
                                    <img src="{{ asset('storage/' . $sp['logo']) }}" alt="{{ $sp['name'] }}" class="max-h-[110px] max-w-full object-contain">
                                @else
                                    <span class="font-display text-2xl font-extrabold text-white/55">{{ $sp['name'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Indikator slide --}}
            <div class="shrink-0 flex items-center justify-center gap-2.5 py-4" style="border-top: 1px solid rgba(255,255,255,0.05);">
                <template x-for="(i) in 4" :key="i">
                    <span class="rounded-full transition-all duration-500"
                          :style="s === i - 1 ? 'width: 30px; height: 6px; background: var(--color-primary);' : 'width: 6px; height: 6px; background: rgba(255,255,255,.2);'"></span>
                </template>
            </div>
        </main>
    @endif

    {{-- ==================== MARQUEE BAWAH (bukan welcome) ==================== --}}
    @if(!in_array($mode, ['welcome', 'loop']))
        <footer class="shrink-0 flex items-center h-[56px] px-12 gap-8 relative overflow-hidden" style="background: #060912; border-top: 1px solid rgba(255,255,255,0.05);">
            <div class="absolute top-0 inset-x-0 h-px" style="background: linear-gradient(90deg, transparent, rgba(var(--color-primary-rgb),0.2), transparent);"></div>
            @if(count($sponsorLogos) > 0)
                <div class="flex-1 min-w-0 relative overflow-hidden">
                    <div class="flex items-center gap-14 whitespace-nowrap" style="animation: vt-marquee 40s linear infinite; width: max-content;">
                        @foreach(array_merge($sponsorLogos, $sponsorLogos) as $sp)
                            <span class="text-[15px] font-bold text-white/45 flex items-center gap-2.5">
                                @if(!empty($sp['logo']))
                                    <img src="{{ asset('storage/' . $sp['logo']) }}" class="h-6 max-w-[70px] object-contain" alt=""> {{ $sp['name'] }}
                                @else
                                    <i class="ti ti-star-filled" style="color: var(--color-primary);"></i> {{ $sp['name'] }}
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex-1"></div>
            @endif
            <span class="shrink-0 text-[12px] font-medium" style="color: rgba(255,255,255,0.25);">Powered by <strong class="font-bold" style="color: rgba(255,255,255,0.4);">{{ app_name() }}</strong></span>
        </footer>
    @endif
</div>
