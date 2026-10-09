@php
    // Data live dari tabel landing_partners (dikelola /admin/landing-partners),
    // bukan setting JSON — logonya butuh penyimpanan file, jadi tabel.
    $partners = \App\Models\LandingPartner::where('is_active', true)
        ->orderBy('sort_order')
        ->get()
        ->groupBy('type');

    $grupTipe = [
        'sponsor' => ['label' => 'Sponsor', 'tinggi' => 'h-14 md:h-20'],
        'medpart' => ['label' => 'Media Partner', 'tinggi' => 'h-10 md:h-14'],
    ];
@endphp

@if($partners->count() > 0)
<section id="sponsor" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Mitra</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">Sponsor &amp; Media Partner</h2>
            <p class="mt-4 text-on-surface-variant">Terima kasih kepada para mitra yang mendukung platform ini.</p>
        </div>

        <div class="mt-12 flex flex-col items-center gap-10">
            @foreach($grupTipe as $tipe => $grup)
                @if(!empty($partners[$tipe]))
                    <div class="w-full">
                        <span class="overline block text-center mb-6">{{ $grup['label'] }}</span>
                        <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
                            @foreach($partners[$tipe] as $partner)
                                @if($partner->link)
                                    <a href="{{ $partner->link }}" target="_blank" rel="noopener" class="transition hover:opacity-80">
                                @endif
                                @if($partner->logo)
                                    <img src="{{ Storage::url($partner->logo) }}" alt="{{ $partner->name }}"
                                        class="{{ $grup['tinggi'] }} w-auto object-contain max-w-[180px]" loading="lazy">
                                @else
                                    <span class="text-sm font-bold uppercase tracking-wider text-on-surface-variant">{{ $partner->name }}</span>
                                @endif
                                @if($partner->link)
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif
