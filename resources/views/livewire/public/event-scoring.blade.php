<div>
    {{-- ========== GATE: Split-Screen Password Access ========== --}}
    @if(!$authenticated)
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
            <div class="position-relative z-index-5">
                <div class="row g-0">
                    <div class="col-xl-7 col-xxl-8">
                        <a href="{{ url('/') }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
                            <img src="{{ asset('templates/assets/images/logos/dark-logo.svg') }}" class="dark-logo" alt="Logo-Dark" />
                            <img src="{{ asset('templates/assets/images/logos/light-logo.svg') }}" class="light-logo" alt="Logo-light" />
                        </a>
                        <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
                            <div class="text-center px-5">
                                <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="Scoring Access" class="img-fluid mb-4" width="500">
                                <h2 class="text-white fw-bolder fs-10 mb-2">{{ $eventner->nama_event }}</h2>
                                <p class="text-white text-opacity-75 fs-4">Panel Penilaian Panitia — Masukkan kode akses untuk melanjutkan</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
                                <div class="d-xl-none text-center mb-5">
                                    <a href="{{ url('/') }}" class="text-nowrap logo-img d-inline-block">
                                        <img src="{{ asset('templates/assets/images/logos/dark-logo.svg') }}" class="dark-logo" alt="Logo" width="180" />
                                    </a>
                                </div>
                                <h2 class="mb-1 fs-7 fw-bolder">Akses Panitia</h2>
                                <p class="mb-7">{{ $eventner->nama_event }}</p>

                                @if (session()->has('scoring_error'))
                                    <div class="alert alert-danger alert-dismissible fade show rounded-2 mb-4 d-flex align-items-center gap-2" role="alert">
                                        <i class="ti ti-alert-circle fs-5 flex-shrink-0"></i>
                                        <span>{{ session('scoring_error') }}</span>
                                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <form wire:submit.prevent="authenticate">
                                    <div class="mb-3">
                                        <label for="scoringCodeInput" class="form-label">Kode Akses Penilaian</label>
                                        <input type="password" wire:model="scoringCodeInput" class="form-control" id="scoringCodeInput" placeholder="Masukkan kode akses..." autocomplete="off" autofocus>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">
                                        <span wire:loading.remove wire:target="authenticate"><i class="ti ti-login me-2"></i> Masuk Panel</span>
                                        <span wire:loading wire:target="authenticate"><span class="spinner-border spinner-border-sm me-2"></span> Memverifikasi...</span>
                                    </button>
                                    <div class="d-flex align-items-center justify-content-center">
                                        <p class="fs-4 mb-0 fw-medium">Bukan panitia?</p>
                                        <a class="text-primary fw-medium ms-2" href="{{ event_url($eventner, 'detail') }}">Kembali ke Event</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- ========== AUTHENTICATED ========== --}}
    @else
        <section class="bg-primary text-white pt-7 pb-5">
            <div class="custom-container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <span class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill fw-bolder"><i class="ti ti-check me-1"></i> Terautentikasi</span>
                        <h1 class="text-white fw-bolder fs-10 mb-2">Panel Input Nilai</h1>
                        <p class="text-white opacity-75 mb-4">Masukkan nilai peserta <strong class="text-secondary">{{ $eventner->nama_event }}</strong></p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ event_url($eventner, 'detail') }}" class="btn btn-outline-light rounded-pill px-4 text-white border-white"><i class="ti ti-info-circle me-1"></i> Info Event</a>
                            <a href="{{ event_url($eventner, 'participant') }}" class="btn btn-outline-light rounded-pill px-4 text-white border-white"><i class="ti ti-users me-1"></i> Daftar Peserta</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 bg-body">
            <div class="container">
                @if (session()->has('scoring_error'))
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4" role="alert">
                        {{ session('scoring_error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($view == 'categories')
                    {{-- SELECT CATEGORY --}}
                    <div class="mb-5">
                        <div class="text-center mb-5">
                            <span class="badge bg-light text-dark px-3 py-2 rounded-pill mb-2 border">Langkah 1</span>
                            <h3 class="fw-bolder">Pilih Kategori Lomba</h3>
                            <p class="text-secondary">Pilih kategori untuk melihat daftar peserta</p>
                        </div>
                        <div class="row g-4">
                            @foreach($categories as $cat)
                                <div class="col-md-6 col-lg-4">
                                    <div wire:click="selectCategory({{ $cat->id }})" class="card border shadow-sm transition-all hover-shadow cursor-pointer rounded-4 h-100 mb-0">
                                        <div class="card-body p-4 text-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                                                <i class="ti ti-medal fs-8"></i>
                                            </div>
                                            <h5 class="fw-bolder mb-1">{{ $cat->full_name }}</h5>
                                            <p class="text-secondary mb-0 fs-3">{{ $cat->registrations_count }} Peserta</p>
                                            <div class="mt-3">
                                                <span class="btn btn-sm btn-primary rounded-pill px-3"><i class="ti ti-edit me-1"></i> Input Nilai</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                @elseif($view == 'participants')
                    {{-- SELECT PARTICIPANT --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <button wire:click="backToCategories" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm border">
                                <i class="ti ti-arrow-left fs-5"></i>
                            </button>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToCategories">Kategori</a></li>
                                    <li class="breadcrumb-item active fw-bolder" aria-current="page">{{ $selectedCategory->full_name }}</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="bg-white p-3 rounded-4 shadow-sm border mb-4">
                            <div class="input-group border-0 shadow-none">
                                <span class="input-group-text bg-transparent border-0 pe-1"><i class="ti ti-search text-secondary"></i></span>
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-0 shadow-none" placeholder="Cari nama sekolah...">
                            </div>
                        </div>
                        <div class="text-center mb-4">
                            <span class="badge bg-light border text-dark px-3 py-2 rounded-pill mb-2">Langkah 2</span>
                            <h4 class="fw-bolder">Pilih Peserta</h4>
                            <span class="badge bg-light border text-dark px-3 py-2 rounded-pill fw-bold">{{ $participants->count() }} Ditemukan</span>
                        </div>
                        <div class="row g-3">
                            @forelse($participants as $reg)
                                <div class="col-md-6">
                                    <div wire:click="selectParticipant({{ $reg->id }})"
                                         class="card border cursor-pointer transition-all hover-shadow {{ $selectedRegistrationId == $reg->id ? 'border-primary bg-primary bg-opacity-5 shadow-sm' : '' }} rounded-4 h-100 mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    @if($reg->logo_sekolah)
                                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="56" height="56" style="object-fit:cover;" alt="">
                                                    @else
                                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:56px;height:56px;">
                                                            <i class="ti ti-school fs-6"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <h6 class="fw-bolder mb-0 fs-4 text-truncate">{{ $reg->nama_sekolah }}</h6>
                                                    <p class="text-secondary mb-0 fs-2 text-truncate">Pelatih: {{ $reg->nama_pelatih }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 py-5 text-center">
                                    <p class="text-secondary fs-4">Tidak ada kontingen.</p>
                                    <button wire:click="$set('search', '')" class="btn btn-link text-primary p-0">Hapus Pencarian</button>
                                </div>
                            @endforelse
                        </div>
                    </div>

                @elseif($view == 'scoring')
                    {{-- SCORING FORM (admin-style table) --}}
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <button wire:click="backToParticipants" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm border">
                                <i class="ti ti-arrow-left fs-5"></i>
                            </button>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToCategories">Kategori</a></li>
                                    <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToParticipants">{{ $selectedCategory->full_name ?? 'Peserta' }}</a></li>
                                    <li class="breadcrumb-item active fw-bolder" aria-current="page">Input Nilai</li>
                                </ol>
                            </nav>
                        </div>

                        <div class="row">
                            <div class="col-lg-8">
                                {{-- Participant Info --}}
                                <div class="card border shadow-sm rounded-4 mb-4 overflow-hidden">
                                    <div class="card-body p-4 bg-primary text-white">
                                        <div class="d-flex align-items-center gap-3">
                                            @if($selectedRegistration->logo_sekolah)
                                                <img src="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" class="rounded-circle border border-white border-2" width="64" height="64" style="object-fit:cover;" alt="">
                                            @else
                                                <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                                                    <i class="ti ti-school text-white fs-6"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="badge bg-secondary text-white mb-1 px-2 py-1 rounded-pill fw-bold">Langkah 3: Penilaian</span>
                                                <h4 class="text-white fw-bolder mb-0">{{ $selectedRegistration->nama_sekolah }}</h4>
                                                <p class="text-white opacity-75 mb-0">{{ $selectedRegistration->nama_pelatih }} &bull; {{ $selectedRegistration->competitionCategory->full_name ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($assessmentCategories->isEmpty())
                                    <div class="text-center py-5">
                                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                                            <i class="ti ti-clipboard-off text-warning fs-10"></i>
                                        </div>
                                        <h5 class="fw-bolder">Format Penilaian Belum Tersedia</h5>
                                        <p class="text-secondary">Penyelenggara belum mengatur format penilaian.</p>
                                    </div>
                                @else
                                    @php $grandTotal = 0; @endphp
                                    @foreach($assessmentCategories as $assessmentCat)
                                        @php
                                            $catTotal = 0;
                                            foreach ($assessmentCat->subCategories as $sub) {
                                                foreach ($sub->criterias as $crit) {
                                                    $val = $scores[$crit->id] ?? null;
                                                    if ($val !== '' && $val !== null) $catTotal += (int) $val;
                                                }
                                            }
                                            $grandTotal += $catTotal;
                                        @endphp
                                        <div class="card border shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-category me-2"></i>{{ $assessmentCat->name }}</h5>
                                                <span class="badge bg-white text-primary fw-semibold">Subtotal: {{ $catTotal }}</span>
                                            </div>
                                            <div class="card-body p-4">
                                                @foreach($assessmentCat->subCategories as $subCat)
                                                    <div class="mb-4 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}">
                                                        <h6 class="fw-semibold text-secondary mb-3"><i class="ti ti-subtask me-1"></i> {{ $subCat->name }}</h6>
                                                        <div class="table-responsive">
                                                            <table class="table align-middle mb-0">
                                                                @php
                                                                    $hdrGroups = []; $labelsFound = false;
                                                                    foreach($subCat->criterias as $c) {
                                                                        foreach($c->score_options as $o) {
                                                                            $sv = is_array($o) ? $o['score'] : $o;
                                                                            $lb = is_array($o) ? ($o['label'] ?? null) : null;
                                                                            if ($lb) $labelsFound = true;
                                                                            $key = $lb ?: $sv;
                                                                            if (!isset($hdrGroups[$key])) $hdrGroups[$key] = ['label' => $lb ?: $sv, 'count' => 0];
                                                                            $hdrGroups[$key]['count']++;
                                                                        }
                                                                    }
                                                                    $totalOpts = array_sum(array_column($hdrGroups, 'count'));
                                                                @endphp
                                                                @if($labelsFound && $totalOpts > 0)
                                                                <thead>
                                                                    <tr class="table-light">
                                                                        <th class="fw-semibold border-bottom-0" width="20%">Kriteria</th>
                                                                        @foreach($hdrGroups as $g)
                                                                            <th class="text-center border-bottom-0 px-1" colspan="{{ $g['count'] }}" width="{{ floor(80 / $totalOpts * $g['count']) }}%">
                                                                                <span class="badge bg-secondary bg-opacity-25 text-dark fw-semibold fs-2 d-inline-block w-100">{{ $g['label'] }}</span>
                                                                            </th>
                                                                        @endforeach
                                                                    </tr>
                                                                </thead>
                                                                @endif
                                                                <tbody>
                                                                    @foreach($subCat->criterias as $criteria)
                                                                        @php
                                                                            $critGrps = [];
                                                                            foreach($criteria->score_options as $o) {
                                                                                $sv = is_array($o) ? $o['score'] : $o;
                                                                                $lb = is_array($o) ? ($o['label'] ?? null) : null;
                                                                                $key = $lb ?: $sv;
                                                                                $critGrps[$key][] = $sv;
                                                                            }
                                                                        @endphp
                                                                        <tr class="{{ isset($scores[$criteria->id]) && $scores[$criteria->id] !== '' && $scores[$criteria->id] !== null ? 'table-success' : '' }}">
                                                                            <td class="fw-semibold">{{ $criteria->name }}</td>
                                                                            @if($labelsFound)
                                                                                @foreach($hdrGroups as $gKey => $g)
                                                                                    <td class="text-center px-1" colspan="{{ $g['count'] }}" style="white-space:nowrap;">
                                                                                        @if(isset($critGrps[$gKey]))
                                                                                            @foreach($critGrps[$gKey] as $sv)
                                                                                                @php $selected = isset($scores[$criteria->id]) && $scores[$criteria->id] == $sv; @endphp
                                                                                                <button type="button" wire:click="$set('scores.{{ $criteria->id }}', '{{ $sv }}')"
                                                                                                    class="btn btn-sm {{ $selected ? 'btn-primary' : 'btn-outline-primary' }} px-1 fw-semibold py-1" style="min-width:32px;">{{ $sv }}</button>
                                                                                            @endforeach
                                                                                        @endif
                                                                                    </td>
                                                                                @endforeach
                                                                            @else
                                                                                <td class="text-end">
                                                                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                                                        @foreach($criteria->score_options as $option)
                                                                                            @php $sv = is_array($option) ? $option['score'] : $option; $selected = isset($scores[$criteria->id]) && $scores[$criteria->id] == $sv; @endphp
                                                                                            <button type="button" wire:click="$set('scores.{{ $criteria->id }}', '{{ $sv }}')"
                                                                                                class="btn btn-sm {{ $selected ? 'btn-primary' : 'btn-outline-primary' }} px-2 fw-semibold py-1" style="min-width:38px;">{{ $sv }}</button>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </td>
                                                                            @endif
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- Grand Total Card --}}
                                    <div class="card border shadow-sm rounded-4 mb-4">
                                        <div class="card-header bg-secondary text-white">
                                            <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-sum me-2"></i>Total Keseluruhan</h5>
                                        </div>
                                        <div class="card-body p-4 text-center">
                                            <h2 class="fw-bolder text-primary mb-0">{{ $grandTotal }}</h2>
                                            <p class="text-secondary small mb-0">Akumulasi semua kategori nilai</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Right: Save Panel --}}
                            <div class="col-lg-4 mt-4 mt-lg-0">
                                <div class="card border shadow-sm rounded-4 sticky-top" style="top: 80px; z-index: 10;">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0 text-white fw-semibold">Simpan Penilaian</h5>
                                    </div>
                                    <div class="card-body p-4">
                                        <p class="text-secondary small mb-4">Pastikan semua nilai sudah diisi.</p>

                                        @if($saveStatus === 'saved')
                                            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success d-flex align-items-center gap-2 mb-4">
                                                <i class="ti ti-check-circle fs-5"></i> Nilai berhasil disimpan!
                                            </div>
                                        @endif

                                        @php
                                            $totalCriteria = $assessmentCategories->sum(fn($cat) => $cat->subCategories->sum(fn($sub) => $sub->criterias->count()));
                                            $filledCount = collect($scores)->filter(fn($v) => $v !== '' && $v !== null)->count();
                                            $progress = $totalCriteria > 0 ? round(($filledCount / $totalCriteria) * 100) : 0;
                                        @endphp
                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="fw-semibold text-secondary small">Progress</span>
                                                <span class="fw-bolder text-primary">{{ $filledCount }}/{{ $totalCriteria }}</span>
                                            </div>
                                            <div class="progress rounded-pill" style="height: 8px;">
                                                <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: {{ $progress }}%"></div>
                                            </div>
                                        </div>

                                        @if($totalCriteria > 0)
                                            <button wire:click="saveScores"
                                                    class="btn btn-primary w-100 py-3 rounded-pill fw-bolder shadow-sm mb-3"
                                                    wire:loading.attr="disabled">
                                                <span wire:loading.remove><i class="ti ti-device-floppy me-2"></i> Simpan Semua Nilai</span>
                                                <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...</span>
                                            </button>
                                        @endif

                                        <button wire:click="backToParticipants"
                                                class="btn btn-outline-secondary w-100 py-2 rounded-pill fw-semibold">
                                            <i class="ti ti-arrow-left me-1"></i> Kembali
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .transition-all { transition: all 0.3s ease; }
    .hover-shadow:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important; }
</style>
