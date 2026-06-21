@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Jadwal Acara';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<section id="schedule" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Jadwal</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        </div>

        <div class="mx-auto mt-12 max-w-3xl space-y-4">
            @foreach($items as $index => $item)
            <div class="surface-card surface-card-hover flex flex-col gap-4 p-5 sm:flex-row sm:items-center" wire:key="schedule-{{ $index }}">
                <div class="flex flex-shrink-0 flex-col items-center justify-center rounded-xl bg-primary px-5 py-3 text-white sm:w-28">
                    <span class="font-display text-sm font-bold">{{ $item['date'] ?? '' }}</span>
                    <span class="text-xs text-white/80">{{ $item['time'] ?? '' }}</span>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-deep-slate">{{ $item['title'] ?? '' }}</h3>
                    @if(!empty($item['description']))
                    <p class="mt-1 text-sm text-on-surface-variant">{{ $item['description'] }}</p>
                    @endif
                    @if(!empty($item['location']))
                    <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary">
                        <i class="ti ti-map-pin"></i>
                        {{ $item['location'] }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
