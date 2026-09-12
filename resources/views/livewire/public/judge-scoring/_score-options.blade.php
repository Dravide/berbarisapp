@php
    // Kelompokkan opsi per label — bentuk score_options bisa scalar atau
    // {score,label} (sama seperti dashboard panitia). Label yang sama
    // digabung, jadi "Kurang 10" dan "Kurang 20" jadi satu blok.
    $groups = [];
    foreach ($scoreOptions as $o) {
        $sv = is_array($o) ? ($o['score'] ?? null) : $o;
        $lb = is_array($o) ? ($o['label'] ?? null) : null;
        $groups[$lb ?: (string) $sv][] = ['score' => $sv, 'label' => $lb];
    }

    // Judul grup hanya berguna bila opsinya memang berlabel.
    $showGroupLabels = collect($groups)->keys()->contains(fn ($k) => !is_numeric($k));

    // Warna blok per label supaya juri mengenali tingkatan tanpa membaca:
    // kurang merah, cukup kuning, baik biru, sangat baik hijau. "Sangat"
    // diperiksa lebih dulu karena juga mengandung kata "baik".
    $palet = function (string $label) {
        $l = mb_strtolower(trim($label));
        return match (true) {
            str_contains($l, 'sangat') => 'text-emerald-700 border-emerald-300 bg-emerald-50',
            str_contains($l, 'kurang') => 'text-rose-700 border-rose-300 bg-rose-50',
            str_contains($l, 'cukup')  => 'text-amber-700 border-amber-300 bg-amber-50',
            str_contains($l, 'baik')   => 'text-sky-700 border-sky-300 bg-sky-50',
            default => 'text-on-surface-variant border-outline-variant/40 bg-surface',
        };
    };

    $filled = isset($scores[$criteriaId]) && $scores[$criteriaId] !== '' && $scores[$criteriaId] !== null;
@endphp

{{-- Tombol nilai. Tiap label jadi blok sendiri (label di atas, tombol di
     bawahnya, kotak berwarna pemisah) supaya tingkatan tidak tercampur saat
     satu kriteria punya banyak opsi. Ukuran tombol diatur $buttonSize. --}}
<div class="flex flex-wrap items-start gap-2 {{ $optionsWrapClass ?? '' }}">
    @foreach($groups as $label => $opts)
        @php $berlabel = $showGroupLabels && !is_numeric($label); @endphp

        <div class="flex flex-col items-center gap-1.5 rounded-xl border px-2.5 py-1.5
                    {{ $berlabel ? $palet($label) : 'border-transparent px-0 py-0' }}">
            @if($berlabel)
                <span class="text-[10px] font-bold uppercase tracking-wide leading-none">
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
