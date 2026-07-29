@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Sekolah yang Telah Bergabung';
    $description = $data['description'] ?? 'Berbagai sekolah dari seluruh Indonesia telah mempercayakan event mereka melalui platform kami.';
@endphp

@if(count($logos ?? []) > 0)
<section id="partners" class="section-pad overflow-hidden">
    <div class="container-landing">
        <div class="text-center mb-12">
            <span class="overline">Partners</span>
            <h2 class="mt-4 text-3xl font-bold leading-tight md:text-4xl font-display text-deep-slate">{{ $heading }}</h2>
            <p class="mt-4 max-w-2xl mx-auto text-on-surface-variant">{{ $description }}</p>
        </div>
    </div>

    <div class="relative">
        <div class="marquee-track flex items-center gap-10">
            {{-- Clone logos twice for seamless infinite scroll --}}
            @foreach([1, 2] as $loop)
                @foreach($logos as $logo)
                <div class="flex-shrink-0 w-[120px] h-[80px] sm:w-[140px] sm:h-[90px] bg-surface-container rounded-xl border border-outline-variant/40 flex items-center justify-center p-3 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <img src="{{ Storage::url($logo) }}" alt="Logo Sekolah" class="max-w-full max-h-full object-contain opacity-70 hover:opacity-100 transition-opacity duration-300 grayscale hover:grayscale-0">
                </div>
                @endforeach
            @endforeach
        </div>

        {{-- Fade edges --}}
        <div class="pointer-events-none absolute inset-y-0 left-0 w-20 bg-gradient-to-r from-surface-container-lowest to-transparent"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 w-20 bg-gradient-to-r from-transparent to-surface-container-lowest"></div>
    </div>
</section>

@push('styles')
<style>
.marquee-track {
    animation: marquee-scroll 40s linear infinite;
}
.marquee-track:hover {
    animation-play-state: paused;
}
@keyframes marquee-scroll {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
</style>
@endpush
@endif
