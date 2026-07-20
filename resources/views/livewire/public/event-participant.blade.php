<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-16">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-users-group"></i>
                Daftar Peserta Kompetisi
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                Daftar Kontingen Resmi
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Menampilkan daftar seluruh sekolah dan kontingen yang terdaftar dalam event <strong class="text-secondary font-semibold">{{ $eventner->nama_event }}</strong>.
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
        $totalKontingen = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
        $totalAnggota = $eventner->competitionCategories->sum(fn($c) => $c->registrations->sum(fn($r) => $r->participants->count()));
        $totalVerified = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'Terverifikasi')->count());
    @endphp
    <div class="container-landing -mt-8 relative z-20">
        <div class="surface-card p-6">
            <div class="grid gap-6 grid-cols-2 md:grid-cols-4">
                <div class="text-center">
                    <span class="text-3xl font-extrabold text-primary font-display block mb-1">
                        {{ $eventner->competitionCategories->count() }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Kategori Lomba</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-emerald-600 font-display block mb-1">
                        {{ $totalKontingen }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Kontingen</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-primary font-display block mb-1">
                        {{ $totalVerified }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Terverifikasi</span>
                </div>
                <div class="text-center border-l border-outline-variant/30">
                    <span class="text-3xl font-extrabold text-amber-500 font-display block mb-1">
                        {{ $totalAnggota }}
                    </span>
                    <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Anggota</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ========== LIST PESERTA PER KATEGORI ========== --}}
    <div class="container-landing py-8">
        <div class="grid gap-8 lg:grid-cols-12">
            <div class="lg:col-span-8 flex flex-col gap-6">
                @foreach($eventner->competitionCategories as $cat)
                    <div class="surface-card overflow-hidden">
                        {{-- Category Header --}}
                        <div class="flex items-center justify-between bg-surface-container px-6 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-medal text-primary text-lg"></i>
                                {{ $cat->full_name }}
                            </h3>
                            <span class="chip py-0.5 px-2.5 text-xs font-bold leading-normal bg-primary/10">{{ $cat->registrations->count() }} kontingen</span>
                        </div>

                        {{-- Category Body - Registrations --}}
                        <div class="divide-y divide-outline-variant/30">
                            @forelse($cat->registrations as $reg)
                                <div class="flex items-center gap-4 px-6 py-4 hover:bg-surface-container-lowest transition duration-150">
                                    @if($reg->logo_sekolah)
                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" alt="" class="h-11 w-11 rounded-xl object-cover border border-outline-variant/30 shadow-sm shrink-0">
                                    @else
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/5 text-primary border border-outline-variant/30 shrink-0">
                                            <i class="ti ti-school text-xl"></i>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-deep-slate leading-tight mb-1 inline-flex items-center gap-2 flex-wrap">
                                            {{ $reg->nama_sekolah }}
                                            @if($reg->urutan_tampil)
                                                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 border border-amber-500/20">
                                                    #{{ str_pad($reg->urutan_tampil, 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                            @endif
                                        </h4>
                                        <span class="text-xs text-on-surface-variant block">Pelatih: {{ $reg->nama_pelatih ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="chip py-1 px-3 !text-[11px]">
                                            {{ $reg->participants->count() }} org
                                        </span>

                                        {{-- Verification Status --}}
                                        @if($reg->status_berkas === 'Terverifikasi')
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-emerald-500/10 text-emerald-600 border border-emerald-500/20" title="Terverifikasi">
                                                <i class="ti ti-check text-sm font-bold"></i>
                                            </span>
                                        @elseif($reg->status_berkas === 'Ditolak')
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-500/10 text-red-600 border border-red-500/20" title="Ditolak">
                                                <i class="ti ti-x text-sm font-bold"></i>
                                            </span>
                                        @elseif($reg->status_berkas === 'booking')
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-slate-500/10 text-slate-600 border border-slate-500/20" title="Booking Slot">
                                                <i class="ti ti-bookmark text-sm font-bold"></i>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-amber-500/10 text-amber-600 border border-amber-500/20" title="Menunggu Verifikasi">
                                                <i class="ti ti-hourglass text-sm font-bold animate-pulse"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center text-sm font-medium text-on-surface-variant bg-surface-container-lowest">
                                    Belum ada kontingen di kategori ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sidebar Column --}}
            <div class="lg:col-span-4 flex flex-col gap-6">
                {{-- Info Status Registrasi --}}
                <div class="surface-card p-6">
                    <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2 mb-4">
                        <i class="ti ti-help-circle text-primary"></i>
                        Status Berkas
                    </h3>
                    <div class="flex flex-col gap-3 text-xs font-semibold text-on-surface-variant">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 shrink-0">
                                <i class="ti ti-check text-sm font-bold"></i>
                            </span>
                            <div>
                                <span class="text-deep-slate block">Terverifikasi</span>
                                <span class="text-[10px] text-on-surface-variant/75 font-normal block leading-tight">Berkas lengkap &amp; siap bertanding</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-t border-outline-variant/30 pt-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/10 text-amber-600 border border-amber-500/20 shrink-0">
                                <i class="ti ti-hourglass text-sm font-bold"></i>
                            </span>
                            <div>
                                <span class="text-deep-slate block">Menunggu Verifikasi</span>
                                <span class="text-[10px] text-on-surface-variant/75 font-normal block leading-tight">Berkas pendaftaran sedang diperiksa panitia</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-t border-outline-variant/30 pt-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-500/10 text-slate-600 border border-slate-500/20 shrink-0">
                                <i class="ti ti-bookmark text-sm font-bold"></i>
                            </span>
                            <div>
                                <span class="text-deep-slate block">Booking Slot</span>
                                <span class="text-[10px] text-on-surface-variant/75 font-normal block leading-tight">Mengunci kuota, berkas belum dikirim/lengkap</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-t border-outline-variant/30 pt-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-red-500/10 text-red-600 border border-red-500/20 shrink-0">
                                <i class="ti ti-x text-sm font-bold"></i>
                            </span>
                            <div>
                                <span class="text-deep-slate block">Ditolak</span>
                                <span class="text-[10px] text-on-surface-variant/75 font-normal block leading-tight">Berkas bermasalah, silakan hubungi admin event</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
