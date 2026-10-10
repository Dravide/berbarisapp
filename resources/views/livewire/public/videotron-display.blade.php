<div class="videotron-container flex flex-col overflow-hidden videotron-root"
     style="background: #f6f5f1; color: #15171c;"
     x-data="vtclock"
     x-init="init()">
<script>
    document.addEventListener('alpine:init', () => {
        // Jam berjalan — dipakai semua mode kecuali sponsor.
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
    /* ====== Tema editorial terang: kertas gading + tinta + aksen tema. ====== */
    .videotron-root {
        --vt-ink: #15171c;
        --vt-paper: #f6f5f1;
        --vt-line: #dcd9ce;
        --vt-muted: #6d7178;
    }

    /* Animasi murni CSS — TANPA anime.js. Fade halus + marquee saja. */
    @keyframes vt-marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    @keyframes vt-rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes vt-in { from { opacity: 0; } to { opacity: 1; } }
    @keyframes vt-beat { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
    @keyframes vt-bar { from { transform: scaleX(0); } to { transform: scaleX(1); } }

    .vt-rise { animation: vt-rise .55s cubic-bezier(.2,.8,.2,1) both; }
    .vt-in { animation: vt-in .5s ease both; }
    .vt-beat { animation: vt-beat 1.6s ease-in-out infinite; }
    .vt-bar { transform-origin: left; animation: vt-bar 1s cubic-bezier(.2,.8,.2,1) both; }
    .vt-stagger > * { animation: vt-rise .5s cubic-bezier(.2,.8,.2,1) both; animation-delay: calc(var(--i, 0) * 80ms); }

    /* Welcome: animasi digerakkan anime.js (script di mode welcome).
       CSS hanya menyediakan bentuk dasar butir debunya. */
    .vt-mote { position: absolute; bottom: -2%; width: 9px; height: 9px; background: var(--color-primary); opacity: 0; }

    /* Panel kertas: putih dengan garis rambut, sudut hampir tajam. */
    .vt-panel { background: #fff; border: 1px solid var(--vt-line); border-radius: 10px; }
    .vt-kicker { text-transform: uppercase; letter-spacing: .28em; font-weight: 700; }
    .vt-grid-paper {
        background-image:
            linear-gradient(rgba(21,23,28,.045) 1px, transparent 1px),
            linear-gradient(90deg, rgba(21,23,28,.045) 1px, transparent 1px);
        background-size: 96px 96px;
    }
</style>

    {{-- ==================== HEADER (bukan welcome/sponsor) ==================== --}}
    @if(!in_array($mode, ['welcome', 'sponsor']))
    <header class="shrink-0 flex items-center gap-6 px-14 h-[88px] relative bg-white" style="border-bottom: 1px solid var(--vt-line);">
        <div class="absolute top-0 inset-x-0 h-[3px]" style="background: var(--color-primary);"></div>

        @if($eventner->logo_event)
            <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-14 w-14 rounded-lg object-cover shrink-0" style="border: 1px solid var(--vt-ink);">
        @else
            <span class="flex h-14 w-14 items-center justify-center rounded-lg shrink-0" style="background: #fff; border: 1px solid var(--vt-ink);">
                <i class="ti ti-calendar-event text-2xl" style="color: var(--vt-ink);"></i>
            </span>
        @endif

        <div class="flex-1 min-w-0">
            <h1 class="font-display text-[26px] font-extrabold leading-tight tracking-tight truncate" style="color: var(--vt-ink);">
                {{ $eventner->nama_event }}
            </h1>
            @if($eventner->venue)
                <p class="text-[14px] font-medium truncate flex items-center gap-1.5" style="color: var(--vt-muted);">
                    <i class="ti ti-map-pin text-[14px]" style="color: var(--color-primary);"></i> {{ $eventner->venue }}
                </p>
            @endif
        </div>

        <div class="flex items-center gap-6 shrink-0">
            <div class="flex items-center gap-2.5 px-4 py-2 text-[12px] font-bold uppercase tracking-[0.2em] text-white"
                 style="background: #c8102e;">
                <span class="h-2 w-2 rounded-full bg-white vt-beat"></span>
                Live
            </div>
            <div class="h-10 w-px" style="background: var(--vt-line);"></div>
            <div class="text-right">
                <div class="font-mono text-[32px] font-bold tabular-nums leading-none tracking-tight" style="color: var(--vt-ink);" x-text="time"></div>
                <div class="text-[12px] font-medium leading-tight mt-1" style="color: var(--vt-muted);" x-text="date"></div>
            </div>
        </div>
    </header>
    @endif

    {{-- ==================== MODE: WELCOME ==================== --}}
    @if($mode === 'welcome')
        <main class="flex-1 flex flex-col items-center justify-center relative overflow-hidden vt-in" style="background: var(--vt-paper);">
            <div class="absolute top-0 inset-x-0 h-[6px]" style="background: var(--color-primary);"></div>
            <div class="absolute inset-0 vt-grid-paper opacity-70 vt-drift"></div>

            {{-- Debu tinta melayang naik — aksen ambien di atas kertas --}}
            <div class="absolute inset-0 vt-grid-paper opacity-70" id="vt-paper"></div>

            {{-- Debu tinta melayang — digerakkan anime.js, 14 butir --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true" id="vt-motes">
                @foreach(range(1, 14) as $i)
                    <span class="vt-mote" style="left: {{ ($i * 7 + 3) % 100 }}%;"></span>
                @endforeach
            </div>

            <div class="relative z-10 flex flex-col items-center text-center gap-9 px-20">
                <div id="vt-logo-wrap" class="flex">
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt=""
                             class="h-36 w-36 rounded-xl object-cover vt-rise"
                             style="border: 1px solid var(--vt-ink); box-shadow: 8px 8px 0 rgba(var(--color-primary-rgb),0.9);">
                    @else
                        <span class="flex h-36 w-36 items-center justify-center rounded-xl vt-rise"
                              style="background: #fff; border: 1px solid var(--vt-ink); box-shadow: 8px 8px 0 rgba(var(--color-primary-rgb),0.9);">
                            <i class="ti ti-calendar-event text-6xl" style="color: var(--color-primary);"></i>
                        </span>
                    @endif
                </div>

                <div>
                    <div class="flex items-center justify-center gap-5 mb-7" id="vt-sapa">
                        <span class="h-px w-24" id="vt-garis-kiri" style="background: var(--vt-ink);"></span>
                        <span class="text-[18px] font-bold uppercase tracking-[0.4em]" style="color: var(--color-primary);">Selamat Datang</span>
                        <span class="h-px w-24" id="vt-garis-kanan" style="background: var(--vt-ink);"></span>
                    </div>
                    <h1 class="font-display font-extrabold leading-[1.03] tracking-tight vt-rise" style="font-size: 100px; color: var(--vt-ink); animation-delay: .22s;">
                        {{ $eventner->nama_event }}
                    </h1>
                    <div class="mt-6 mx-auto h-[3px] w-56" id="vt-underline" style="background: var(--color-primary); transform: scaleX(0);"></div>
                    @if($eventner->venue || $eventner->tanggal)
                        <p class="mt-8 text-[24px] font-medium flex items-center justify-center gap-6 vt-rise" style="color: #3d4048; animation-delay: .34s;">
                            @if($eventner->venue)
                                <span class="inline-flex items-center gap-2"><i class="ti ti-map-pin" style="color: var(--color-primary);"></i>{{ $eventner->venue }}</span>
                            @endif
                            @if($eventner->venue && $eventner->tanggal)<span style="color: var(--vt-line);">/</span>@endif
                            @if($eventner->tanggal)
                                <span class="inline-flex items-center gap-2"><i class="ti ti-calendar" style="color: var(--color-primary);"></i>{{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <div class="mt-5 font-mono text-[42px] font-bold tabular-nums tracking-tight vt-rise" style="color: var(--vt-ink); animation-delay: .45s;" x-text="time"></div>
            </div>
        </main>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.2/anime.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof anime !== 'function') return;

                // Underline judul tumbuh sekali setelah judul muncul.
                anime({
                    targets: '#vt-underline',
                    scaleX: [0, 1],
                    opacity: [0, 1],
                    duration: 900,
                    delay: 650,
                    easing: 'easeOutQuart',
                });

                // Garis pengapit sapaan tumbuh dari tengah, lalu berdenyut pelan terus-menerus.
                ['#vt-garis-kiri', '#vt-garis-kanan'].forEach((sel, i) => {
                    anime({
                        targets: sel,
                        scaleX: [0, 1],
                        duration: 700,
                        delay: 250 + i * 120,
                        easing: 'easeOutQuart',
                    });
                    anime({
                        targets: sel,
                        opacity: [1, 0.35],
                        direction: 'alternate',
                        loop: true,
                        duration: 2200,
                        delay: 1200 + i * 120,
                        easing: 'easeInOutSine',
                    });
                });

                // Logo melayang pelan tanpa henti — offset shadow ikut mungging
                // (dianimasikan via CSS var agar tak menimpa transform elemennya).
                anime({
                    targets: '#vt-logo-wrap',
                    translateY: [-9, 0],
                    direction: 'alternate',
                    loop: true,
                    duration: 3000,
                    easing: 'easeInOutSine',
                });

                // Kertas grid bergeser sangat pelan — seolah digeser tangan.
                anime({
                    targets: '#vt-paper',
                    backgroundPosition: ['0px 0px', '96px 96px'],
                    duration: 60000,
                    loop: true,
                    easing: 'linear',
                });

                // Debu tinta: naik dari bawah, sedikit bergoyang, memudar — terus berulang.
                document.querySelectorAll('#vt-motes .vt-mote').forEach((mote, i) => {
                    const dur = 11000 + (i % 5) * 3200;
                    anime({
                        targets: mote,
                        translateY: [-880, -960],
                        translateX: [
                            { value: (i % 2 ? 1 : -1) * (14 + (i % 4) * 12), duration: dur / 2, easing: 'easeInOutSine' },
                            { value: 0, duration: dur / 2, easing: 'easeInOutSine' },
                        ],
                        opacity: [
                            { value: 0.16, duration: dur * 0.2, easing: 'easeInQuad' },
                            { value: 0.16, duration: dur * 0.55, easing: 'linear' },
                            { value: 0, duration: dur * 0.25, easing: 'easeOutQuad' },
                        ],
                        duration: dur,
                        delay: i * 900,
                        loop: true,
                        easing: 'linear',
                    });
                });
            });
        </script>

    {{-- ==================== MODE: DRAWING ==================== --}}
    @elseif($mode === 'drawing')
        <main class="flex-1 flex flex-col px-14 py-8 overflow-hidden" wire:poll.15s="refreshData">
            @if($categoryId && count($drawingQueue) > 0)
                @php $berikutnya = $drawingQueue[0] ?? null; @endphp
                <div class="grid grid-cols-3 gap-6 flex-1 min-h-0">
                    {{-- Kolom utama: antrean, gaya tabel garis rambut --}}
                    <div class="col-span-2 vt-panel flex flex-col overflow-hidden">
                        <div class="shrink-0 flex items-center gap-3 px-8 py-4" style="border-bottom: 1px solid var(--vt-line);">
                            <i class="ti ti-list-numbers text-lg" style="color: var(--color-primary);"></i>
                            <span class="vt-kicker text-[13px]" style="color: var(--vt-ink);">Urutan Tampil</span>
                            <span class="ml-auto text-[12px] font-bold px-3 py-1" style="background: rgba(var(--color-primary-rgb),0.1); color: var(--color-primary);">{{ count($drawingQueue) }} kontingen menunggu</span>
                        </div>
                        <div class="flex-1 overflow-y-auto vt-scroll divide-y" style="border-color: var(--vt-line);">
                            @foreach($drawingQueue as $i => $reg)
                                <div class="relative flex items-center gap-5 px-8 py-4 vt-stagger" style="--i: {{ $i % 8 }}; {{ $i === 0 ? 'background: rgba(var(--color-primary-rgb),0.06); box-shadow: inset 5px 0 0 var(--color-primary);' : '' }}">
                                    <span class="shrink-0 inline-flex items-center justify-center h-11 w-11 text-lg font-extrabold"
                                          style="{{ $i === 0 ? 'background: var(--color-primary); color: #fff;' : 'background: #fff; color: var(--vt-ink); border: 1px solid var(--vt-line);' }}">
                                        {{ $reg['no'] }}
                                    </span>
                                    <span class="flex-1 text-[21px] font-bold {{ $i === 0 ? '' : '' }} truncate" style="color: {{ $i === 0 ? 'var(--vt-ink)' : '#3d4048' }};">
                                        {{ $reg['nama'] }}
                                    </span>
                                    @if($i === 0)
                                        <span class="shrink-0 text-[11px] font-bold uppercase tracking-[0.18em] px-4 py-1.5 text-white" style="background: var(--vt-ink);">Berikutnya</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Kolom kanan: sudah tampil + sedang bersiap --}}
                    <div class="flex flex-col gap-6">
                        <div class="vt-panel flex flex-col overflow-hidden flex-1">
                            <div class="shrink-0 flex items-center gap-3 px-7 py-4" style="border-bottom: 1px solid var(--vt-line);">
                                <i class="ti ti-check text-lg" style="color: #15803d;"></i>
                                <span class="vt-kicker text-[13px]" style="color: var(--vt-ink);">Sudah Tampil</span>
                            </div>
                            <div class="flex-1 flex flex-col justify-center gap-2.5 p-5">
                                @forelse(array_reverse($drawingDone) as $done)
                                    <div class="flex items-center gap-4 px-5 py-3.5" style="background: var(--vt-paper); border: 1px solid var(--vt-line); border-radius: 8px;">
                                        <span class="shrink-0 inline-flex items-center justify-center h-9 w-9 text-sm font-extrabold" style="border: 1px solid var(--vt-line); color: var(--vt-ink);">
                                            {{ $done['no'] }}
                                        </span>
                                        <span class="flex-1 text-[17px] font-semibold truncate" style="color: #3d4048;">{{ $done['nama'] }}</span>
                                        <i class="ti ti-circle-check text-lg" style="color: #15803d;"></i>
                                    </div>
                                @empty
                                    <p class="text-sm text-center" style="color: var(--vt-muted);">Belum ada yang tampil</p>
                                @endforelse
                            </div>
                        </div>
                        @if($berikutnya)
                            <div class="p-8 text-center" style="background: var(--vt-ink); border-radius: 10px;">
                                <div class="text-[11px] font-bold uppercase tracking-[0.3em]" style="color: rgba(255,255,255,.5);">Sedang Bersiap</div>
                                <div class="font-display font-extrabold text-white text-[30px] leading-tight mt-3 vt-rise" wire:key="berikut-{{ $berikutnya['id'] }}">{{ $berikutnya['nama'] }}</div>
                                <div class="text-sm font-medium mt-2" style="color: rgba(255,255,255,.55);">Nomor {{ $berikutnya['no'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center gap-5">
                    <div class="flex h-20 w-20 items-center justify-center vt-panel" style="border-radius: 10px;">
                        <i class="ti ti-list-numbers text-3xl" style="color: var(--vt-line);"></i>
                    </div>
                    <p class="text-lg" style="color: var(--vt-muted);">Belum ada urutan tampil — pilih tingkat lewat URL (?categoryId=...).</p>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: VOTE ==================== --}}
    @elseif($mode === 'vote')
        <main class="flex-1 flex flex-col px-14 py-8 overflow-hidden" wire:poll.10s="refreshData">
            <div class="shrink-0 flex items-center justify-center gap-5 mb-6">
                <span class="h-px w-32" style="background: var(--vt-ink);"></span>
                <h2 class="font-display text-[30px] font-extrabold tracking-wide" style="color: var(--vt-ink);">Klasemen Vote</h2>
                <span class="h-px w-32" style="background: var(--vt-ink);"></span>
            </div>
            <div class="flex items-center justify-center gap-14 mb-7 shrink-0">
                <div class="text-center">
                    <span class="font-mono text-[52px] font-bold tabular-nums leading-none" style="color: var(--vt-ink);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                    <span class="vt-kicker text-[11px] block mt-2" style="color: var(--vt-muted);">Total Vote</span>
                </div>
                <div class="h-14 w-px" style="background: var(--vt-line);"></div>
                <div class="text-center">
                    <span class="font-mono text-[52px] font-bold tabular-nums leading-none" style="color: var(--vt-ink);">{{ count($topVote) }}</span>
                    <span class="vt-kicker text-[11px] block mt-2" style="color: var(--vt-muted);">Kontingen</span>
                </div>
            </div>

            @if(count($topVote) > 0)
                @php
                    $top3 = array_slice($topVote, 0, 3);
                    $rest = array_slice($topVote, 3);
                    $maxV = max($topVote[0]['total_votes'] ?? 1, 1);
                @endphp

                <div class="flex-1 flex flex-col gap-6 min-h-0">
                    {{-- Papan peringkat 1-3: baris utama + dua panel --}}
                    <div class="flex gap-6 shrink-0">
                        @foreach($top3 as $i => $r)
                            @php
                                $warna = $i === 0 ? 'var(--color-primary)' : ($i === 1 ? '#8a6d1f' : '#4d6a8a');
                            @endphp
                            <div class="flex-1 vt-panel flex flex-col items-center p-6 vt-rise {{ $i === 0 ? 'row-span-1' : '' }}"
                                 style="animation-delay: {{ $i * 0.12 }}s; {{ $i === 0 ? 'border-top: 5px solid var(--color-primary);' : '' }}">
                                <span class="text-[11px] font-bold uppercase tracking-[0.25em] px-3 py-1 text-white" style="background: {{ $warna }};">
                                    {{ ['Juara 1', 'Juara 2', 'Juara 3'][$i] }}
                                </span>
                                @if($r['logo_sekolah'])
                                    <img src="{{ asset('storage/' . $r['logo_sekolah']) }}" class="h-16 w-16 rounded-full object-cover mt-4" style="border: 2px solid {{ $i === 0 ? 'var(--color-primary)' : 'var(--vt-line)' }};">
                                @else
                                    <span class="flex h-16 w-16 items-center justify-center rounded-full mt-4" style="background: var(--vt-paper); border: 1px solid var(--vt-line);"><i class="ti ti-school text-2xl" style="color: var(--vt-muted);"></i></span>
                                @endif
                                <h3 class="mt-3 text-[16px] font-bold text-center leading-tight line-clamp-2" style="color: var(--vt-ink);">{{ $r['display_name'] ?? $r['nama_sekolah'] }}</h3>
                                <span class="font-mono font-bold text-[30px] tabular-nums mt-1" style="color: {{ $warna }};">{{ number_format($r['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                <span class="vt-kicker text-[10px]" style="color: var(--vt-muted);">suara</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Peringkat 4+: daftar bar proporsional --}}
                    @if(count($rest) > 0)
                        <div class="vt-panel flex-1 flex flex-col overflow-hidden min-h-0">
                            <div class="shrink-0 flex items-center gap-3 px-7 py-3.5" style="border-bottom: 1px solid var(--vt-line);">
                                <i class="ti ti-trophy text-base" style="color: var(--color-primary);"></i>
                                <span class="vt-kicker text-[12px]" style="color: var(--vt-ink);">Peringkat 4+</span>
                            </div>
                            <div class="flex-1 overflow-y-auto vt-scroll divide-y flex flex-col justify-center" style="border-color: var(--vt-line);">
                                @foreach($rest as $i => $reg)
                                    @php $pct = min((($reg['total_votes'] ?? 0) / $maxV) * 100, 100); @endphp
                                    <div class="relative flex items-center gap-4 px-7 py-3 overflow-hidden vt-stagger" style="--i: {{ $i }};">
                                        <div class="absolute inset-y-0 left-0 pointer-events-none vt-bar" style="width: {{ $pct }}%; background: rgba(var(--color-primary-rgb),0.08);"></div>
                                        <span class="relative shrink-0 inline-flex items-center justify-center h-8 w-8 text-sm font-bold" style="border: 1px solid var(--vt-line); color: var(--vt-muted);">{{ $i + 4 }}</span>
                                        <span class="relative flex-1 text-[17px] font-semibold truncate" style="color: var(--vt-ink);">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</span>
                                        <span class="relative font-mono font-bold text-[19px] tabular-nums" style="color: var(--color-primary);">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="flex h-20 w-20 items-center justify-center mx-auto mb-5 vt-panel" style="border-radius: 10px;">
                            <i class="ti ti-heart-off text-3xl" style="color: var(--vt-line);"></i>
                        </div>
                        <h3 class="font-display text-2xl font-bold" style="color: var(--vt-muted);">Belum Ada Vote</h3>
                    </div>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: CHAMPION ==================== --}}
    @elseif($mode === 'champion')
        <main class="flex-1 flex flex-col items-center justify-center px-16 relative overflow-hidden" style="background: var(--vt-paper);">
            <div class="absolute top-0 inset-x-0 h-[6px]" style="background: var(--color-primary);"></div>
            @if($championRanking && count($championRanking) > 0)
                <div class="text-center flex flex-col items-center gap-6 max-w-[1300px]">
                    @if($championName)
                        <div class="vt-kicker text-[15px] vt-rise" style="color: var(--color-primary);">
                            <i class="ti ti-trophy-filled me-2"></i>{{ $championName }}
                        </div>
                    @endif
                    @if($championTitle)
                        <div class="font-display font-extrabold vt-rise" style="font-size: 72px; animation-delay: .1s; letter-spacing: -0.02em; color: var(--vt-ink);">
                            {{ $championTitle }}
                        </div>
                    @endif
                    {{-- Pemenang: blok tinta dengan teks kertas --}}
                    <div class="relative px-16 py-9 mt-2 vt-rise" style="background: var(--vt-ink); border-radius: 12px; animation-delay: .22s; box-shadow: 10px 10px 0 rgba(var(--color-primary-rgb),0.85);">
                        @if($championRanking[0]['nama'])
                            <div class="font-display font-extrabold text-white leading-tight" style="font-size: 52px;">
                                {{ $championRanking[0]['nama'] }}
                            </div>
                        @endif
                        <div class="font-mono text-[22px] font-bold mt-3" style="color: #d9e2ff;">
                            Nilai {{ number_format($championRanking[0]['total'], 2, ',', '.') }}
                        </div>
                    </div>

                    {{-- Runner-up --}}
                    @if(count($championRanking) > 1)
                        <div class="flex gap-6 mt-8 w-full justify-center">
                            @foreach(array_slice($championRanking, 1) as $i => $ps)
                                <div class="vt-panel px-10 py-5 flex items-center gap-5 vt-rise" style="animation-delay: {{ 0.4 + $i * 0.15 }}s; min-width: 380px; border-top: 4px solid {{ $i === 0 ? '#8a6d1f' : '#4d6a8a' }};">
                                    <span class="shrink-0 inline-flex items-center justify-center h-12 w-12 text-lg font-extrabold text-white" style="background: {{ $i === 0 ? '#8a6d1f' : '#4d6a8a' }};">
                                        {{ $ps['rank'] }}
                                    </span>
                                    <div class="text-left flex-1 min-w-0">
                                        @if($ps['title'])
                                            <div class="vt-kicker text-[11px]" style="color: var(--vt-muted);">{{ $ps['title'] }}</div>
                                        @endif
                                        <div class="text-[20px] font-bold truncate" style="color: var(--vt-ink);">{{ $ps['nama'] }}</div>
                                    </div>
                                    <span class="font-mono font-bold text-[22px] tabular-nums" style="color: var(--color-primary);">{{ number_format($ps['total'], 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center">
                    <div class="flex h-20 w-20 items-center justify-center mx-auto mb-5 vt-panel" style="border-radius: 10px;">
                        <i class="ti ti-trophy text-3xl" style="color: var(--vt-line);"></i>
                    </div>
                    <h3 class="font-display text-2xl font-bold" style="color: var(--vt-muted);">Belum Ada Juara</h3>
                    <p class="mt-2" style="color: var(--vt-muted);">Hasil akan tampil setelah kategori juara dipublikasikan.</p>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: RUNDOWN ==================== --}}
    @elseif($mode === 'rundown')
        <main class="flex-1 flex flex-col items-center justify-center px-20 overflow-hidden" wire:poll.30s="refreshData" style="background: var(--vt-paper);">
            <div class="w-full max-w-[1400px]">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center gap-3 px-6 py-2.5 mb-5" style="background: #fff; border: 1px solid var(--vt-line); border-radius: 999px;">
                        <span class="h-2 w-2" style="background: var(--color-primary);"></span>
                        <span class="vt-kicker text-[13px]" style="color: var(--vt-ink);">Agenda <span x-text="date"></span></span>
                    </div>
                </div>

                @if(count($rundowns) > 0)
                    <div class="flex flex-col gap-3 vt-stagger">
                        @foreach($rundowns as $r)
                            @php $aktif = $rundownSekarang !== null && $r['id'] == $rundownSekarang; @endphp
                            <div class="flex items-center gap-8 px-10 py-6 transition-all"
                                 style="border-radius: 10px; {{ $aktif
                                    ? 'background: var(--vt-ink); border: 1px solid var(--vt-ink);'
                                    : 'background: #fff; border: 1px solid var(--vt-line);' }}">
                                <span class="font-mono text-[28px] font-bold tabular-nums shrink-0" style="{{ $aktif ? 'color: #d9e2ff;' : 'color: var(--color-primary);' }}">
                                    {{ $r['start'] }}
                                </span>
                                <div class="h-10 w-px" style="{{ $aktif ? 'background: rgba(255,255,255,.2);' : 'background: var(--vt-line);' }}"></div>
                                <span class="flex-1 text-[26px] font-bold" style="{{ $aktif ? 'color: #fff;' : 'color: #3d4048;' }}">{{ $r['title'] }}</span>
                                @if($aktif)
                                    <span class="shrink-0 inline-flex items-center gap-2.5 px-5 py-2 text-[12px] font-bold uppercase tracking-[0.18em] text-white" style="background: var(--color-primary);">
                                        <span class="h-2 w-2 bg-white vt-beat"></span> Sekarang
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center gap-5 py-16">
                        <div class="flex h-20 w-20 items-center justify-center vt-panel" style="border-radius: 10px;">
                            <i class="ti ti-folder-off text-3xl" style="color: var(--vt-line);"></i>
                        </div>
                        <p class="text-lg" style="color: var(--vt-muted);">Rundown belum tersedia.</p>
                    </div>
                @endif
            </div>
        </main>

    {{-- ==================== MODE: SPONSOR ==================== --}}
    @elseif($mode === 'sponsor')
        <main class="flex-1 flex flex-col relative overflow-hidden" style="background: var(--vt-paper);">
            <div class="absolute top-0 inset-x-0 h-[6px]" style="background: var(--color-primary);"></div>
            <div class="text-center pt-14 pb-8 shrink-0">
                <span class="vt-kicker text-[15px]" style="color: var(--vt-muted);">Didukung Oleh</span>
            </div>
            @if(count($sponsorLogos) > 0)
                <div class="flex-1 flex flex-col justify-center px-20 pb-14">
                    <div class="grid grid-cols-3 gap-8 vt-stagger">
                        @foreach($sponsorLogos as $i => $sp)
                            <div class="vt-panel flex items-center justify-center p-10 min-h-[200px]" style="--i: {{ $i % 6 }}; border-bottom: 4px solid var(--vt-ink);">
                                @if(!empty($sp['logo']))
                                    <img src="{{ asset('storage/' . $sp['logo']) }}" alt="{{ $sp['name'] }}" class="max-h-[120px] max-w-full object-contain">
                                @else
                                    <span class="font-display text-[26px] font-extrabold text-center" style="color: var(--vt-ink);">{{ $sp['name'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center gap-5">
                    <div class="flex h-20 w-20 items-center justify-center vt-panel" style="border-radius: 10px;">
                        <i class="ti ti-heart-handshake text-3xl" style="color: var(--vt-line);"></i>
                    </div>
                    <p class="text-lg" style="color: var(--vt-muted);">Belum ada sponsor &amp; media partner.</p>
                </div>
            @endif
        </main>

    {{-- ==================== MODE: LOOP ==================== --}}
    @elseif($mode === 'loop')
        <main class="flex-1 relative overflow-hidden" x-data="{ s: 0 }"
              x-init="setInterval(() => s = (s + 1) % 4, 30000)">
            {{-- Slide tiap 30 dtk: welcome/vote/rundown/sponsor diputar --}}

            <div x-show="s === 0" class="absolute inset-0 flex flex-col" style="background: var(--vt-paper);">
                <div class="absolute top-0 inset-x-0 h-[6px]" style="background: var(--color-primary);"></div>
                <div class="flex-1 flex flex-col items-center justify-center gap-8">
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="h-28 w-28 rounded-xl object-cover" style="border: 1px solid var(--vt-ink); box-shadow: 6px 6px 0 rgba(var(--color-primary-rgb),0.9);">
                    @endif
                    <h1 class="font-display font-extrabold text-[64px] tracking-tight text-center px-20" style="color: var(--vt-ink);">{{ $eventner->nama_event }}</h1>
                    <div class="font-mono text-3xl font-bold tabular-nums" style="color: var(--vt-muted);" x-text="time"></div>
                </div>
            </div>

            <div x-show="s === 1" x-cloak class="absolute inset-0 flex flex-col" style="background: var(--vt-paper);" wire:poll.15s="refreshData">
                <div class="flex-1 flex items-center justify-center gap-16 flex-col px-20">
                    <div class="text-center">
                        <span class="vt-kicker text-[13px] block mb-4" style="color: var(--vt-muted);">Klasemen Vote</span>
                        <span class="font-mono font-bold text-[96px] tabular-nums leading-none" style="color: var(--vt-ink);">{{ number_format($totalVoteCount, 0, ',', '.') }}</span>
                        <span class="vt-kicker text-[13px] block mt-3" style="color: var(--vt-muted);">Total Suara</span>
                    </div>
                    <div class="w-full max-w-[1200px] flex flex-col gap-2.5">
                        @foreach(array_slice($topVote, 0, 5) as $i => $reg)
                            <div class="flex items-center gap-5 px-8 py-4" style="background: #fff; border: 1px solid var(--vt-line); border-radius: 8px;">
                                <span class="shrink-0 inline-flex items-center justify-center h-11 w-11 text-base font-extrabold"
                                      style="{{ $i === 0 ? 'background: var(--color-primary); color: #fff;' : 'border: 1px solid var(--vt-line); color: var(--vt-muted);' }}">
                                    {{ $i + 1 }}
                                </span>
                                <span class="flex-1 text-[20px] font-semibold truncate" style="color: var(--vt-ink);">{{ $reg['display_name'] ?? $reg['nama_sekolah'] }}</span>
                                <span class="font-mono font-bold text-[24px] tabular-nums" style="color: var(--color-primary);">{{ number_format($reg['total_votes'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div x-show="s === 2" x-cloak class="absolute inset-0 flex flex-col" style="background: var(--vt-paper);">
                <div class="flex-1 flex flex-col justify-center px-24 gap-3">
                    <span class="vt-kicker text-[13px] text-center mb-6" style="color: var(--vt-muted);">Agenda</span>
                    @foreach(array_slice($rundowns, 0, 6) as $r)
                        <div class="flex items-center gap-6 px-8 py-4" style="background: #fff; border: 1px solid var(--vt-line); border-radius: 8px;">
                            <span class="font-mono text-xl font-bold shrink-0 tabular-nums" style="color: var(--color-primary);">{{ $r['start'] }}</span>
                            <span class="text-[20px] font-semibold" style="color: #3d4048;">{{ $r['title'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div x-show="s === 3" x-cloak class="absolute inset-0 flex flex-col" style="background: var(--vt-paper);">
                <div class="flex-1 flex items-center justify-center px-20">
                    <div class="grid grid-cols-2 gap-7 w-full">
                        @foreach(array_slice($sponsorLogos, 0, 4) as $sp)
                            <div class="vt-panel flex items-center justify-center p-10 min-h-[180px]" style="border-bottom: 4px solid var(--vt-ink);">
                                @if(!empty($sp['logo']))
                                    <img src="{{ asset('storage/' . $sp['logo']) }}" alt="{{ $sp['name'] }}" class="max-h-[110px] max-w-full object-contain">
                                @else
                                    <span class="font-display text-2xl font-extrabold" style="color: var(--vt-ink);">{{ $sp['name'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Indikator slide --}}
            <div class="shrink-0 flex items-center justify-center gap-2.5 py-4" style="border-top: 1px solid var(--vt-line); background: #fff;">
                <template x-for="(i) in 4" :key="i">
                    <span class="transition-all duration-500"
                          :style="s === i - 1 ? 'width: 30px; height: 6px; background: var(--color-primary);' : 'width: 6px; height: 6px; background: rgba(21,23,28,.2);'"></span>
                </template>
            </div>
        </main>
    @endif

    {{-- ==================== MARQUEE BAWAH (bukan welcome/loop) ==================== --}}
    @if(!in_array($mode, ['welcome', 'loop']))
        <footer class="shrink-0 flex items-center h-[56px] px-12 gap-8 relative overflow-hidden" style="background: var(--vt-ink); border-top: 3px solid var(--color-primary);">
            @if(count($sponsorLogos) > 0)
                <div class="flex-1 min-w-0 relative overflow-hidden">
                    <div class="flex items-center gap-14 whitespace-nowrap" style="animation: vt-marquee 40s linear infinite; width: max-content;">
                        @foreach(array_merge($sponsorLogos, $sponsorLogos) as $sp)
                            <span class="text-[15px] font-semibold flex items-center gap-2.5" style="color: rgba(255,255,255,.72);">
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
            <span class="shrink-0 text-[12px] font-medium" style="color: rgba(255,255,255,.4);">Powered by <strong class="font-bold" style="color: rgba(255,255,255,.75);">{{ app_name() }}</strong></span>
        </footer>
    @endif
</div>
