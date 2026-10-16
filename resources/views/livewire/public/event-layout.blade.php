<div class="min-h-screen bg-surface">

    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-16">
        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                Denah Lapangan
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white md:text-sm max-w-xl">
                Arena, pos penilaian, dan daerah persiapan (DP) tiap tingkat lomba di
                <strong class="font-semibold text-white">{{ $eventner->nama_event }}</strong>.
            </p>
            <div class="mt-4">
                <a href="{{ event_url($eventner, 'detail') }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Kembali Ke Detail Event
                </a>
            </div>
        </div>
    </div>

    <div class="container-landing py-8 space-y-6">

        {{-- Pemilih denah: hanya muncul kalau event ini punya lebih dari satu
             denah terbit (mis. per tingkat lomba). Satu denah langsung
             ditampilkan; daftar berisi satu baris cuma menambah satu klik. --}}
        @if($denahTerbit->count() > 1)
            <div class="surface-card p-4">
                <div class="d-flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn-ghost text-xs py-2 px-3 {{ $denah === null ? 'bg-primary/10 text-primary' : '' }}"
                        wire:click="tampilkanSemua"
                    >
                        Semua Denah
                    </button>
                    @foreach($denahTerbit as $d)
                        <button
                            type="button"
                            wire:key="pilih-{{ $d->id }}"
                            class="btn-ghost text-xs py-2 px-3 {{ $denah?->id === $d->id ? 'bg-primary/10 text-primary' : '' }}"
                            wire:click="pilihDenah({{ $d->id }})"
                        >
                            {{ $d->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if($denah)
            @include('livewire.public.partials.denah-tampil', ['denah' => $denah])
        @else
            {{-- Mode "semua": tiap denah terbit ditampilkan berurutan. --}}
            <div class="space-y-6">
                @foreach($denahTerbit as $d)
                    @include('livewire.public.partials.denah-tampil', ['denah' => $d])
                @endforeach
            </div>
        @endif

        {{-- Catatan kaki: peserta sering bertanya "DP saya yang mana", dan
             jawabannya ada di aturan penomoran, bukan di gambarnya. --}}
        <div class="surface-card p-5">
            <h3 class="font-display text-sm font-bold text-on-surface mb-2">
                <i class="ti ti-info-circle text-primary"></i> Cara Membaca Denah
            </h3>
            <ul class="text-xs text-on-surface-variant space-y-1 mb-0 list-disc pl-5">
                <li><strong>DP (Daerah Persiapan)</strong> diberi nomor dari yang paling dekat arena. DP 1 di ujung terdekat, nomor berikutnya menjauh.</li>
                <li>Panah penuh menandakan arah gerak pasukan menuju arena.</li>
                <li>Daerah persiapan yang ditampilkan di denah ini hanya menunjukkan <em>tempatnya</em>; penempatan pasukan per DP diumumkan panitia di lapangan.</li>
            </ul>
        </div>
    </div>

    @once
        <style>
            /* Partial denah-kanvas sengaja berdiri sendiri, dipakai juga oleh
               PDF dompdf yang tak mengenal Tailwind, jadi utilitas yang
               dipakainya didefinisikan ulang di sini. */
            .denah-tampil .min-w-0 { min-width: 0; }
        </style>
    @endonce
</div>
