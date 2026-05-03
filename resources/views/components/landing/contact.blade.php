@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $phone = $data['phone'] ?? '';
    $email = $data['email'] ?? '';
    $address = $data['address'] ?? '';
    $mapEmbed = $data['map_embed_url'] ?? '';
@endphp

@if($phone || $email || $address)
<div class="section zubuz-section-padding3" id="contact">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>Hubungi Kami</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="row">
                    @if($phone)
                    <div class="col-md-4 mb-4">
                        <div class="zubuz-iconbox-wrap center">
                            <div class="zubuz-iconbox-icon">
                                <img src="{{ asset('templates/zubaz/assets/images/v1/icon3.png') }}" alt="">
                            </div>
                            <div class="zubuz-iconbox-data">
                                <h3>Telepon</h3>
                                <p><a href="tel:{{ $phone }}" class="text-dark">{{ $phone }}</a></p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($email)
                    <div class="col-md-4 mb-4">
                        <div class="zubuz-iconbox-wrap center">
                            <div class="zubuz-iconbox-icon">
                                <img src="{{ asset('templates/zubaz/assets/images/v1/icon4.png') }}" alt="">
                            </div>
                            <div class="zubuz-iconbox-data">
                                <h3>Email</h3>
                                <p><a href="mailto:{{ $email }}" class="text-dark">{{ $email }}</a></p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($address)
                    <div class="col-md-4 mb-4">
                        <div class="zubuz-iconbox-wrap center">
                            <div class="zubuz-iconbox-icon">
                                <img src="{{ asset('templates/zubaz/assets/images/v1/icon5.png') }}" alt="">
                            </div>
                            <div class="zubuz-iconbox-data">
                                <h3>Alamat</h3>
                                <p>{{ $address }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @if($mapEmbed)
                <div class="rounded overflow-hidden border mt-3">
                    <iframe src="{{ $mapEmbed }}" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
