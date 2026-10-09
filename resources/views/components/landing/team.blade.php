@php
    $data = json_decode($section?->content ?? 'null', true) ?? [];
    $title = $data['title'] ?? 'Tim Kami';
    $subtitle = $data['subtitle'] ?? 'Orang-orang di balik platform ini.';
    // Hanya anggota bernama yang dihitung — item kosong hasil "Tambah" yang
    // disimpan tanpa diisi tidak boleh membuat section render. Gerbang ini
    // sepakat dengan $sectionsRender['team'] di LandingPage::render().
    $items = collect($data['items'] ?? [])
        ->filter(fn ($item) => ! empty($item['name']))
        ->values();
@endphp

@if($items->isNotEmpty())
<section id="team" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Tim</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
            @foreach($items as $i => $item)
            <div class="surface-card surface-card-hover flex flex-col items-center p-6 text-center" wire:key="team-{{ $i }}">
                @if(!empty($item['photo']))
                    <img src="{{ Storage::url($item['photo']) }}" alt="{{ $item['name'] ?? '' }}"
                        class="h-20 w-20 rounded-full object-cover ring-2 ring-primary/20" loading="lazy">
                @else
                    <span class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/10 font-display text-2xl font-extrabold text-primary">
                        {{ strtoupper(substr($item['name'] ?? '?', 0, 1)) }}
                    </span>
                @endif
                <h3 class="mt-4 text-sm font-bold text-deep-slate">{{ $item['name'] ?? '' }}</h3>
                <p class="mt-1 text-xs text-on-surface-variant">{{ $item['role'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
