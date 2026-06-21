@php
    $data = json_decode($section?->content ?? 'null', true) ?? $defaults ?? [];
    $title = $data['title'] ?? 'Pertanyaan yang Sering Diajukan';
    $items = $data['items'] ?? [];
@endphp

@if(count($items) > 0)
<section id="faq" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">FAQ</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
        </div>

        <div class="mx-auto mt-10 max-w-3xl space-y-3">
            @foreach($items as $index => $item)
            <details class="surface-card group overflow-hidden p-0" wire:key="faq-{{ $index }}">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-semibold text-deep-slate transition hover:bg-surface-container-low">
                    <span>{{ $item['question'] ?? '' }}</span>
                    <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary transition group-open:rotate-180">
                        <i class="ti ti-chevron-down"></i>
                    </span>
                </summary>
                <div class="px-5 pb-5 text-sm leading-relaxed text-on-surface-variant">
                    {{ $item['answer'] ?? '' }}
                </div>
            </details>
            @endforeach
        </div>
    </div>
</section>
@endif
