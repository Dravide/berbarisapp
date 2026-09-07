<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-14">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-list-check text-amber-400"></i>
                Detail Penilaian
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                {{ $registration->display_name }}
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Rincian nilai dari setiap juri untuk event <strong class="text-secondary font-semibold">{{ $eventner->nama_event }}</strong>.
            </p>
            <div class="mt-4">
                <a href="{{ event_url($eventner, 'results') }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Kembali Ke Hasil Perlombaan
                </a>
            </div>
        </div>
    </div>

    {{-- ========== KARTU IDENTITAS ========== --}}
    <div class="container-landing -mt-8 relative z-20">
        <div class="surface-card p-6">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                @if($registration->logo_sekolah)
                    <img src="{{ asset('storage/' . $registration->logo_sekolah) }}" alt="{{ $registration->nama_sekolah }}" class="h-20 w-20 rounded-2xl object-cover border border-outline-variant/30 shadow-sm shrink-0">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-primary/5 text-primary border border-outline-variant/30 shrink-0">
                        <i class="ti ti-school text-4xl"></i>
                    </div>
                @endif

                <div class="flex-1 text-center sm:text-left min-w-0">
                    <h2 class="font-display text-lg font-bold text-deep-slate leading-tight">{{ $registration->nama_sekolah }}</h2>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-2">
                        <span class="chip bg-primary/10 text-primary py-1 px-2.5 text-xs font-bold leading-normal">
                            <i class="ti ti-category"></i>
                            {{ $registration->competitionCategory ? $registration->competitionCategory->full_name : '-' }}
                        </span>
                        @if($registration->npsn)
                            <span class="chip bg-surface-container text-on-surface-variant py-1 px-2.5 text-xs font-bold leading-normal">
                                <i class="ti ti-id"></i> NPSN {{ $registration->npsn }}
                            </span>
                        @endif
                        @if($registration->nama_pelatih)
                            <span class="chip bg-surface-container text-on-surface-variant py-1 px-2.5 text-xs font-bold leading-normal">
                                <i class="ti ti-user"></i> {{ $registration->nama_pelatih }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="shrink-0 text-center sm:text-right">
                    <span class="font-display text-3xl font-extrabold text-primary block">{{ number_format($finalTotal, 0) }}</span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Poin Akhir</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== PASUKAN ========== --}}
    @if($registration->danton_nama || $registration->participants->count() > 0)
        <div class="container-landing pt-6">
            <div class="surface-card p-6">
                <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                    <i class="ti ti-users-group text-primary text-lg"></i> Susunan Pasukan
                </h3>

                <div class="flex flex-wrap gap-4">
                    {{-- Danton --}}
                    @if($registration->danton_nama)
                        <div class="flex flex-col items-center w-[88px]">
                            @if($registration->danton_foto)
                                <img src="{{ asset('storage/' . $registration->danton_foto) }}" alt="{{ $registration->danton_nama }}" class="h-16 w-16 rounded-2xl object-cover border-2 border-amber-400 shadow-sm">
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 border-2 border-amber-300">
                                    <i class="ti ti-flag text-2xl"></i>
                                </div>
                            @endif
                            <span class="text-[9px] font-bold uppercase tracking-wider text-amber-600 mt-1.5">Danton</span>
                            <span class="text-[11px] font-semibold text-deep-slate text-center leading-tight line-clamp-2">{{ $registration->danton_nama }}</span>
                        </div>
                    @endif

                    {{-- Anggota --}}
                    @foreach($registration->participants as $p)
                        <div class="flex flex-col items-center w-[88px]">
                            @if($p->foto)
                                <img src="{{ asset('storage/' . $p->foto) }}" alt="{{ $p->nama }}" class="h-16 w-16 rounded-2xl object-cover border border-outline-variant/30 shadow-sm">
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-surface-container text-on-surface-variant border border-outline-variant/30">
                                    <i class="ti ti-user text-2xl"></i>
                                </div>
                            @endif
                            <span class="text-[9px] font-bold uppercase tracking-wider text-on-surface-variant mt-1.5">Anggota {{ $loop->iteration }}</span>
                            <span class="text-[11px] font-semibold text-deep-slate text-center leading-tight line-clamp-2">{{ $p->nama }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ========== REKAP NILAI PER JURI ========== --}}
    <div class="container-landing py-6">
        <div class="surface-card">
            <div class="flex items-center justify-between px-6 py-4 bg-surface-container border-b border-outline-variant/40">
                <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                    <i class="ti ti-list-check text-primary text-lg"></i> Rekap Nilai per Juri
                </h3>
                <span class="chip bg-primary/10 text-primary py-0.5 px-2.5 text-xs font-bold leading-normal">{{ $judges->count() }} Juri</span>
            </div>

            @if($judges->count() > 0)
                <div class="divide-y divide-outline-variant/30">
                    @foreach($judges as $judge)
                        @php
                            $judgeRows = $rowsByJudge[$judge->id] ?? [];
                        @endphp
                        <div class="px-6 py-5">
                            {{-- Header juri --}}
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    @if($judge->photo)
                                        <img src="{{ asset('storage/' . $judge->photo) }}" alt="{{ $judge->name }}" class="h-11 w-11 rounded-full object-cover border border-outline-variant/30 shadow-sm shrink-0">
                                    @else
                                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/5 text-primary border border-outline-variant/30 shrink-0">
                                            <i class="ti ti-user-star text-xl"></i>
                                        </div>
                                    @endif
                                    <h4 class="text-sm font-bold text-deep-slate leading-tight">{{ $judge->name }}</h4>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="font-display font-extrabold text-primary text-lg">{{ number_format($judgeScores[$judge->id] ?? 0, 0) }}</span>
                                    <span class="text-[9px] font-bold text-on-surface-variant uppercase tracking-wider block">Total Juri</span>
                                </div>
                            </div>

                            {{-- Rincian per sub kategori --}}
                            @if(count($judgeRows) > 0)
                                <div class="space-y-4">
                                    @foreach($judgeRows as $row)
                                        <div class="rounded-xl border border-outline-variant/30 overflow-hidden">
                                            <div class="flex items-center gap-2 bg-surface-container-low px-4 py-2.5 border-b border-outline-variant/20">
                                                <i class="ti ti-subtask text-primary text-sm"></i>
                                                <span class="text-xs font-bold text-deep-slate">{{ $row['sub']->name }}</span>
                                                <span class="ml-auto font-display font-bold text-primary text-sm">{{ number_format($row['subtotal'], 0) }}</span>
                                            </div>
                                            <div class="divide-y divide-outline-variant/20">
                                                @foreach($row['items'] as $item)
                                                    <div class="flex items-center gap-3 px-4 py-2.5">
                                                        <span class="text-xs text-on-surface-variant flex-1 min-w-0">{{ $item['criteria']->name }}</span>
                                                        <span class="text-xs font-semibold text-deep-slate shrink-0">{{ number_format($item['score'], 0) }} × {{ rtrim(rtrim(number_format($item['weight'], 2, '.', ''), '0'), '.') }}</span>
                                                        <span class="font-display font-bold text-deep-slate text-sm w-14 text-right shrink-0">{{ number_format($item['weighted'], 0) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-on-surface-variant italic">Belum ada nilai finalized dari juri ini.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <div class="flex items-center justify-center mb-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/5 text-primary">
                            <i class="ti ti-list-check text-3xl"></i>
                        </div>
                    </div>
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Belum Dinilai</h3>
                    <p class="text-sm text-on-surface-variant max-w-md mx-auto">
                        Belum ada nilai juri yang sudah difinalisasi untuk pasukan ini.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ========== RINGKASAN AKHIR ========== --}}
    @if($judges->count() > 0)
        <div class="container-landing pb-12">
            <div class="surface-card p-6">
                <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                    <i class="ti ti-calculator text-primary text-lg"></i> Ringkasan Nilai
                </h3>

                <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-0 sm:justify-between">
                    <div class="flex items-center justify-between sm:block sm:text-center sm:flex-1">
                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider sm:block sm:mb-1">Total Gabungan Juri</span>
                        <span class="font-display font-extrabold text-deep-slate text-xl sm:text-2xl">{{ number_format($grandTotal, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:block sm:text-center sm:flex-1">
                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider sm:block sm:mb-1">Potongan</span>
                        <span class="font-display font-extrabold {{ $totalDeduction > 0 ? 'text-red-500' : 'text-deep-slate' }} text-xl sm:text-2xl">-{{ number_format($totalDeduction, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between sm:block sm:text-center sm:flex-1 border-t border-outline-variant/30 pt-3 sm:border-t-0 sm:pt-0">
                        <span class="text-[10px] font-bold text-primary uppercase tracking-wider sm:block sm:mb-1">Total Akhir</span>
                        <span class="font-display font-extrabold text-primary text-2xl sm:text-3xl">{{ number_format($finalTotal, 0) }}</span>
                    </div>
                </div>

                @if($deductions->count() > 0)
                    <div class="mt-5 pt-4 border-t border-outline-variant/30">
                        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Detail Potongan</p>
                        <div class="space-y-1.5">
                            @foreach($deductions as $d)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-on-surface-variant">{{ $d->note ?: 'Potongan nilai' }}</span>
                                    <span class="font-semibold text-red-500">-{{ number_format($d->amount, 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
