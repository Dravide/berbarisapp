@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Siap Mengelola Event Lebih Efisien?';
    $description = $data['description'] ?? 'Mulai gunakan BARIS APP sekarang dan rasakan kemudahan mengelola event dan kompetisi secara digital. Daftar gratis dan langsung gunakan.';
    $buttonText = $data['button_text'] ?? 'Daftar Sekarang';
    $buttonUrl = $data['button_url'] ?? route('login');
    $image = $data['image'] ?? '';
@endphp

<div class="zubuz-cta-section blue-bg">
    <div class="container">
        <div class="row">
            <div class="col-lg-7 order-lg-2 d-flex align-items-center">
                <div class="zubuz-default-content light">
                    <h2>{{ $heading }}</h2>
                    <p>{{ $description }}</p>
                    <div class="zubuz-extara-mt">
                        <a class="zubuz-default-btn" href="{{ $buttonUrl }}" style="background: #fff; color: #0072FF;">
                            <span>{{ $buttonText }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="zubuz-cta-thumb">
                    @if($image)
                        <img src="{{ Storage::url($image) }}" alt="CTA">
                    @else
                        <img src="{{ asset('templates/zubaz/assets/images/v1/cta-mocup.png') }}" alt="">
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
