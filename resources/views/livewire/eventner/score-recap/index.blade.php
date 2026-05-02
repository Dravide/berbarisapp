<div>
    {{-- Page Header (Template Standard) --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Rekap Nilai</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">{{ $eventner->nama_event }}</li>
                            <li class="breadcrumb-item active" aria-current="page">Rekap Nilai</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Category Tabs --}}
    <div class="card w-100">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-semibold mb-0">Rekapitulasi Nilai per Kategori</h5>
                @if($selectedCategoryId)
                    <a href="{{ route('eventner.scoring.pdf', ['category_id' => $selectedCategoryId]) }}"
                       class="btn btn-sm btn-outline-danger" target="_blank">
                        <i class="ti ti-file-type-pdf me-1"></i> Download PDF
                    </a>
                @endif
            </div>

            {{-- Tabs (Template Standard: nav-tabs nav-fill) --}}
            <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                @foreach($categories as $cat)
                    <li class="nav-item" role="presentation">
                        <button wire:click="selectCategory({{ $cat->id }})"
                                class="nav-link {{ $selectedCategoryId == $cat->id ? 'active bg-primary text-white' : '' }}"
                                type="button" role="tab">
                            <i class="ti ti-medal me-1"></i> {{ $cat->name }}
                            <span class="badge {{ $selectedCategoryId == $cat->id ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' }} ms-1">{{ $cat->registrations_count }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            @if($selectedCategory)
                {{-- Table Content --}}
                @if($scoringData->isEmpty())
                    <div class="text-center py-5">
                        <i class="ti ti-clipboard-off fs-10 text-muted d-block mb-3"></i>
                        <h5 class="fw-semibold text-muted">Belum Ada Data</h5>
                        <p class="text-muted">Belum ada peserta atau penilaian pada kategori ini.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle text-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Rank</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Kontingen</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Pelatih</h6></th>
                                    @foreach($assessmentCategories as $ac)
                                        <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">{{ $ac->name }}</h6></th>
                                    @endforeach
                                    <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Total</h6></th>
                                    <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">PDF</h6></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scoringData as $index => $data)
                                    <tr>
                                        <td class="text-center">
                                            @if($index === 0)
                                                <span class="badge bg-warning text-dark fw-semibold">🥇 1</span>
                                            @elseif($index === 1)
                                                <span class="badge bg-secondary text-white fw-semibold">🥈 2</span>
                                            @elseif($index === 2)
                                                <span class="badge bg-success text-white fw-semibold">🥉 3</span>
                                            @else
                                                <span class="text-muted fw-semibold">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($data['participant']->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $data['participant']->logo_sekolah) }}" class="rounded-circle border" width="36" height="36" style="object-fit:cover;" alt="">
                                                @else
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:36px;height:36px;">
                                                        <i class="ti ti-school fs-5"></i>
                                                    </div>
                                                @endif
                                                <h6 class="fw-semibold mb-0">{{ $data['participant']->nama_sekolah }}</h6>
                                            </div>
                                        </td>
                                        <td><span class="text-muted">{{ $data['participant']->nama_pelatih }}</span></td>
                                        @foreach($assessmentCategories as $ac)
                                            @php $catScore = $data['categoryTotals'][$ac->id] ?? 0; @endphp
                                            <td class="text-center">
                                                <span class="fw-semibold {{ $catScore > 0 ? '' : 'text-muted' }}">{{ $catScore }}</span>
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            <span class="badge bg-primary fw-semibold fs-3 px-3">{{ $data['grandTotal'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('eventner.scoring.pdf-participant', ['registration_id' => $data['participant']->id]) }}"
                                               class="btn btn-sm btn-outline-danger p-1" target="_blank" title="Download PDF">
                                                <i class="ti ti-file-type-pdf fs-4"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary Stats --}}
                    <div class="row g-3 mt-4">
                        <div class="col-md-3">
                            <div class="card mb-0 bg-success-subtle border-0">
                                <div class="card-body p-3 text-center">
                                    <p class="text-muted small mb-1 fw-semibold">Nilai Tertinggi</p>
                                    <h3 class="fw-semibold text-success mb-0">{{ $scoringData->max('grandTotal') }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-0 bg-danger-subtle border-0">
                                <div class="card-body p-3 text-center">
                                    <p class="text-muted small mb-1 fw-semibold">Nilai Terendah</p>
                                    <h3 class="fw-semibold text-danger mb-0">{{ $scoringData->min('grandTotal') }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-0 bg-primary-subtle border-0">
                                <div class="card-body p-3 text-center">
                                    <p class="text-muted small mb-1 fw-semibold">Rata-rata</p>
                                    <h3 class="fw-semibold text-primary mb-0">{{ round($scoringData->avg('grandTotal'), 1) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card mb-0 bg-info-subtle border-0">
                                <div class="card-body p-3 text-center">
                                    <p class="text-muted small mb-1 fw-semibold">Total Peserta</p>
                                    <h3 class="fw-semibold text-info mb-0">{{ $scoringData->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="ti ti-category fs-10 text-muted d-block mb-3"></i>
                    <h5 class="fw-semibold text-muted">Pilih Kategori</h5>
                    <p class="text-muted">Pilih kategori lomba di tab di atas untuk melihat rekap nilai.</p>
                </div>
            @endif
        </div>
    </div>
</div>
