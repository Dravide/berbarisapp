@foreach($categories as $category)
    <div class="cat-section">
        <table class="cat-head">
            <tr>
                <td class="cat-name">{{ $category->name }}</td>
            </tr>
        </table>

        @foreach($category->subCategories as $subcat)
            <div class="sub-head">{{ $subcat->name }}</div>

            @if($subcat->criterias->isNotEmpty())
                @php
                    // Urutan label unik → jadi kolom "SKOR PENILAIAN" (KURANG | CUKUP | BAIK | ...).
                    $labelCols = [];
                    foreach($subcat->criterias as $crit) {
                        foreach($crit->score_options ?? [] as $o) {
                            if (is_array($o) && !empty($o['label'])) {
                                $labelCols[] = $o['label'];
                            }
                        }
                    }
                    $labelCols = array_values(array_unique($labelCols));
                    $hasLabels = count($labelCols) > 0;
                    // Tiap skor jadi sel sendiri; colspan header = jumlah skor TERBESAR
                    // pada label itu antar semua kriteria (baris lebih pendek tetap muat).
                    $labelSpan = [];
                    foreach($labelCols as $label) {
                        $labelSpan[$label] = collect($subcat->criterias)
                            ->map(fn($c) => collect($c->score_options ?? [])
                                ->filter(fn($o) => is_array($o) && ($o['label'] ?? null) === $label)
                                ->map(fn($o) => $o['score'] ?? null)
                                ->filter(fn($v) => $v !== null)
                                ->unique()
                                ->count())
                            ->max() ?? 1;
                    }
                @endphp
                <table class="krit">
                    <thead>
                        <tr>
                            <th width="45%">Kriteria Penilaian</th>
                            <th width="12%" style="text-align:center;">Bobot</th>
                            @if($hasLabels)
                                @foreach($labelCols as $label)
                                    <th colspan="{{ $labelSpan[$label] }}" style="text-align:center;">{{ $label }}</th>
                                @endforeach
                            @else
                                <th width="43%" style="text-align:center;">Skor Penilaian</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subcat->criterias as $crit)
                        <tr>
                            <td class="cn">{{ $crit->name }}</td>
                            <td class="sv">{{ $crit->weight ?? 1 }}x</td>
                            @if($hasLabels)
                                @foreach($labelCols as $label)
                                    @php
                                        // Skor unik MILIK kriteria ini saja pada label tsb (mis. "12" dan "16").
                                        $cellScores = collect($crit->score_options ?? [])
                                            ->filter(fn($o) => is_array($o) && ($o['label'] ?? null) === $label)
                                            ->map(fn($o) => $o['score'] ?? null)
                                            ->filter(fn($v) => $v !== null)
                                            ->unique()
                                            ->values();
                                    @endphp
                                    @for($span = 0; $span < $labelSpan[$label]; $span++)
                                        @php $sval = $cellScores->get($span); @endphp
                                        <td class="sv">{{ $sval ?? '&nbsp;' }}</td>
                                    @endfor
                                @endforeach
                            @else
                                <td class="sv">
                                    @foreach($crit->score_options as $score)
                                        @php $sv = is_array($score) ? ($score['score'] ?? '') : $score; @endphp
                                        <span>{{ $sv }}</span>@if(!$loop->last) &nbsp;@endif
                                    @endforeach
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        @if($category->deductionCategories->isNotEmpty())
            <div class="deduction-head">Pengurangan Nilai (untuk kategori ini)</div>
            @foreach($category->deductionCategories as $deductionCat)
                <div class="sub-head">{{ $deductionCat->name }}</div>
                @if($deductionCat->criterias->isNotEmpty())
                    <table class="ded">
                        <thead>
                            <tr>
                                <th width="40%">Kriteria Pengurangan</th>
                                <th width="60%" style="text-align:center;">Opsi Pengurangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deductionCat->criterias as $deductionCrit)
                            <tr>
                                <td>{{ $deductionCrit->name }}</td>
                                <td style="text-align:center;">
                                    0
                                    @foreach($deductionCrit->deduction_options as $opt)
                                        &nbsp;{{ $opt }}
                                    @endforeach
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        @endif
    </div>
@endforeach
