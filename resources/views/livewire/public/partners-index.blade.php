<div class="section-pad">
    <div class="container-landing">
        {{-- Header --}}
        <div class="text-center mb-12">
            <span class="overline">Mitra</span>
            <h1 class="mt-4 text-3xl font-bold leading-tight md:text-4xl font-display text-deep-slate">Sekolah yang Telah Bergabung</h1>
            <p class="mt-4 max-w-2xl mx-auto text-on-surface-variant">Berbagai sekolah dari seluruh Indonesia telah mempercayakan event dan kompetisi mereka melalui platform BARIS APP.</p>
        </div>

        {{-- Search --}}
        <div class="mb-8 max-w-md mx-auto">
            <div class="relative">
                <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60"></i>
                <input type="text" wire:model.live="search" placeholder="Cari nama sekolah..." class="w-full rounded-xl border border-outline-variant/60 bg-surface-container-lowest pl-10 pr-4 py-3 text-sm text-deep-slate placeholder:text-on-surface-variant/50 focus:border-primary/50 focus:ring-2 focus:ring-primary/10 transition outline-none">
            </div>
        </div>

        {{-- School Cards Grid --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($schools as $school)
            <a href="{{ route('admin.schools.show', $school->npsn) }}" class="surface-card surface-card-hover group flex flex-col overflow-hidden p-0 text-decoration-none">
                {{-- Logo + Name header --}}
                <div class="flex items-center gap-4 p-5">
                    @if($school->logo_sekolah)
                        <img src="{{ Storage::url($school->logo_sekolah) }}" alt="{{ $school->display_name }}" class="w-14 h-14 rounded-xl object-cover flex-shrink-0 border border-outline-variant/30">
                    @else
                        <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i class="ti ti-school text-xl text-primary/60"></i>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold leading-snug text-deep-slate transition-colors group-hover:text-primary line-clamp-2">{{ $school->display_name }}</h3>
                        <span class="text-xs text-on-surface-variant/60 mt-0.5">NPSN {{ $school->npsn }}</span>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="flex items-center gap-4 border-t border-outline-variant/30 px-5 py-3 text-xs text-on-surface-variant">
                    <span class="inline-flex items-center gap-1">
                        <i class="ti ti-file-text text-primary"></i>
                        {{ $school->total_registrations }} pendaftaran
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <i class="ti ti-users text-[#5a7d00]"></i>
                        {{ $school->total_participants }} peserta
                    </span>
                </div>

                {{-- Event Chips --}}
                @if($school->events->isNotEmpty())
                <div class="px-5 pb-4">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($school->events->take(3) as $event)
                            <span class="chip text-xs">{{ $event }}</span>
                        @endforeach
                        @if($school->events->count() > 3)
                            <span class="chip text-xs text-on-surface-variant/60">+{{ $school->events->count() - 3 }} lainnya</span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- CTA --}}
                <div class="mt-auto px-5 pb-5 pt-2">
                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-primary transition group-hover:gap-2">
                        Lihat Detail
                        <i class="ti ti-arrow-right"></i>
                    </span>
                </div>
            </a>
            @empty
            <div class="col-span-full text-center py-16">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-primary/10 flex items-center justify-center">
                    <i class="ti ti-school text-2xl text-primary/40"></i>
                </div>
                <h3 class="text-lg font-semibold text-deep-slate">Belum ada sekolah terdaftar</h3>
                <p class="mt-1 text-on-surface-variant">Sekolah akan muncul di sini setelah mendaftar di salah satu event.</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($schools->hasPages())
        <div class="mt-12">
            {{ $schools->links() }}
        </div>
        @endif
    </div>
</div>
