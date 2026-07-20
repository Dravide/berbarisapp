@php
    $eventners = $eventners ?? collect();
@endphp

@if($eventners->count() > 0)
<section id="eventners" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Penyelenggara</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">Penyelenggara Event yang Telah Bergabung</h2>
            <p class="mt-4 text-on-surface-variant">Mereka telah mempercayakan pengelolaan event dan kompetisi mereka melalui platform kami.</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($eventners as $eventner)
            <div class="surface-card surface-card-hover group flex flex-col overflow-hidden p-0">
                {{-- Poster banner --}}
                <a href="{{ event_url($eventner, 'detail') }}" class="relative block aspect-[4/3] overflow-hidden">
                    @if($eventner->poster)
                        <img src="{{ Storage::url($eventner->poster) }}" alt="{{ $eventner->nama_event }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/15 via-surface-container-lowest to-tertiary/15">
                            <span class="font-display text-4xl font-extrabold text-primary/50">
                                {{ strtoupper(substr($eventner->nama_event ?? '?', 0, 1)) }}
                            </span>
                        </div>
                    @endif
                    @if($eventner->tingkat_perlombaan)
                    <span class="chip absolute left-3 top-3 backdrop-blur">{{ $eventner->tingkat_perlombaan }}</span>
                    @endif
                </a>

                {{-- Body --}}
                <div class="flex flex-1 flex-col p-5">
                    <h3 class="text-base font-bold leading-snug text-deep-slate transition-colors duration-200 group-hover:text-primary">{{ $eventner->nama_event }}</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">{{ $eventner->diselenggarakan_oleh }}</p>

                    <div class="mt-4 flex items-center gap-4 border-t border-outline-variant/50 pt-3 text-xs text-on-surface-variant">
                        @if($eventner->lokasi)
                        <span class="inline-flex items-center gap-1">
                            <i class="ti ti-map-pin text-primary"></i>
                            {{ \Illuminate\Support\Str::limit($eventner->lokasi, 15) }}
                        </span>
                        @endif
                        <span class="inline-flex items-center gap-1">
                            <i class="ti ti-users text-[#5a7d00]"></i>
                            {{ $eventner->registrations_count ?? $eventner->registrations->count() }} peserta
                        </span>
                    </div>

                    <a href="{{ event_url($eventner, 'detail') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary transition group-hover:gap-2">
                        Lihat Event
                        <i class="ti ti-arrow-right"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        @if($eventners->count() > 8)
        <div class="mt-10 text-center">
            <a href="#" class="btn-ghost">Lihat Semua Event</a>
        </div>
        @endif
    </div>
</section>
@endif
