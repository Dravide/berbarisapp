@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Galeri';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<section id="gallery" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Galeri</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($items as $index => $item)
            @if(!empty($item['image']))
            <figure class="surface-card surface-card-hover group relative overflow-hidden" wire:key="gallery-{{ $index }}">
                <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['caption'] ?? '' }}" class="h-64 w-full object-cover transition duration-500 group-hover:scale-105">
                @if(!empty($item['caption']))
                <figcaption class="absolute inset-0 flex items-end bg-gradient-to-t from-deep-slate/80 via-deep-slate/10 to-transparent p-5 opacity-0 transition group-hover:opacity-100">
                    <p class="text-sm font-medium text-white">{{ $item['caption'] }}</p>
                </figcaption>
                @endif
            </figure>
            @endif
            @endforeach
        </div>
    </div>
</section>
@endif
