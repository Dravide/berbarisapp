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

<div class="section zubuz-section-padding2" id="about">
    <div class="container">
        <div class="row">
            <div class="col-lg-5">
                <div class="zubuz-thumb thumb-pr">
                    @if($image)
                        <img src="{{ Storage::url($image) }}" alt="About" style="border-radius: 16px;">
                    @else
                        <img src="{{ asset('templates/zubaz/assets/images/v1/mocup01.png') }}" alt="">
                    @endif
                    <div class="zubuz-thumb-card">
                        <img src="{{ asset('templates/zubaz/assets/images/v1/card1.png') }}" alt="">
                    </div>
                </div>
            </div>
            <div class="col-lg-7 d-flex align-items-center">
                <div class="zubuz-default-content">
                    <h2>{{ $heading }}</h2>
                    <p>{{ $description }}</p>
                    <div class="zubuz-extara-mt">
                        @foreach($points as $point)
                        <p><span class="font-semibold">{{ $point['title'] ?? '' }}:</span> {{ $point['text'] ?? '' }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
