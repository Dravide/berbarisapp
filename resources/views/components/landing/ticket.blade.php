@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'E-Tiket Digital';
    $subtitle = $data['subtitle'] ?? 'Beli tiket event favoritmu secara online. Praktis, aman, dengan QR code check-in.';
    $events = $events ?? collect();
@endphp

@if($events->count() > 0)
<section id="ticket" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center"><i class="ti ti-ticket"></i> E-Tiket</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($events as $event)
            @php
                $tgl = $event->tanggal
                    ? ($event->tanggal_akhir
                        ? \Illuminate\Support\Carbon::parse($event->tanggal)->translatedFormat('d M') . ' - ' . \Illuminate\Support\Carbon::parse($event->tanggal_akhir)->translatedFormat('d M Y')
                        : \Illuminate\Support\Carbon::parse($event->tanggal)->translatedFormat('d M Y'))
                    : null;
            @endphp
            <div class="surface-card surface-card-hover group flex flex-col overflow-hidden p-0">
                <a href="{{ event_url($event, 'detail') }}" class="relative block aspect-[4/3] overflow-hidden">
                    @if($event->poster)
                        <img src="{{ Storage::url($event->poster) }}" alt="{{ $event->nama_event }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary/15 via-surface-container-lowest to-tertiary/15">
                            <span class="font-display text-4xl font-extrabold text-primary/50">
                                {{ strtoupper(substr($event->nama_event ?? '?', 0, 1)) }}
                            </span>
                        </div>
                    @endif
                    @if($event->ticket_price)
                    <span class="absolute bottom-3 left-3 rounded-full bg-deep-slate/85 px-3 py-1 text-xs font-bold text-secondary backdrop-blur">
                        Rp {{ number_format($event->ticket_price, 0, ',', '.') }}
                    </span>
                    @else
                    <span class="chip absolute bottom-3 left-3 backdrop-blur">GRATIS</span>
                    @endif
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

                    <a href="{{ event_url($event, 'ticket') }}" class="btn-primary mt-4 w-full">
                        <i class="ti ti-ticket"></i>
                        Beli Tiket
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
