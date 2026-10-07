@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Pertanyaan yang Sering Diajukan';
    $items = $data['items'] ?? [];

    // Kartu kontak menempel di sini. Dulu section "Hubungi Kami" sendiri:
    // tiga kartu besar di bawah FAQ yang isinya cuma nomor telepon dan alamat.
    // Digabung karena orang yang membaca FAQ adalah orang yang sedang mencari
    // bantuan — di situ juga tempatnya bertanya. Anchor id="contact" tetap
    // dipasang supaya tautan lama ke #contact tidak mati.
    $kontak = json_decode(\App\Models\Setting::get('landing_contact') ?? 'null', true) ?? [];
    $phone = $kontak['phone'] ?? '';
    $email = $kontak['email'] ?? '';
    $address = $kontak['address'] ?? '';
@endphp

@if(count($items) > 0 || $phone || $email || $address)
<section id="faq" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">FAQ</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-3 lg:items-start">
            {{-- Pertanyaan --}}
            @if(count($items) > 0)
            <div class="space-y-3 lg:col-span-2">
                @foreach($items as $index => $item)
                <details class="surface-card group overflow-hidden p-0" wire:key="faq-{{ $index }}">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-semibold text-deep-slate transition hover:bg-surface-container-low">
                        <span>{{ $item['question'] ?? '' }}</span>
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary transition group-open:rotate-180">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 text-sm leading-relaxed text-on-surface-variant">
                        {{ $item['answer'] ?? '' }}
                    </div>
                </details>
                @endforeach
            </div>
            @endif

            {{-- Kontak --}}
            <div id="contact" class="surface-card p-7 lg:col-span-1">
                <h3 class="font-display text-lg font-bold text-deep-slate">Masih ada pertanyaan?</h3>
                <p class="mt-2 text-sm text-on-surface-variant">Tim kami siap membantu.</p>

                <ul class="mt-6 space-y-4 text-sm">
                    @if($phone)
                    <li>
                        <a href="tel:{{ $phone }}" class="flex items-center gap-3 text-on-surface-variant transition hover:text-primary">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="ti ti-phone"></i>
                            </span>
                            <span>{{ $phone }}</span>
                        </a>
                    </li>
                    @endif
                    @if($email)
                    <li>
                        <a href="mailto:{{ $email }}" class="flex items-center gap-3 break-all text-on-surface-variant transition hover:text-primary">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <i class="ti ti-mail"></i>
                            </span>
                            <span>{{ $email }}</span>
                        </a>
                    </li>
                    @endif
                    @if($address)
                    <li class="flex items-start gap-3 text-on-surface-variant">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="ti ti-map-pin"></i>
                        </span>
                        <span class="pt-2">{{ $address }}</span>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</section>
@endif
