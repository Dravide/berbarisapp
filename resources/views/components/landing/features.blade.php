@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Fitur Lengkap untuk Event Sukses';
    $items = $data['items'] ?? [];

    // Each card a distinct soft bg + icon color pair
    $cardStyles = [
        ['bg' => 'bg-violet-50',    'iconBg' => 'bg-violet-500',    'ring' => 'ring-violet-200/60'],
        ['bg' => 'bg-emerald-50',   'iconBg' => 'bg-emerald-500',   'ring' => 'ring-emerald-200/60'],
        ['bg' => 'bg-amber-50',     'iconBg' => 'bg-amber-500',     'ring' => 'ring-amber-200/60'],
        ['bg' => 'bg-sky-50',       'iconBg' => 'bg-sky-500',       'ring' => 'ring-sky-200/60'],
        ['bg' => 'bg-rose-50',      'iconBg' => 'bg-rose-500',      'ring' => 'ring-rose-200/60'],
        ['bg' => 'bg-lime-50',      'iconBg' => 'bg-lime-600',      'ring' => 'ring-lime-200/60'],
        ['bg' => 'bg-fuchsia-50',   'iconBg' => 'bg-fuchsia-500',   'ring' => 'ring-fuchsia-200/60'],
        ['bg' => 'bg-cyan-50',      'iconBg' => 'bg-cyan-500',      'ring' => 'ring-cyan-200/60'],
        ['bg' => 'bg-orange-50',    'iconBg' => 'bg-orange-500',    'ring' => 'ring-orange-200/60'],
    ];

    // Bento layout: indices of large (2-col span) cards
    $largeIndices = [0, 3, 5, 7];

    $iconMap = [
        'icon3.png' => 'ti-chart-bar',
        'icon4.png' => 'ti-shield-lock',
        'icon5.png' => 'ti-clipboard-list',
        'icon6.png' => 'ti-device-mobile',
        'icon7.png' => 'ti-cash',
        'icon8.png' => 'ti-plug-connected',
        'icon9.png' => 'ti-certificate',
        'icon10.png' => 'ti-video',
        'icon11.png' => 'ti-affiliate',
    ];
@endphp

<section id="features" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Fitur</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">Semua yang Anda butuhkan untuk menyelenggarakan event &amp; kompetisi dalam satu platform terpadu.</p>
        </div>

        <div class="mt-12 bento-grid">
            @foreach($items as $index => $item)
                @php
                    $style = $cardStyles[$index % count($cardStyles)];
                    $isLarge = in_array($index, $largeIndices);
                @endphp
                <div class="bento-item {{ $isLarge ? 'bento-item-large' : '' }}"
                    wire:key="feature-{{ $index }}"
                    style="animation: fadeInUp 0.45s ease-out {{ $index * 0.06 }}s both;">
                    <div class="feature-card {{ $style['bg'] }} group h-full rounded-3xl p-7 border border-transparent hover:ring-2 {{ $style['ring'] }} transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
                        {{-- Icon circle --}}
                        <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-full {{ $style['iconBg'] }} text-white shadow-md shadow-current/25 transition-transform duration-300 group-hover:scale-110">
                            @if(!empty($item['icon_custom']))
                                <img src="{{ Storage::url($item['icon_custom']) }}" alt="{{ $item['title'] ?? '' }}" class="h-5 w-5 object-contain brightness-0 invert">
                            @else
                                <i class="ti {{ $iconMap[$item['icon'] ?? ''] ?? 'ti-star' }} text-lg"></i>
                            @endif
                        </div>
                        {{-- Content --}}
                        <h3 class="text-base font-bold text-deep-slate group-hover:text-primary transition-colors">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
    .bento-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
    .bento-item-large {
        grid-column: span 2;
    }

    @media (max-width: 1024px) {
        .bento-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .bento-item-large {
            grid-column: span 1;
        }
    }

    @media (max-width: 640px) {
        .bento-grid {
            grid-template-columns: 1fr;
        }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
