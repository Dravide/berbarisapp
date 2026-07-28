@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Fitur Lengkap untuk Event Sukses';
    $items = $data['items'] ?? [];

    // Bento backgrounds — gradient pairs for each card
    $bentoStyles = [
        'bg-gradient-to-br from-violet-500/10 via-purple-400/5 to-indigo-600/10',
        'bg-gradient-to-bl from-emerald-500/10 via-teal-400/5 to-cyan-600/10',
        'bg-gradient-to-tr from-amber-500/10 via-orange-400/5 to-rose-600/10',
        'bg-gradient-to-br from-sky-500/10 via-blue-400/5 to-indigo-600/10',
        'bg-gradient-to-tl from-rose-500/10 via-pink-400/5 to-fuchsia-600/10',
        'bg-gradient-to-br from-lime-500/10 via-green-400/5 to-emerald-600/10',
        'bg-gradient-to-tr from-fuchsia-500/10 via-purple-400/5 to-violet-600/10',
        'bg-gradient-to-bl from-cyan-500/10 via-sky-400/5 to-blue-600/10',
        'bg-gradient-to-br from-orange-500/10 via-amber-400/5 to-yellow-600/10',
    ];

    $iconGradients = [
        'from-violet-500 to-indigo-600',
        'from-emerald-500 to-cyan-600',
        'from-amber-500 to-rose-600',
        'from-sky-500 to-indigo-600',
        'from-rose-500 to-fuchsia-600',
        'from-lime-500 to-emerald-600',
        'from-fuchsia-500 to-violet-600',
        'from-cyan-500 to-blue-600',
        'from-orange-500 to-yellow-600',
    ];

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

        <div class="bento-grid mt-12">
            @foreach($items as $index => $item)
                @php
                    $bgClass = $bentoStyles[$index % count($bentoStyles)];
                    $iconGrad = $iconGradients[$index % count($iconGradients)];
                    $isLarge = $index === 0 || $index === 3;
                @endphp
                <div class="bento-card {{ $isLarge ? 'bento-large' : '' }} {{ $bgClass }} group relative overflow-hidden rounded-3xl border border-white/40 p-8 shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
                    wire:key="feature-{{ $index }}"
                    style="animation: fadeInUp 0.5s ease-out {{ $index * 0.07 }}s both;">
                    {{-- Decorative blur circles --}}
                    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/30 blur-2xl transition-transform duration-500 group-hover:scale-150"></div>
                    <div class="absolute -bottom-4 -left-4 h-16 w-16 rounded-full bg-white/20 blur-xl transition-transform duration-500 group-hover:scale-125"></div>

                    {{-- Icon --}}
                    <div class="relative z-10 mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br {{ $iconGrad }} text-white shadow-lg shadow-current/20">
                        @if(!empty($item['icon_custom']))
                            <img src="{{ Storage::url($item['icon_custom']) }}" alt="{{ $item['title'] ?? '' }}" class="h-6 w-6 object-contain brightness-0 invert">
                        @else
                            <i class="ti {{ $iconMap[$item['icon'] ?? ''] ?? 'ti-star' }} text-xl"></i>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="relative z-10">
                        <h3 class="text-lg font-bold text-deep-slate group-hover:text-primary transition-colors">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] ?? '' }}</p>
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
        gap: 1.5rem;
    }
    .bento-card.bento-large {
        grid-column: span 2;
    }

    @media (max-width: 768px) {
        .bento-grid {
            grid-template-columns: 1fr;
        }
        .bento-card.bento-large {
            grid-column: span 1;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
