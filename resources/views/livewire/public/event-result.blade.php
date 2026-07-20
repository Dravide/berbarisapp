<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-16">
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
                <a href="{{ event_url($eventner, 'detail') }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
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

    {{-- ========== CATEGORY SELECT ========== --}}
    @if(count($categories) > 1)
        <div class="container-landing pt-6">
            <div class="mx-auto" style="max-width: 420px;">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2 text-center">
                    <i class="ti ti-filter text-primary"></i> Pilih Kategori Lomba
                </label>
                <div class="relative">
                    <select
                        wire:model.live="selectedCategoryId"
                        wire:change="switchCategory(selectedCategoryId)"
                        class="w-full appearance-none bg-white border border-outline-variant/40 rounded-xl px-5 py-3.5 text-sm font-bold text-deep-slate shadow-sm cursor-pointer
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition">
                        @foreach($categories as $cat)
                            @php $label = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                            <option value="{{ $cat['id'] }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <i class="ti ti-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant pointer-events-none"></i>
                </div>
            </div>
        </div>
    @endif

    {{-- ========== CHAMPION RANKINGS ========== --}}
    <div class="container-landing py-8">
        @forelse($allRankings as $group)
            @php
                $top3 = collect($group['participants'])->where('rank', '<=', 3)->values();
                $rest = collect($group['participants'])->where('rank', '>', 3)->values();
                $rank1 = $top3->firstWhere('rank', 1);
                $rank2 = $top3->firstWhere('rank', 2);
                $rank3 = $top3->firstWhere('rank', 3);
            @endphp
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

                {{-- ===== PODIUM TOP 3 ===== --}}
                @if($top3->count() > 0)
                    <div class="px-6 pt-8 pb-4">
                        <div class="flex items-end justify-center gap-3 sm:gap-6 max-w-lg mx-auto">

                            {{-- 2nd Place --}}
                            <div class="flex flex-col items-center flex-1 max-w-[140px]">
                                @if($rank2)
                                    @if($rank2['participant']->logo_sekolah)
                                        <img src="{{ asset('storage/' . $rank2['participant']->logo_sekolah) }}" alt="" class="h-14 w-14 sm:h-16 sm:w-16 rounded-full object-cover border-2 border-slate-300 shadow-md mb-2">
                                    @else
                                        <div class="flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 border-2 border-slate-300 shadow-md mb-2">
                                            <i class="ti ti-school text-2xl"></i>
                                        </div>
                                    @endif
                                    <h4 class="text-xs sm:text-sm font-bold text-deep-slate text-center leading-tight mb-1 line-clamp-2">{{ $rank2['participant']->nama_sekolah }}</h4>
                                    @if($rank2['title'])
                                        <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 mb-1.5">
                                            <i class="ti ti-award"></i> {{ $rank2['title'] }}
                                        </span>
                                    @endif
                                    <span class="font-display font-extrabold text-primary text-sm">{{ number_format($rank2['total'], 0) }}</span>
                                    <div class="w-full bg-slate-200 border border-slate-200/80 rounded-t-xl mt-3 flex items-center justify-center" style="height: 80px;">
                                        <span class="font-display text-3xl font-extrabold text-slate-400">2</span>
                                    </div>
                                @else
                                    <div class="w-full bg-slate-50 border border-slate-200/50 rounded-t-xl" style="height: 80px;"></div>
                                @endif
                            </div>

                            {{-- 1st Place --}}
                            <div class="flex flex-col items-center flex-1 max-w-[160px]">
                                @if($rank1)
                                    <div class="relative mb-2">
                                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                                            <i class="ti ti-crown-filled text-amber-400 text-2xl drop-shadow-sm"></i>
                                        </div>
                                        @if($rank1['participant']->logo_sekolah)
                                            <img src="{{ asset('storage/' . $rank1['participant']->logo_sekolah) }}" alt="" class="h-18 w-18 sm:h-20 sm:w-20 rounded-full object-cover border-3 border-amber-400 shadow-lg ring-4 ring-amber-400/20">
                                        @else
                                            <div class="flex h-18 w-18 sm:h-20 sm:w-20 items-center justify-center rounded-full bg-amber-50 text-amber-500 border-3 border-amber-400 shadow-lg ring-4 ring-amber-400/20">
                                                <i class="ti ti-school text-3xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <h4 class="text-xs sm:text-sm font-bold text-deep-slate text-center leading-tight mb-1 line-clamp-2">{{ $rank1['participant']->nama_sekolah }}</h4>
                                    @if($rank1['title'])
                                        <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 mb-1.5">
                                            <i class="ti ti-award"></i> {{ $rank1['title'] }}
                                        </span>
                                    @endif
                                    <span class="font-display font-extrabold text-primary text-base">{{ number_format($rank1['total'], 0) }}</span>
                                    <div class="w-full bg-amber-300 border border-amber-300/80 rounded-t-xl mt-3 flex items-center justify-center" style="height: 110px;">
                                        <span class="font-display text-4xl font-extrabold text-amber-500/80">1</span>
                                    </div>
                                @endif
                            </div>

                            {{-- 3rd Place --}}
                            <div class="flex flex-col items-center flex-1 max-w-[140px]">
                                @if($rank3)
                                    @if($rank3['participant']->logo_sekolah)
                                        <img src="{{ asset('storage/' . $rank3['participant']->logo_sekolah) }}" alt="" class="h-14 w-14 sm:h-16 sm:w-16 rounded-full object-cover border-2 border-sky-300 shadow-md mb-2">
                                    @else
                                        <div class="flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-full bg-sky-50 text-sky-500 border-2 border-sky-300 shadow-md mb-2">
                                            <i class="ti ti-school text-2xl"></i>
                                        </div>
                                    @endif
                                    <h4 class="text-xs sm:text-sm font-bold text-deep-slate text-center leading-tight mb-1 line-clamp-2">{{ $rank3['participant']->nama_sekolah }}</h4>
                                    @if($rank3['title'])
                                        <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 text-[10px] font-bold text-emerald-600 mb-1.5">
                                            <i class="ti ti-award"></i> {{ $rank3['title'] }}
                                        </span>
                                    @endif
                                    <span class="font-display font-extrabold text-primary text-sm">{{ number_format($rank3['total'], 0) }}</span>
                                    <div class="w-full bg-sky-200 border border-sky-200/80 rounded-t-xl mt-3 flex items-center justify-center" style="height: 60px;">
                                        <span class="font-display text-3xl font-extrabold text-sky-400/80">3</span>
                                    </div>
                                @else
                                    <div class="w-full bg-sky-50 border border-sky-200/50 rounded-t-xl" style="height: 60px;"></div>
                                @endif
                            </div>

                        </div>
                    </div>
                @endif

                {{-- ===== REMAINING RANKINGS (4+) ===== --}}
                @if($rest->count() > 0)
                    <div class="border-t border-outline-variant/30">
                        <div class="divide-y divide-outline-variant/30">
                            @foreach($rest as $ps)
                                <div class="flex items-center gap-4 px-6 py-4 hover:bg-surface-container-lowest transition duration-150">
                                    {{-- Rank Badge --}}
                                    <div class="shrink-0 w-10 text-center">
                                        <span class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-surface-container border border-outline-variant/30 font-bold text-sm text-on-surface-variant">{{ $ps['rank'] }}</span>
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
                @endif
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
