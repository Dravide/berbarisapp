@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Siap Mengelola Event Lebih Efisien?';
    $description = $data['description'] ?? ('Mulai gunakan ' . app_name() . ' sekarang dan rasakan kemudahan mengelola event dan kompetisi secara digital. Daftar gratis dan langsung gunakan.');
    $buttonText = $data['button_text'] ?? 'Daftar Sekarang';
    $buttonUrl = $data['button_url'] ?? route('login');
    $image = $data['image'] ?? '';
@endphp

<section class="section-pad bg-surface">
    <div class="container-landing">
        {{-- Panel gelap solid: satu-satunya blok gelap di halaman, jadi penutup
             yang menonjol tanpa gradien dan tanpa glow (R-01/R-13). --}}
        <div class="rounded-3xl bg-deep-slate px-6 py-16 text-center md:px-12 md:py-20">
            <div class="mx-auto max-w-2xl">
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
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
