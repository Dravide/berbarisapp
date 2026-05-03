@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Apa Kata Mereka?';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<div class="section zubuz-section-padding2 bg-light" id="testimonials">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>{{ $title }}</h2>
        </div>
        <div class="row">
            @foreach($items as $index => $item)
            <div class="col-lg-6">
                <div class="zubuz-testimonial-wrap">
                    <div class="zubuz-testimonial-rating">
                        <ul>
                            @for($r = 0; $r < ($item['rating'] ?? 5); $r++)
                            <li><img src="{{ asset('templates/zubaz/assets/images/icon/star-green.svg') }}" alt=""></li>
                            @endfor
                        </ul>
                    </div>
                    <div class="zubuz-testimonial-data">
                        <h3>{{ $item['title'] ?? $item['name'] ?? '' }}</h3>
                        <p>"{{ $item['text'] ?? '' }}"</p>
                    </div>
                    <div class="zubuz-testimonial-author">
                        <div class="zubuz-testimonial-author-thumb">
                            @if(!empty($item['avatar']))
                                <img src="{{ Storage::url($item['avatar']) }}" alt="{{ $item['name'] ?? '' }}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                            @else
                                <img src="{{ asset('templates/zubaz/assets/images/v1/t_user' . (($index % 4) + 1) . '.png') }}" alt="">
                            @endif
                        </div>
                        <div class="zubuz-testimonial-author-data">
                            <span>{{ $item['name'] ?? '' }}</span>
                            <p>{{ $item['role'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
