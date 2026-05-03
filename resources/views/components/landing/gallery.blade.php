@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Galeri';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<div class="section zubuz-section-padding3">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>{{ $title }}</h2>
        </div>
        <div class="row">
            @foreach($items as $index => $item)
            @if(!empty($item['image']))
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="zubuz-portfolio-wrap">
                    <div class="zubuz-portfolio-thumb">
                        <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['caption'] ?? '' }}" style="border-radius: 12px; width: 100%; height: 250px; object-fit: cover;">
                    </div>
                    @if(!empty($item['caption']))
                    <div class="zubuz-portfolio-data">
                        <p>{{ $item['caption'] }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
@endif
