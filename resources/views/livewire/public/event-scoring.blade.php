<div>
    {{-- ========== GATE: Split-Screen Password Access ========== --}}
    @if(!$authenticated)
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
            <div class="position-relative z-index-5">
                <div class="row g-0">
                    {{-- Left Side: Branding & Illustration --}}
                    <div class="col-xl-7 col-xxl-8">
                        <a href="{{ url('/') }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
                            <img src="{{ asset('templates/assets/images/logos/dark-logo.svg') }}" class="dark-logo" alt="Logo-Dark" />
                            <img src="{{ asset('templates/assets/images/logos/light-logo.svg') }}" class="light-logo" alt="Logo-light" />
                        </a>
                        <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
                            <div class="text-center px-5">
                                <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="Scoring Access" class="img-fluid mb-4" width="500">
                                <h2 class="text-white fw-bolder fs-10 mb-2">{{ $eventner->nama_event }}</h2>
                                <p class="text-white text-opacity-75 fs-4">Panel Penilaian Panitia &mdash; Masukkan kode akses untuk melanjutkan</p>
                            </div>
                        </div>
                    </div>

                    {{-- Right Side: Access Form --}}
                    <div class="col-xl-5 col-xxl-4">
                        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
                            <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
                                {{-- Mobile Logo --}}
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
                                        <input
                                            type="password"
                                            wire:model="scoringCodeInput"
                                            class="form-control"
                                            id="scoringCodeInput"
                                            placeholder="Masukkan kode akses..."
                                            autocomplete="off"
                                            autofocus
                                        >
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">
                                        <span wire:loading.remove wire:target="authenticate">
                                            <i class="ti ti-login me-2"></i> Masuk Panel
                                        </span>
                                        <span wire:loading wire:target="authenticate">
                                            <span class="spinner-border spinner-border-sm me-2"></span> Memverifikasi...
                                        </span>
                                    </button>

                                    <div class="d-flex align-items-center justify-content-center">
                                        <p class="fs-4 mb-0 fw-medium">Bukan panitia?</p>
                                        <a class="text-primary fw-medium ms-2" href="{{ route('event.detail', $eventner->slug) }}">
                                            Kembali ke Event
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- ========== AUTHENTICATED: Scoring Workflow ========== --}}
    @else
        {{-- Header Banner --}}
        <section class="bg-primary-subtle pt-7 pb-5">
            <div class="custom-container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <span class="badge bg-success text-white mb-3 px-3 py-2 rounded-pill fw-bolder">
                            <i class="ti ti-check me-1"></i> Terautentikasi
                        </span>
                        <h1 class="text-dark fw-bolder fs-10 mb-2">Panel Input Nilai</h1>
                        <p class="fs-5 text-muted mb-4">
                            Masukkan nilai peserta <strong>{{ $eventner->nama_event }}</strong> berdasarkan format penilaian yang telah ditentukan.
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('event.detail', $eventner->slug) }}" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="ti ti-info-circle me-1"></i> Info Event
                            </a>
                            <a href="{{ route('event.participant', $eventner->slug) }}" class="btn btn-outline-secondary rounded-pill px-4">
                                <i class="ti ti-users me-1"></i> Daftar Peserta
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5">
            <div class="custom-container">
                @if (session()->has('scoring_error'))
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 mb-4" role="alert">
                        {{ session('scoring_error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($view == 'categories')
                    {{-- ========== STEP 2: SELECT CATEGORY ========== --}}
                    <div class="mb-5">
                        <div class="text-center mb-5">
                            <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill mb-2">Langkah 1</span>
                            <h3 class="fw-bolder text-dark">Pilih Kategori Lomba</h3>
                            <p class="text-muted">Pilih kategori untuk melihat daftar peserta yang akan dinilai</p>
                        </div>
                        <div class="row g-4">
                            @foreach($categories as $cat)
                                <div class="col-md-6 col-lg-4">
                                    <div wire:click="selectCategory({{ $cat->id }})"
                                         class="card border-0 shadow-sm transition-all hover-shadow cursor-pointer rounded-4 h-100 mb-0"
                                         style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);">
                                        <div class="card-body p-4 text-center">
                                            <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                                                <i class="ti ti-medal fs-8"></i>
                                            </div>
                                            <h5 class="fw-bolder text-dark mb-1">{{ $cat->name }}</h5>
                                            <p class="text-muted mb-0 fs-3">{{ $cat->registrations_count }} Peserta Terdaftar</p>
                                            <div class="mt-3">
                                                <span class="btn btn-sm btn-success rounded-pill px-3">
                                                    <i class="ti ti-edit me-1"></i> Input Nilai
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                @elseif($view == 'participants')
                    {{-- ========== STEP 3: SELECT PARTICIPANT ========== --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <button wire:click="backToCategories" class="btn btn-sm btn-light rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm">
                                <i class="ti ti-arrow-left fs-5"></i>
                            </button>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToCategories">Kategori</a></li>
                                    <li class="breadcrumb-item active text-primary fw-bolder" aria-current="page">{{ $selectedCategory->name }}</li>
                                </ol>
                            </nav>
                        </div>

                        <div class="bg-white p-3 rounded-4 shadow-sm border mb-4">
                            <div class="input-group input-group-lg border-0 shadow-none">
                                <span class="input-group-text bg-transparent border-0 pe-1">
                                    <i class="ti ti-search text-muted"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-0 shadow-none fs-4" placeholder="Cari nama sekolah atau kontingen...">
                            </div>
                        </div>

                        <div class="text-center mb-4">
                            <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill mb-2">Langkah 2</span>
                            <h4 class="fw-bolder text-dark">Pilih Peserta</h4>
                            <span class="badge bg-light-success text-success px-3 py-2 rounded-pill fw-bold">{{ $participants->count() }} Ditemukan</span>
                        </div>

                        <div class="row g-3">
                            @forelse($participants as $reg)
                                <div class="col-md-6">
                                    <div wire:click="selectParticipant({{ $reg->id }})"
                                         class="card border-2 cursor-pointer transition-all hover-shadow {{ $selectedRegistrationId == $reg->id ? 'border-success bg-light-success shadow-sm' : 'border-light shadow-none' }} rounded-4 h-100 mb-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    @if($reg->logo_sekolah)
                                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="56" height="56" style="object-fit:cover;" alt="">
                                                    @else
                                                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width:56px;height:56px;">
                                                            <i class="ti ti-school fs-6"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <h6 class="fw-bolder mb-0 fs-4 text-truncate">{{ $reg->nama_sekolah }}</h6>
                                                    <p class="text-muted mb-0 fs-2 text-truncate">Pelatih: {{ $reg->nama_pelatih }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 py-5 text-center">
                                    <img src="{{ asset('templates/assets/images/backgrounds/search-error.svg') }}" width="200" class="mb-3" alt="">
                                    <p class="text-muted fs-4">Tidak ada kontingen yang sesuai.</p>
                                    <button wire:click="$set('search', '')" class="btn btn-link text-primary p-0">Hapus Pencarian</button>
                                </div>
                            @endforelse
                        </div>
                    </div>

                @elseif($view == 'scoring')
                    {{-- ========== STEP 4: SCORING FORM ========== --}}
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <button wire:click="backToParticipants" class="btn btn-sm btn-light rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm">
                                <i class="ti ti-arrow-left fs-5"></i>
                            </button>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToCategories">Kategori</a></li>
                                    <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToParticipants">{{ $selectedCategory->name ?? 'Peserta' }}</a></li>
                                    <li class="breadcrumb-item active text-primary fw-bolder" aria-current="page">Input Nilai</li>
                                </ol>
                            </nav>
                        </div>

                        <div class="row">
                            {{-- Left: Scoring Form --}}
                            <div class="col-lg-8">
                                {{-- Participant Info Card --}}
                                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                                    <div class="card-body p-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                        <div class="d-flex align-items-center gap-3">
                                            @if($selectedRegistration->logo_sekolah)
                                                <img src="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" class="rounded-circle border border-white border-2" width="64" height="64" style="object-fit:cover;" alt="">
                                            @else
                                                <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                                                    <i class="ti ti-school text-white fs-6"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <span class="badge bg-warning text-dark mb-1 px-2 py-1 rounded-pill fw-bold">Langkah 3: Penilaian</span>
                                                <h4 class="text-white fw-bolder mb-0">{{ $selectedRegistration->nama_sekolah }}</h4>
                                                <p class="text-white text-opacity-75 mb-0">Pelatih: {{ $selectedRegistration->nama_pelatih }} &bull; Kategori: {{ $selectedRegistration->competitionCategory->name ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($assessmentCategories->isEmpty())
                                    <div class="text-center py-5">
                                        <div class="bg-warning-subtle rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                                            <i class="ti ti-clipboard-off text-warning fs-10"></i>
                                        </div>
                                        <h5 class="fw-bolder">Format Penilaian Belum Tersedia</h5>
                                        <p class="text-muted">Penyelenggara belum mengatur format penilaian untuk event ini.</p>
                                    </div>
                                @else
                                    {{-- Render Assessment Categories & Criteria --}}
                                    @foreach($assessmentCategories as $assessmentCat)
                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-transparent border-bottom px-4 py-3" style="background: linear-gradient(to right, #f8faff, transparent);">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-primary rounded-2 p-2">
                                                        <i class="ti ti-category text-white fs-5"></i>
                                                    </div>
                                                    <h5 class="fw-bolder mb-0 text-dark">{{ $assessmentCat->name }}</h5>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                @foreach($assessmentCat->subCategories as $subCat)
                                                    <div class="mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                        <h6 class="fw-bold text-muted mb-3">
                                                            <i class="ti ti-subtask me-1"></i> {{ $subCat->name }}
                                                        </h6>
                                                        <div class="row g-3">
                                                            @foreach($subCat->criterias as $criteria)
                                                                <div class="col-12">
                                                                    <div class="d-flex align-items-center justify-content-between gap-2 py-2 px-3 rounded-3 {{ isset($scores[$criteria->id]) && $scores[$criteria->id] !== '' && $scores[$criteria->id] !== null ? 'bg-success-subtle' : 'bg-light' }}">
                                                                        <span class="fw-semibold text-dark fs-3 flex-shrink-0">
                                                                            {{ $criteria->name }}
                                                                        </span>
                                                                        <div class="d-flex gap-1 flex-shrink-0">
                                                                            @foreach($criteria->score_options as $option)
                                                                                <button type="button"
                                                                                    wire:click="$set('scores.{{ $criteria->id }}', '{{ $option }}')"
                                                                                    class="btn btn-sm rounded-pill px-3 fw-bold {{ isset($scores[$criteria->id]) && $scores[$criteria->id] == $option ? 'btn-primary shadow-sm' : 'btn-outline-primary' }}">
                                                                                    {{ $option }}
                                                                                </button>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            {{-- Right: Save Panel --}}
                            <div class="col-lg-4 mt-4 mt-lg-0">
                                <div class="card border-0 shadow-lg rounded-4 sticky-top" style="top: 100px; z-index: 10;">
                                    <div class="card-body p-4">
                                        <h4 class="fw-bolder text-dark mb-1">Simpan Penilaian</h4>
                                        <p class="text-muted small mb-4">Pastikan semua nilai sudah diisi dengan benar</p>

                                        @if($saveStatus === 'saved')
                                            <div class="alert alert-success rounded-3 mb-4 d-flex align-items-center gap-2">
                                                <i class="ti ti-check-circle fs-5"></i>
                                                Nilai berhasil disimpan!
                                            </div>
                                        @endif

                                        {{-- Progress --}}
                                        <div class="mb-4">
                                            @php
                                                $totalCriteria = $assessmentCategories->sum(function($cat) {
                                                    return $cat->subCategories->sum(function($sub) { return $sub->criterias->count(); });
                                                });
                                                $filledCount = collect($scores)->filter(fn($v) => $v !== '' && $v !== null)->count();
                                                $progress = $totalCriteria > 0 ? round(($filledCount / $totalCriteria) * 100) : 0;
                                            @endphp
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="fw-semibold text-muted small">Progress</span>
                                                <span class="fw-bolder text-primary">{{ $filledCount }}/{{ $totalCriteria }}</span>
                                            </div>
                                            <div class="progress rounded-pill" style="height: 8px;">
                                                <div class="progress-bar bg-success rounded-pill transition-all"
                                                     role="progressbar"
                                                     style="width: {{ $progress }}%"
                                                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        @if($totalCriteria > 0)
                                            <button wire:click="saveScores"
                                                    class="btn btn-success w-100 py-3 rounded-pill fw-bolder fs-4 shadow-sm mb-3"
                                                    wire:loading.attr="disabled">
                                                <span wire:loading.remove>
                                                    <i class="ti ti-device-floppy me-2"></i> Simpan Semua Nilai
                                                </span>
                                                <span wire:loading>
                                                    <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                                                </span>
                                            </button>
                                        @endif

                                        <button wire:click="backToParticipants"
                                                class="btn btn-outline-secondary w-100 py-2 rounded-pill fw-semibold">
                                            <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar Peserta
                                        </button>

                                        <div class="mt-4 p-3 bg-light rounded-3">
                                            <p class="text-muted small mb-2"><i class="ti ti-info-circle me-1"></i> Tips</p>
                                            <ul class="text-muted small mb-0 ps-3">
                                                <li>Klik angka nilai untuk menilai tiap kriteria</li>
                                                <li>Nilai yang terpilih akan berwarna biru</li>
                                                <li>Anda dapat kembali mengedit nilai kapan saja</li>
                                            </ul>
                                        </div>
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
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .bg-light-primary { background-color: #ecf2ff; }
    .bg-light-success { background-color: #e6f9ed; }
    .bg-light-warning { background-color: #fff8e1; }
</style>
