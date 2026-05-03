@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Fitur Lengkap untuk Event Sukses';
    $items = $data['items'] ?? [
        ['icon' => 'icon3.png', 'title' => 'Manajemen Pendaftaran', 'description' => 'Kelola pendaftaran peserta secara digital dengan verifikasi otomatis dan tracking status real-time.'],
        ['icon' => 'icon4.png', 'title' => 'Penilaian Juri Digital', 'description' => 'Sistem penilaian digital dengan format kustom, perhitungan otomatis, dan rekap nilai instan.'],
        ['icon' => 'icon5.png', 'title' => 'Voting Online', 'description' => 'Fitur voting online terintegrasi dengan pembayaran digital untuk penghargaan favorit penonton.'],
        ['icon' => 'icon6.png', 'title' => 'E-Tiket & Pembayaran', 'description' => 'Jual tiket event secara online dengan integrasi gateway pembayaran dan QR code check-in.'],
        ['icon' => 'icon7.png', 'title' => 'Live Scoreboard', 'description' => 'Papan skor real-time yang bisa dipancarkan ke layar proyektor untuk transparansi penilaian.'],
        ['icon' => 'icon8.png', 'title' => 'Drawing & Undian', 'description' => 'Sistem undian digital untuk menentukan urutan tampil peserta dengan animasi menarik.'],
    ];
@endphp

<div class="section zubuz-section-padding3 bg-light" id="features">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>{{ $title }}</h2>
        </div>
        <div class="row">
            @foreach($items as $index => $item)
            <div class="col-xl-4 col-md-6" wire:key="feature-{{ $index }}">
                <div class="zubuz-iconbox-wrap center">
                    <div class="zubuz-iconbox-icon">
                        @if(!empty($item['icon_custom']))
                            <img src="{{ Storage::url($item['icon_custom']) }}" alt="{{ $item['title'] ?? '' }}" style="max-height: 60px;">
                        @else
                            <img src="{{ asset('templates/zubaz/assets/images/v1/' . ($item['icon'] ?? 'icon3.png')) }}" alt="">
                        @endif
                    </div>
                    <div class="zubuz-iconbox-data">
                        <h3>{{ $item['title'] ?? '' }}</h3>
                        <p>{{ $item['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
