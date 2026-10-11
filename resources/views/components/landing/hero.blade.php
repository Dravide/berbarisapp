@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Kelola Event & Kompetisi dengan Mudah';
    $subheading = $data['subheading'] ?? 'Platform manajemen event terpadu yang membantu penyelenggara mengelola pendaftaran, penilaian, voting, dan tiket secara digital.';
    $ctaText = $data['cta_text'] ?? 'Mulai Sekarang';
    $ctaUrl = $data['cta_url'] ?? route('login');
    $videoUrl = $data['video_url'] ?? '';
    $bgImage = $data['background_image'] ?? '';

    // Visual produk: gambar/video yang dulu dipasang di section "Tentang".
    // Section itu dibuang (isinya mengulang #features), tapi visualnya sayang
    // dibuang — sekarang jadi latar di bawah hero. Kuncinya masih
    // `landing_about` supaya gambar yang sudah diunggah admin tidak perlu
    // dipindah; namanya warisan, bukan salah tulis.
    $about = json_decode(\App\Models\Setting::get('landing_about') ?? 'null', true) ?? [];
    $vizImage = $about['image'] ?? '';
    $vizVideo = $about['video'] ?? '';
@endphp

<section id="hero" class="relative overflow-hidden">
    {{-- Background layer --}}
    @if($bgImage)
        <div class="absolute inset-0 -z-10 bg-cover bg-center" style="background-image: url('{{ Storage::url($bgImage) }}')"></div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-deep-slate/70 via-deep-slate/60 to-surface"></div>
    @else
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-surface via-surface-container-low to-surface-container"></div>
    @endif

    <div class="container-landing section-pad">
        <div class="mx-auto max-w-3xl text-center">
            {{-- Tanpa eyebrow di atas judul: labelnya sama dengan isi H1,
                 jadi hanya menambah satu baris bacaan (R-09). --}}
            <h1 class="font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-deep-slate sm:text-5xl md:text-6xl @if($bgImage) !text-white @endif">
                {{ $heading }}
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed @if($bgImage) text-white/85 @else text-on-surface-variant @endif">
                {{ $subheading }}
            </p>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ $ctaUrl }}" class="btn-primary w-full sm:w-auto">
                    {{ $ctaText }}
                </a>
                @if($videoUrl)
                <a href="{{ $videoUrl }}" class="btn-ghost w-full sm:w-auto @if($bgImage) !border-white/40 !text-white hover:!bg-white/10 @endif">
                    <i class="ti ti-player-play-filled"></i>
                    Tonton Demo
                </a>
                @endif
            </div>
        </div>

        {{-- Visual produk. Dulu empat kartu angka di sini ("500+ Event",
             "10K+ Peserta", "50+ Kota") — angka karangan yang tidak bisa
             diedit admin dan bertabrakan dengan section statistik di bawahnya.
             Diganti tangkapan layar asli: jujur, dan tidak perlu dirawat.
             Gambar menang atas video bawaan supaya video seeder tidak
             menutupi gambar yang sengaja dipasang admin. --}}
        @if($vizImage || $vizVideo)
        <div class="mx-auto mt-16 max-w-5xl">
            <div class="overflow-hidden rounded-2xl border border-outline-variant/60 shadow-[0_4px_16px_rgba(17,24,39,0.10)]">
                @if($vizImage)
                    <img src="{{ Storage::url($vizImage) }}" alt="{{ app_name() }}" class="h-full w-full object-cover">
                @else
                    <video autoplay muted loop playsinline class="aspect-[16/9] w-full object-cover">
                        <source src="{{ $vizVideo }}" type="video/mp4">
                    </video>
                @endif
            </div>
        </div>
        @endif
    </div>
</section>
