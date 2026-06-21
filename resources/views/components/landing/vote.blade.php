@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Voting Online';
    $subtitle = $data['subtitle'] ?? 'Dukung peserta favoritmu lewat voting online. Setiap suara menentukan juara favorit.';
    $events = $events ?? collect();
@endphp

@if($events->count() > 0)
<section id="vote" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center"><i class="ti ti-thumb-up"></i> E-Voting</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($events as $event)
            @php
                $tgl = $event->tanggal ? \Illuminate\Support\Carbon::parse($event->tanggal)->translatedFormat('d M Y') : null;
            @endphp
            <div class="surface-card surface-card-hover group flex flex-col overflow-hidden p-0">
                <a href="{{ route('event.detail', $event->slug) }}" class="relative block aspect-[4/3] overflow-hidden">
                    @if($event->poster)
                        <img src="{{ Storage::url($event->poster) }}" alt="{{ $event->nama_event }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-secondary/20 via-surface-container-lowest to-tertiary/15">
                            <span class="font-display text-4xl font-extrabold text-primary/50">
                                {{ strtoupper(substr($event->nama_event ?? '?', 0, 1)) }}
                            </span>
                        </div>
                    @endif
                    <span class="badge-live absolute bottom-3 left-3">Vote Dibuka</span>
                </a>

                <div class="flex flex-1 flex-col p-5">
                    <h3 class="text-base font-bold leading-snug text-deep-slate transition-colors duration-200 group-hover:text-primary">{{ $event->nama_event }}</h3>
                    <p class="mt-1 text-xs text-on-surface-variant">{{ $event->diselenggarakan_oleh }}</p>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-outline-variant/50 pt-3 text-xs text-on-surface-variant">
                        @if($event->lokasi)
                        <span class="inline-flex items-center gap-1"><i class="ti ti-map-pin text-primary"></i>{{ \Illuminate\Support\Str::limit($event->lokasi, 15) }}</span>
                        @endif
                        @if($tgl)
                        <span class="inline-flex items-center gap-1"><i class="ti ti-calendar-event text-[#5a7d00]"></i>{{ $tgl }}</span>
                        @endif
                    </div>

                    <a href="{{ route('event.vote', $event->slug) }}" class="btn-secondary mt-4 w-full">
                        <i class="ti ti-thumb-up"></i>
                        Vote Sekarang
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
