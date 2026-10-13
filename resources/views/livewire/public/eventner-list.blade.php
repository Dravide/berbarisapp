<div>
    @if($eventners->count())
        @include('components.seo.json-ld-itemlist', ['eventners' => $eventners])
    @endif

    {{-- Header --}}
    <div class="section-pad bg-surface">
        <div class="container-landing">
            <div class="text-center">
                <span class="overline justify-center">Penyelenggara</span>
                <h1 class="mt-4 text-3xl font-bold leading-tight md:text-4xl font-display text-deep-slate">Penyelenggara Event yang Telah Bergabung</h1>
                <p class="mt-4 max-w-2xl mx-auto text-on-surface-variant">Mereka telah mempercayakan pengelolaan event dan kompetisi mereka melalui platform kami.</p>
            </div>
        </div>
    </div>

    {{-- Grid penyelenggara --}}
    <div class="pb-20">
        <div class="container-landing">
            {{-- Filter Tabs --}}
            <div class="mb-8 flex items-center justify-center gap-2">
                <button type="button"
                        wire:click="setFilter('all')"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold transition-colors {{ $filter === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-surface-container-high text-on-surface-variant hover:text-deep-slate' }}">
                    <span>Semua Penyelenggara</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $filter === 'all' ? 'bg-white/20 text-white' : 'bg-black/5 text-on-surface-variant' }}">{{ $counts['all'] ?? 0 }}</span>
                </button>
                <button type="button"
                        wire:click="setFilter('verified')"
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold transition-colors {{ $filter === 'verified' ? 'bg-primary text-white shadow-sm' : 'bg-surface-container-high text-on-surface-variant hover:text-deep-slate' }}">
                    <x-verified-badge size="xs" class="{{ $filter === 'verified' ? '!text-white' : 'text-primary' }}" />
                    <span>Terverifikasi</span>
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $filter === 'verified' ? 'bg-white/20 text-white' : 'bg-black/5 text-on-surface-variant' }}">{{ $counts['verified'] ?? 0 }}</span>
                </button>
            </div>

            @if($eventners->count() > 0)
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($eventners as $eventner)
                    @include('components.landing.eventner-card', ['eventner' => $eventner])
                @endforeach
            </div>

            @if($eventners->hasPages())
            <div class="mt-12">
                {{ $eventners->links() }}
            </div>
            @endif
            @else
            <div class="mx-auto max-w-md text-center">
                <p class="text-on-surface-variant">Belum ada penyelenggara yang terdaftar.</p>
            </div>
            @endif
        </div>
    </div>
</div>
