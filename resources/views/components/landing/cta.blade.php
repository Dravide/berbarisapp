@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Siap Mengelola Event Lebih Efisien?';
    $description = $data['description'] ?? 'Mulai gunakan BARIS APP sekarang dan rasakan kemudahan mengelola event dan kompetisi secara digital. Daftar gratis dan langsung gunakan.';
    $buttonText = $data['button_text'] ?? 'Daftar Sekarang';
    $buttonUrl = $data['button_url'] ?? route('login');
    $image = $data['image'] ?? '';
@endphp

<section class="section-pad bg-surface">
    <div class="container-landing">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0c1020] via-primary to-[#00174b] px-6 py-16 text-center shadow-[0_20px_60px_rgba(0,98,255,0.30)] md:px-12 md:py-20">
            {{-- Decorative blobs --}}
            <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-tertiary/20 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-secondary/20 blur-3xl"></div>

            <div class="relative mx-auto max-w-2xl">
                @if($image)
                <div class="mx-auto mb-8 overflow-hidden rounded-2xl border border-white/20">
                    <img src="{{ Storage::url($image) }}" alt="CTA" class="max-h-64 w-full object-cover">
                </div>
                @endif

                <h2 class="font-display text-3xl font-extrabold leading-tight text-white md:text-4xl">{{ $heading }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-white/80">{{ $description }}</p>

                <div class="mt-8 flex justify-center">
                    <a href="{{ $buttonUrl }}" class="btn-primary">
                        {{ $buttonText }}
                        <i class="ti ti-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
