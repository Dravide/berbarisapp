@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $image = $data['image'] ?? '';
    $heading = $data['heading'] ?? 'Platform Event & Kompetisi Terpadu';
    $description = $data['description'] ?? 'BARIS APP menyediakan solusi lengkap untuk menyelenggarakan event dan kompetisi. Dari pendaftaran peserta hingga pengumuman pemenang, semuanya terintegrasi dalam satu platform.';
    $points = $data['points'] ?? [
        ['title' => 'Pendaftaran Digital', 'text' => 'Peserta mendaftar secara online dengan verifikasi otomatis dan tracking status real-time.'],
        ['title' => 'Penilaian Terintegrasi', 'text' => 'Juri memberikan nilai secara digital dengan format penilaian yang bisa dikustomisasi.'],
    ];
@endphp

<section id="about" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            {{-- Visual --}}
            <div class="relative">
                <div class="overflow-hidden rounded-2xl border border-outline-variant/60 shadow-[0_8px_30px_rgba(0,98,255,0.06)]">
                    @if($image)
                        <img src="{{ Storage::url($image) }}" alt="Tentang BARIS APP" class="h-full w-full object-cover">
                    @else
                        <div class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-primary/10 via-surface-container-lowest to-tertiary/10">
                            <i class="ti ti-device-desktop-analytics text-7xl text-primary/40"></i>
                        </div>
                    @endif
                </div>
                {{-- Floating stat card --}}
                <div class="surface-card absolute -bottom-6 -right-4 hidden items-center gap-3 p-4 sm:flex">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-secondary text-deep-slate">
                        <i class="ti ti-rosette-discount-check text-xl"></i>
                    </div>
                    <div>
                        <div class="font-display text-lg font-bold text-deep-slate">99%</div>
                        <div class="text-xs text-on-surface-variant">Kepuasan Pengguna</div>
                    </div>
                </div>
            </div>

            {{-- Copy --}}
            <div>
                <span class="overline">Tentang Kami</span>
                <h2 class="mt-4 text-3xl font-bold leading-tight md:text-4xl">{{ $heading }}</h2>
                <p class="mt-4 text-on-surface-variant">{{ $description }}</p>

                <div class="mt-7 space-y-4">
                    @foreach($points as $point)
                    <div class="flex gap-3">
                        <div class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-secondary text-deep-slate">
                            <i class="ti ti-check text-sm font-bold"></i>
                        </div>
                        <div>
                            <span class="font-semibold text-deep-slate">{{ $point['title'] ?? '' }}</span>
                            <p class="text-sm text-on-surface-variant">{{ $point['text'] ?? '' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
