@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    $mapEmbed = $data['map_embed_url'] ?? '';
@endphp

@if($phone || $email || $address)
<section id="contact" class="section-pad bg-surface-container-low">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Kontak</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">Hubungi Kami</h2>
            <p class="mt-4 text-on-surface-variant">Punya pertanyaan seputar platform kami? Tim kami siap membantu.</p>
        </div>

        <div class="mx-auto mt-12 grid max-w-4xl grid-cols-1 gap-6 md:grid-cols-3">
            @if($phone)
            <a href="tel:{{ $phone }}" class="surface-card surface-card-hover group p-7 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary transition group-hover:bg-secondary group-hover:text-deep-slate">
                    <i class="ti ti-phone text-2xl"></i>
                </div>
                <h3 class="font-bold text-deep-slate">Telepon</h3>
                <p class="mt-1 text-sm text-on-surface-variant">{{ $phone }}</p>
            </a>
            @endif
            @if($email)
            <a href="mailto:{{ $email }}" class="surface-card surface-card-hover group p-7 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary transition group-hover:bg-secondary group-hover:text-deep-slate">
                    <i class="ti ti-mail text-2xl"></i>
                </div>
                <h3 class="font-bold text-deep-slate">Email</h3>
                <p class="mt-1 break-all text-sm text-on-surface-variant">{{ $email }}</p>
            </a>
            @endif
            @if($address)
            <div class="surface-card surface-card-hover p-7 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <i class="ti ti-map-pin text-2xl"></i>
                </div>
                <h3 class="font-bold text-deep-slate">Alamat</h3>
                <p class="mt-1 text-sm text-on-surface-variant">{{ $address }}</p>
            </div>
            @endif
        </div>

        @if($mapEmbed)
        <div class="mx-auto mt-8 max-w-4xl overflow-hidden rounded-2xl border border-outline-variant/60">
            <iframe src="{{ $mapEmbed }}" width="100%" height="320" style="border:0; display:block;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        @endif
    </div>
</section>
@endif
