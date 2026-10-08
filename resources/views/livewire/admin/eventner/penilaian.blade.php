<div>
    <div class="row">
        <div class="col-12">
            <!-- Header & Breadcrumb -->
            <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-3">
                    <div class="row align-items-center">
                        <div class="col-9">
                            <h4 class="fw-semibold mb-8">Format Penilaian: {{ $eventner->nama_event }}</h4>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.index') }}">Eventner</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a class="text-muted text-decoration-none" href="{{ route('admin.eventner.show', $eventner->id) }}">{{ \Illuminate\Support\Str::limit($eventner->nama_event, 24) }}</a>
                                    </li>
                                    <li class="breadcrumb-item" aria-current="page">Format Penilaian</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-3">
                            <div class="text-center mb-n5">
                                <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $semuaKategori = $this->categories;
        $deduksiGlobal = $this->globalDeductions;

        // Seksi per tingkat dibangun dari GABUNGAN rubrik dan deduksi global:
        // tingkat yang deduksinya sudah diisi tapi rubriknya belum tetap harus
        // tampak, jangan tenggelam di pesan kosong.
        $kategoriPerTingkat = $semuaKategori->filter(fn ($c) => $c->competition_category_id !== null)->groupBy('competition_category_id');
        $deduksiPerTingkat = $deduksiGlobal->filter(fn ($d, $tingkatId) => $tingkatId !== null);
        $idTingkat = $kategoriPerTingkat->keys()->merge($deduksiPerTingkat->keys())->unique()->sort()->values();

        $tanpaTingkat = $semuaKategori->filter(fn ($c) => $c->competition_category_id === null);
        $deduksiTanpaTingkat = $deduksiGlobal->get(null, collect());
    @endphp

    @if($semuaKategori->isEmpty() && $deduksiGlobal->isEmpty())
        <div class="alert alert-info border-0 bg-info-subtle">
            Belum ada format penilaian.
        </div>
    @else
        {{-- Seksi 1: rubrik & deduksi tanpa tingkat lomba — berlaku di semua tingkat --}}
        @if($tanpaTingkat->isNotEmpty() || $deduksiTanpaTingkat->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-checklist text-primary me-2"></i>Berlaku Semua Tingkat
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($tanpaTingkat as $category)
                        @include('livewire.admin.eventner.partials.rubrik-category', ['category' => $category])
                    @endforeach

                    @if($deduksiTanpaTingkat->isNotEmpty())
                        <div class="mt-3">
                            <h6 class="fw-semibold text-muted mb-2">Pengurangan Global</h6>
                            @foreach($deduksiTanpaTingkat as $deductionCat)
                                @include('livewire.admin.eventner.partials.deduction-category', ['deductionCat' => $deductionCat])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Seksi 2: satu card per tingkat lomba --}}
        @foreach($idTingkat as $tingkatId)
            @php
                $kategoriTingkat = $kategoriPerTingkat->get($tingkatId, collect());
                $contoh = $kategoriTingkat->first();
                $tingkat = $contoh ? $contoh->competitionCategory : null;
                $namaTingkat = $tingkat?->full_name ?? ('Tingkat #' . $tingkatId);
                $deduksiTingkat = $deduksiPerTingkat->get($tingkatId, collect());
            @endphp

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-checklist text-primary me-2"></i>{{ $namaTingkat }}
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($kategoriTingkat as $category)
                        @include('livewire.admin.eventner.partials.rubrik-category', ['category' => $category])
                    @endforeach

                    @if($deduksiTingkat->isNotEmpty())
                        <div class="mt-3">
                            <h6 class="fw-semibold text-muted mb-2">Pengurangan Global Tingkat Ini</h6>
                            @foreach($deduksiTingkat as $deductionCat)
                                @include('livewire.admin.eventner.partials.deduction-category', ['deductionCat' => $deductionCat])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
