@props([
    'size' => 'sm',
    'withLabel' => false,
    'title' => 'Penyelenggara Terverifikasi',
    'class' => '',
])

@php
    $dimensions = match($size) {
        'xs' => 'w-3.5 h-3.5',
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
        default => 'w-4 h-4',
    };
    $px = match($size) {
        'xs' => 14,
        'sm' => 16,
        'md' => 20,
        'lg' => 24,
        default => 16,
    };
@endphp

@if($withLabel)
    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-semibold text-primary border border-primary/20 {{ $class }}" title="{{ $title }}">
        <svg width="{{ $px }}" height="{{ $px }}" class="{{ $dimensions }} shrink-0 text-primary" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" style="display:inline-block;vertical-align:middle;">
            <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01z" fill="currentColor"/>
            <path d="M10.354 6.854a.5.5 0 0 0-.708-.708L7 8.793 5.354 7.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0z" fill="#ffffff"/>
        </svg>
        <span>Terverifikasi</span>
    </span>
@else
    <span class="inline-flex items-center text-primary align-middle {{ $class }}" title="{{ $title }}">
        <svg width="{{ $px }}" height="{{ $px }}" class="{{ $dimensions }} shrink-0 text-primary" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" style="display:inline-block;vertical-align:middle;">
            <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01z" fill="currentColor"/>
            <path d="M10.354 6.854a.5.5 0 0 0-.708-.708L7 8.793 5.354 7.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0z" fill="#ffffff"/>
        </svg>
    </span>
@endif
