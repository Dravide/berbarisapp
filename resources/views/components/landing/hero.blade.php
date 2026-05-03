@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $heading = $data['heading'] ?? 'Kelola Event & Kompetisi dengan Mudah';
    $subheading = $data['subheading'] ?? 'Platform manajemen event terpadu yang membantu penyelenggara mengelola pendaftaran, penilaian, voting, dan tiket secara digital.';
    $ctaText = $data['cta_text'] ?? 'Mulai Sekarang';
    $ctaUrl = $data['cta_url'] ?? route('login');
    $videoUrl = $data['video_url'] ?? '';
    $bgImage = $data['background_image'] ?? '';
@endphp

<div class="zubuz-hero-section" id="hero" @if($bgImage) style="background-image: url({{ Storage::url($bgImage) }})" @else style="background-image: url({{ asset('templates/zubaz/assets/images/v1/hero-shape1.png') }})" @endif>
    <div class="container">
        <div class="zubuz-hero-content center position-relative">
            <h1>{{ $heading }}</h1>
            <p>{{ $subheading }}</p>
            <div class="zubuz-hero-btn-wrap center">
                <a class="zubuz-default-btn" href="{{ $ctaUrl }}">
                    <span>{{ $ctaText }}</span>
                </a>
                @if($videoUrl)
                <a class="video-init zubuz-hero-video" href="{{ $videoUrl }}">
                    <img src="{{ asset('templates/zubaz/assets/images/v1/play-btn.png') }}" alt="">
                    Tonton Demo
                </a>
                @endif
            </div>
            <div class="zubuz-hero-shape">
                <img src="{{ asset('templates/zubaz/assets/images/v1/shape.png') }}" alt="">
            </div>
        </div>
        <div class="zubuz-hero-bottom">
            <div class="zubuz-hero-thumb wow fadeInUpX">
                <img src="{{ asset('templates/zubaz/assets/images/v1/hero-mocup1.png') }}" alt="">
            </div>
            <div class="zubuz-hero-card card1 wow zoomIn">
                <img src="{{ asset('templates/zubaz/assets/images/v1/h-card1.png') }}" alt="">
            </div>
            <div class="zubuz-hero-card card2 wow zoomIn">
                <img src="{{ asset('templates/zubaz/assets/images/v1/h-card2.png') }}" alt="">
            </div>
            <div class="zubuz-hero-card card3 wow zoomIn">
                <img src="{{ asset('templates/zubaz/assets/images/v1/h-card4.png') }}" alt="">
            </div>
            <div class="zubuz-hero-card card4 wow zoomIn">
                <img src="{{ asset('templates/zubaz/assets/images/v1/h-card3.png') }}" alt="">
            </div>
        </div>
    </div>
</div>
