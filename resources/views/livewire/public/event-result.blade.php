<div class="min-h-screen bg-surface">

    {{-- Header --}}
    <div class="container-landing pt-6">
        <div class="surface-card p-6 text-center">
            <div class="flex items-center justify-center gap-3 mb-2">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                    <i class="ti ti-trophy text-2xl"></i>
                </div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-deep-slate sm:text-3xl">
                    Hasil Perlombaan
                </h1>
            </div>
            <p class="text-sm text-on-surface-variant font-semibold">{{ $eventner->nama_event }}</p>
        </div>
    </div>

    {{-- Category Tabs --}}
    @if(count($categories) > 1)
        <div class="container-landing pt-4">
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

    {{-- Champion Rankings --}}
    <div class="container-landing py-8">
        @forelse($allRankings as $group)
            <div class="surface-card mb-6 overflow-hidden">
                {{-- Champion Category Header --}}
                <div class="bg-deep-slate px-6 py-4">
                    <h3 class="font-display text-lg font-bold text-white flex items-center gap-2 m-0">
                        <i class="ti ti-trophy text-amber-400"></i>
                        {{ $group['champion']->name }}
                    </h3>
                    @if($group['champion']->description)
                        <p class="text-white/60 text-sm mt-1 mb-0">{{ $group['champion']->description }}</p>
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

                {{-- Rankings Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                <th class="px-4 py-3 text-center text-xs font-bold text-on-surface-variant uppercase tracking-wider w-16">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-on-surface-variant uppercase tracking-wider">Sekolah</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-on-surface-variant uppercase tracking-wider w-36">Gelar</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-on-surface-variant uppercase tracking-wider w-24 pr-6">Skor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @foreach($group['participants'] as $ps)
                                @php
                                    $rowBg = '';
                                    if ($ps['rank'] == 1) $rowBg = 'bg-amber-50';
                                    elseif ($ps['rank'] == 2) $rowBg = 'bg-slate-50';
                                    elseif ($ps['rank'] == 3) $rowBg = 'bg-sky-50';
                                @endphp
                                <tr class="{{ $rowBg }}">
                                    <td class="px-4 py-3.5 text-center">
                                        @if($ps['rank'] == 1)
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-amber-500 text-white font-bold text-sm shadow-sm">1</span>
                                        @elseif($ps['rank'] == 2)
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-slate-400 text-white font-bold text-sm shadow-sm">2</span>
                                        @elseif($ps['rank'] == 3)
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-sky-500 text-white font-bold text-sm shadow-sm">3</span>
                                        @else
                                            <span class="font-bold text-on-surface-variant">{{ $ps['rank'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            @if($ps['participant']->logo_sekolah)
                                                <img src="{{ asset('storage/' . $ps['participant']->logo_sekolah) }}" class="h-9 w-9 rounded-full border border-outline-variant/30 object-cover shrink-0" alt="">
                                            @else
                                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary shrink-0">
                                                    <i class="ti ti-school"></i>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <span class="font-bold text-deep-slate block truncate">{{ $ps['participant']->nama_sekolah }}</span>
                                                <span class="text-xs text-on-surface-variant">NPSN: {{ $ps['participant']->npsn }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($ps['title'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                                <i class="ti ti-award"></i> {{ $ps['title'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right pr-6">
                                        <span class="font-display font-extrabold text-primary text-base">{{ number_format($ps['total'], 0) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
