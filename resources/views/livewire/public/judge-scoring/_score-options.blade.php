@php
    // Kelompokkan opsi per label — bentuk score_options bisa scalar atau
    // {score,label} (sama seperti dashboard panitia).
    $groups = [];
    foreach ($scoreOptions as $o) {
        $sv = is_array($o) ? ($o['score'] ?? null) : $o;
        $lb = is_array($o) ? ($o['label'] ?? null) : null;
        $groups[$lb ?: (string) $sv][] = ['score' => $sv, 'label' => $lb];
    }
    // Judul grup hanya berguna bila opsinya memang berlabel.
    $showGroupLabels = collect($groups)->keys()->contains(fn ($k) => !is_numeric($k));
    $filled = isset($scores[$criteriaId]) && $scores[$criteriaId] !== '' && $scores[$criteriaId] !== null;
@endphp

{{-- Tombol nilai. Kelompok berlabel disusun sebagai kolom: label di ATAS
     tombol miliknya, bukan menyelip di antara tombol (terbaca seperti
     tombol nilai tambahan). Ukuran tombol diatur $buttonSize pemanggil. --}}
<div class="flex flex-wrap items-start gap-x-3 gap-y-2 {{ $optionsWrapClass ?? '' }}">
    @foreach($groups as $label => $opts)
        <div class="flex flex-col items-center gap-1">
            @if($showGroupLabels)
                <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant leading-none">
                    {{ $label }}
                </span>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                @foreach($opts as $opt)
                    @php $selected = $filled && (string) $scores[$criteriaId] === (string) $opt['score']; @endphp
                    <button type="button"
                            wire:click="setScore({{ $criteriaId }}, '{{ $opt['score'] }}')"
                            wire:loading.attr="disabled"
                            @disabled($isFinalized)
                            class="{{ $scoreBtnBase }} {{ $buttonSize }}
                                {{ $selected
                                    ? 'bg-primary text-white border-primary shadow-sm'
                                    : 'bg-white text-on-surface border-outline-variant/50 hover:border-primary hover:text-primary' }}
                                {{ $isFinalized ? 'opacity-50 cursor-not-allowed' : 'active:scale-95' }}">
                        {{ $opt['score'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach

    <span class="self-center w-6 text-center text-[11px] font-bold text-emerald-600">
        @if($filled)<i class="ti ti-check"></i>@endif
    </span>
</div>
