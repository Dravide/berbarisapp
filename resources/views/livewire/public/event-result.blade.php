<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary text-white py-12 md:py-16">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-trophy text-amber-400"></i>
                Hasil Perlombaan
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                Pengumuman Juara
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Hasil penilaian dan peringkat juara resmi dari event <strong class="text-secondary font-semibold">{{ $eventner->nama_event }}</strong>.
            </p>
            <div class="mt-4">
                <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Kembali Ke Detail Event
                </a>
            </div>
        </div>
    </div>

    {{-- ========== QUICK STATS ========== --}}
    @php
        $totalChampionPublic = \App\Models\ChampionCategory::where('eventner_id', $eventner->id)->where('is_public', true)->count();
        $totalChampionAll = \App\Models\ChampionCategory::where('eventner_id', $eventner->id)->count();
    @endphp
    <div class="container-landing -mt-8 relative z-20">
        <div class="surface-card p-6">
            <div class="grid gap-6 grid-cols-2 md:grid-cols-3">
                <div class="text-center">
                    <span class="text-3xl font-extrabold text-primary font-display block mb-1">
                        {{ count($categories) }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Kategori Lomba</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-amber-500 font-display block mb-1">
                        {{ $totalChampionPublic }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Kategori Juara</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-emerald-600 font-display block mb-1">
                        {{ collect($allRankings)->sum(fn($g) => count($g['participants'])) }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Pemenang</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== CATEGORY TABS ========== --}}
    @if(count($categories) > 1)
        <div class="container-landing pt-6">
            <div class="flex flex-wrap gap-2 justify-center">
                @foreach($categories as $cat)
                    <button
                        wire:click="switchCategory({{ $cat['id'] }})"
                        class="px-4 py-2 rounded-full text-sm font-bold transition border
                            {{ $selectedCategoryId == $cat['id']
                                ? 'bg-primary text-white border-primary shadow-sm'
                                : 'bg-white text-on-surface-variant border-outline-variant/40 hover:bg-primary/5 hover:text-primary hover:border-primary/30' }}">
                        {{ $cat['name'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========== CHAMPION RANKINGS ========== --}}
    <div class="container-landing py-8">
        @forelse($allRankings as $group)
            <div class="surface-card mb-6 overflow-hidden">
                {{-- Champion Category Header --}}
                <div class="flex items-center justify-between bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                        <i class="ti ti-trophy text-amber-500 text-lg"></i>
                        {{ $group['champion']->name }}
                    </h3>
                    @if($group['champion']->description)
                        <span class="chip py-0.5 px-2.5 text-xs font-bold leading-normal bg-primary/10">{{ $group['champion']->description }}</span>
                    @endif
                </div>

                {{-- Rank Title Legend --}}
                @if($group['rankTitles']->count() > 0)
                    <div class="px-6 pt-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($group['rankTitles'] as $rt)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 px-3 py-1.5 text-xs font-bold text-amber-700">
                                    <i class="ti ti-medal"></i> {{ $rt->title }}
                                    <span class="text-amber-500/60 font-medium">(Rank {{ $rt->rank_start }}-{{ $rt->rank_end }})</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Rankings List --}}
                <div class="divide-y divide-outline-variant/30">
                    @foreach($group['participants'] as $ps)
                        <div class="flex items-center gap-4 px-6 py-4 hover:bg-surface-container-lowest transition duration-150">
                            {{-- Rank Badge --}}
                            <div class="shrink-0 w-10 text-center">
                                @if($ps['rank'] == 1)
                                    <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-amber-500 text-white font-bold text-sm shadow-sm">1</span>
                                @elseif($ps['rank'] == 2)
                                    <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-slate-400 text-white font-bold text-sm shadow-sm">2</span>
                                @elseif($ps['rank'] == 3)
                                    <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-sky-500 text-white font-bold text-sm shadow-sm">3</span>
                                @else
                                    <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-surface-container border border-outline-variant/30 font-bold text-sm text-on-surface-variant">{{ $ps['rank'] }}</span>
                                @endif
                            </div>

                            {{-- School Info --}}
                            @if($ps['participant']->logo_sekolah)
                                <img src="{{ asset('storage/' . $ps['participant']->logo_sekolah) }}" alt="" class="h-11 w-11 rounded-xl object-cover border border-outline-variant/30 shadow-sm shrink-0">
                            @else
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/5 text-primary border border-outline-variant/30 shrink-0">
                                    <i class="ti ti-school text-xl"></i>
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-deep-slate leading-tight mb-1 inline-flex items-center gap-2 flex-wrap">
                                    {{ $ps['participant']->nama_sekolah }}
                                    @if($ps['title'])
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-600 border border-emerald-500/20">
                                            <i class="ti ti-award"></i> {{ $ps['title'] }}
                                        </span>
                                    @endif
                                </h4>
                                <span class="text-xs text-on-surface-variant block">NPSN: {{ $ps['participant']->npsn }}</span>
                            </div>

                            {{-- Score --}}
                            <div class="shrink-0 text-right">
                                <span class="font-display font-extrabold text-primary text-lg">{{ number_format($ps['total'], 0) }}</span>
                                <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider block">Skor</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="surface-card p-12 text-center">
                <div class="flex items-center justify-center mb-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500">
                        <i class="ti ti-trophy-off text-3xl"></i>
                    </div>
                </div>
                <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Belum Ada Hasil Perlombaan</h3>
                <p class="text-sm text-on-surface-variant max-w-md mx-auto">
                    Data hasil perlombaan akan muncul setelah penilaian selesai dan kategori juara dipublikasikan oleh penyelenggara.
                </p>
            </div>
        @endforelse
    </div>

</div>
