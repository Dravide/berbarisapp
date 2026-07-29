@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Sekolah yang Telah Bergabung';
    $description = $data['description'] ?? 'Berbagai sekolah dari seluruh Indonesia telah mempercayakan event mereka melalui platform kami.';
@endphp

@if(count($logos ?? []) > 0)
<section id="partners" class="section-pad">
    <div class="container-landing">
        <div class="text-center mb-12">
            <span class="overline">Partners</span>
            <h2 class="mt-4 text-3xl font-bold leading-tight md:text-4xl font-display text-deep-slate">{{ $heading }}</h2>
            <p class="mt-4 max-w-2xl mx-auto text-on-surface-variant">{{ $description }}</p>
        </div>

        <div class="flex flex-wrap justify-center items-center gap-8">
            @foreach($logos as $logo)
                <div class="w-16 h-16 flex items-center justify-center">
                    <img src="{{ Storage::url($logo) }}" alt="Logo Sekolah" class="max-w-full max-h-full object-contain grayscale hover:grayscale-0 opacity-60 hover:opacity-100 transition-all duration-300">
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
