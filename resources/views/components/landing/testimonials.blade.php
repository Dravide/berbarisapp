@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Apa Kata Mereka?';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<section id="testimonials" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Testimoni</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach($items as $index => $item)
            <div class="surface-card surface-card-hover flex flex-col p-7" wire:key="testi-{{ $index }}">
                <div class="mb-4 flex items-center gap-1 text-secondary">
                    @for($r = 0; $r < ($item['rating'] ?? 5); $r++)
                        <i class="ti ti-star-filled"></i>
                    @endfor
                </div>

                <p class="flex-1 text-base leading-relaxed text-on-surface-variant">
                    <i class="ti ti-quote text-2xl text-primary/30"></i>
                    {{ $item['text'] ?? '' }}
                </p>

                <div class="mt-6 flex items-center gap-3 border-t border-outline-variant/50 pt-5">
                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-primary/10">
                        @if(!empty($item['avatar']))
                            <img src="{{ Storage::url($item['avatar']) }}" alt="{{ $item['name'] ?? '' }}" class="h-full w-full object-cover">
                        @else
                            <span class="font-display text-sm font-bold text-primary">
                                {{ strtoupper(substr($item['name'] ?? '?', 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <div class="font-semibold text-deep-slate">{{ $item['name'] ?? '' }}</div>
                        <div class="text-xs text-on-surface-variant">{{ $item['role'] ?? '' }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
