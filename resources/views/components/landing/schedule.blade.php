@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Jadwal Acara';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<div class="section zubuz-section-padding3 bg-light" id="schedule">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>{{ $title }}</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @foreach($items as $index => $item)
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="bg-primary text-white rounded p-3 text-center flex-shrink-0" style="min-width: 100px;">
                        <div class="fs-8 fw-bold">{{ $item['date'] ?? '' }}</div>
                        <div class="fs-3">{{ $item['time'] ?? '' }}</div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-semibold">{{ $item['title'] ?? '' }}</h5>
                        <p class="text-muted mb-1">{{ $item['description'] ?? '' }}</p>
                        @if(!empty($item['location']))
                        <small class="text-primary"><i class="fas fa-map-marker-alt me-1"></i>{{ $item['location'] }}</small>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
