{{-- Rubrik satu kategori penilaian: header, sub-kategori, kriteria + bobot + opsi skor.
    Markup Bootstrap mengikuti pratinjau juri di builder.blade.php — partial PDF
    _rubrik_body tidak dipakai karena CSS-nya hanya hidup di dalam <style> PDF. --}}
<div class="mb-4">
    <div class="bg-dark text-white p-2 fw-semibold mb-3 rounded">
        {{ $category->name }}
        @if($category->competitionRound)
            <span class="badge bg-light text-dark ms-2">Babak: {{ $category->competitionRound->name }}</span>
        @endif
        @if($category->competitionSeries)
            <span class="badge bg-light text-dark ms-2">Seri: {{ $category->competitionSeries->name }}</span>
        @endif
    </div>

    @forelse($category->subCategories as $subCat)
        <div class="ms-3 mb-3">
            <div class="fw-semibold bg-light p-2 mb-2 border">{{ $subCat->name }}</div>

            @if($subCat->criterias->isNotEmpty())
                <table class="table table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="40%">Kriteria</th>
                            <th width="10%" class="text-center">Bobot</th>
                            <th width="50%" class="text-center">Pilihan Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subCat->criterias as $crit)
                            <tr>
                                <td class="fw-medium align-middle">{{ $crit->name }}</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary-subtle text-primary">
                                        {{ \App\Support\ScoreOptions::format((float) $crit->weight) }}x
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        @foreach($crit->score_options ?? [] as $score)
                                            @php $sv = is_array($score) ? $score['score'] : $score; $lb = is_array($score) ? ($score['label'] ?? null) : null; @endphp
                                            <label class="px-2 py-1 border text-center d-flex flex-column align-items-center lh-1" style="min-width: 40px;">
                                                <span>{{ $sv }}</span>
                                                @if($lb) <small class="fs-1 opacity-75 mt-0">{{ $lb }}</small> @endif
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted fs-2 mb-0">Belum ada kriteria.</p>
            @endif
        </div>
    @empty
        <p class="text-muted ms-3">Belum ada sub-kategori.</p>
    @endforelse

    @if($category->deductionCategories->isNotEmpty())
        <div class="ms-3 mb-3">
            <div class="fw-semibold bg-danger-subtle text-danger p-2 mb-2 border">Pengurangan Nilai</div>
            @foreach($category->deductionCategories as $deductionCat)
                @include('livewire.admin.eventner.partials.deduction-category', ['deductionCat' => $deductionCat])
            @endforeach
        </div>
    @endif
</div>
