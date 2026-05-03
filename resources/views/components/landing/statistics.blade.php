@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $items = $data['items'] ?? [
        ['value' => '500', 'suffix' => '+', 'label' => 'Event Diselenggarakan'],
        ['value' => '10K', 'suffix' => '+', 'label' => 'Peserta Terdaftar'],
        ['value' => '50', 'suffix' => '+', 'label' => 'Kota di Indonesia'],
        ['value' => '99', 'suffix' => '%', 'label' => 'Kepuasan Pengguna'],
    ];
@endphp

@if(count($items) > 0)
<div class="section dark-bg zubuz-section-padding4">
    <div class="container">
        <div class="row">
            @foreach($items as $index => $item)
            <div class="col-lg-3 col-md-6">
                <div class="zubuz-counter-wrap text-center text-white">
                    <h2 class="text-white" style="font-size: 48px; font-weight: 700;">
                        {{ $item['value'] ?? 0 }}{{ $item['suffix'] ?? '' }}
                    </h2>
                    <p class="text-white-50">{{ $item['label'] ?? '' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
