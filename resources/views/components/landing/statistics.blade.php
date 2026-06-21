@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $items = $data['items'] ?? [
        ['value' => '500', 'suffix' => '+', 'label' => 'Event Diselenggarakan'],
        ['value' => '10K', 'suffix' => '+', 'label' => 'Peserta Terdaftar'],
        ['value' => '50', 'suffix' => '+', 'label' => 'Kota di Indonesia'],
        ['value' => '99', 'suffix' => '%', 'label' => 'Kepuasan Pengguna'],
    ];
@endphp

@if(count($items) > 0)
<section id="statistics" class="bg-deep-slate py-16 md:py-20">
    <div class="container-landing">
        <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
            @foreach($items as $index => $item)
            <div class="text-center">
                <div class="font-display text-4xl font-extrabold tracking-tight text-secondary md:text-5xl">
                    {{ $item['value'] ?? 0 }}{{ $item['suffix'] ?? '' }}
                </div>
                <div class="mt-2 text-xs uppercase tracking-[0.15em] text-white/60 sm:text-sm">
                    {{ $item['label'] ?? '' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
