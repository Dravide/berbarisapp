<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-16">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-trophy"></i>
                Hall of Fame
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                {{ $penyelenggara }}
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Rekap juara seluruh event yang diselenggarakan
                <strong class="text-secondary font-semibold">{{ $penyelenggara }}</strong> di Berbaris App.
            </p>
            <div class="mt-4">
                <a href="{{ route('landing') }}#eventners" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Lihat Semua Event
                </a>
            </div>
        </div>
    </div>

    {{-- ========== DAFTAR JUARA ========== --}}
    <div class="container-landing py-8 space-y-6">
        @if(count($events) > 0)
            @foreach($events as $event)
                <div class="surface-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                        <h2 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                            <i class="ti ti-calendar-event text-primary text-lg"></i>
                            {{ $event['nama_event'] }}
                        </h2>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                            {{ $event['tanggal'] ? \Carbon\Carbon::parse($event['tanggal'])->translatedFormat('d F Y') : '' }}
                        </span>
                    </div>

                    <div class="divide-y divide-outline-variant/30">
                        @foreach($event['categories'] as $kategori)
                            <div class="px-6 py-4">
                                <h3 class="text-sm font-extrabold text-deep-slate mb-3 inline-flex items-center gap-2">
                                    <i class="ti ti-trophy text-amber-500"></i> {{ $kategori['name'] }}
                                </h3>
                                <ul class="space-y-2">
                                    @foreach($kategori['winners'] as $w)
                                        <li class="flex items-center gap-3 rounded-lg border border-outline-variant/30 bg-surface-container-lowest px-3 py-2.5">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-extrabold border
                                                {{ $w['rank'] === 1 ? 'bg-amber-500/15 text-amber-600 border-amber-500/30' : ($w['rank'] === 2 ? 'bg-slate-400/15 text-slate-600 border-slate-400/30' : ($w['rank'] === 3 ? 'bg-orange-500/15 text-orange-600 border-orange-500/30' : 'bg-primary/10 text-primary border-outline-variant/30')) }}">
                                                {{ $w['rank'] }}
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-xs font-bold text-deep-slate leading-tight">{{ $w['title'] }}</div>
                                                <div class="text-xs text-on-surface-variant mt-0.5 truncate">{{ $w['nama'] }}</div>
                                            </div>
                                            <span class="chip py-1 px-2.5 !text-[11px] shrink-0">Nilai {{ number_format((float) $w['total'], 2) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-6 py-3 border-t border-outline-variant/30 bg-surface-container-low">
                        <a href="{{ $event['url'] }}" class="text-xs font-bold text-primary hover:text-secondary text-decoration-none inline-flex items-center gap-1">
                            Detail event <i class="ti ti-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        @else
            <div class="surface-card p-10 text-center">
                <i class="ti ti-trophy text-5xl text-on-surface-variant/30 block mb-3"></i>
                <h4 class="font-display text-base font-bold text-deep-slate mb-1">Belum ada juara yang diumumkan.</h4>
                <p class="text-xs text-on-surface-variant">Rekap juara akan tampil di sini setelah panitia mengumumkan hasil.</p>
            </div>
        @endif
    </div>

</div>
