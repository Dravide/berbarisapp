@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Pertanyaan yang Sering Diajukan';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<div class="section zubuz-section-padding2" id="faq">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>{{ $title }}</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="zubuz-accordion-wrap" id="zubuz-accordion">
                    @foreach($items as $index => $item)
                    <div class="zubuz-accordion-item {{ $index === 0 ? 'open' : '' }}">
                        <div class="zubuz-accordion-header">
                            <h3>{{ $item['question'] ?? '' }}</h3>
                            <div class="zubuz-active-icon">
                                <svg width="22" height="13" viewBox="0 0 22 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="zubuz-accordion-body">
                            <p>{{ $item['answer'] ?? '' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
