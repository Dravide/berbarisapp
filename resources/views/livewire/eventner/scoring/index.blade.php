<div>
    {{-- Page Header (Template Standard) --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Input Nilai</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">{{ $eventner->nama_event }}</li>
                            <li class="breadcrumb-item active" aria-current="page">Input Nilai</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Flash error dari action (finalize massal dll) dirender sebagai
         toast SweetAlert via listener di bawah --}}

    {{-- Mode Simulasi (Sandbox) — tombol kecil, pengaturan di modal --}}
    <div class="d-flex justify-content-end mb-3">
        <button type="button"
                class="btn btn-sm {{ $simulateMode ? 'btn-warning fw-bold' : 'btn-outline-warning fw-semibold' }}"
                data-bs-toggle="modal" data-bs-target="#simulateModal">
            <i class="ti ti-flask me-1"></i> Mode Simulasi
            @if($simulateMode)
                <span class="badge bg-dark text-warning ms-1">AKTIF</span>
            @endif
        </button>
    </div>

    @if($simulateMode)
        <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-4">
            <i class="ti ti-alert-triangle fs-5"></i>
            <span class="small fw-semibold">Simulasi aktif: nilai yang Anda klik hanya untuk latihan dan tidak akan direkam di mana pun.</span>
        </div>
    @endif

    @php
        // Babak final tanpa seri: satu pool se-tingkat, dan rubriknya pun tak
        // lagi ditentukan seri (lihat AssessmentCategory::forEntry()). Badge
        // seri — juga "Tanpa Seri" — di sana cuma menyiratkan pembagian yang
        // tak berlaku, jadi keduanya disembunyikan. Dihitung sekali di sini,
        // bukan di dalam loop.
        $babakFinal = (bool) $selectedRound?->isFinal();
    @endphp
    @if($view == 'categories')
        {{-- ========== STEP 1: SELECT CATEGORY ========== --}}
        <div class="card w-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-category me-2"></i>Pilih Kategori Lomba</h5>
                <a href="{{ route('eventner.scoring.csv') }}" class="btn btn-sm btn-light" target="_blank">
                    <i class="ti ti-file-type-csv text-success me-1"></i> Rekap Semua (CSV)
                </a>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Pilih kategori untuk melihat daftar peserta yang akan dinilai.</p>
                <div class="row g-3">
                    @foreach($categories as $cat)
                        <div class="col-md-6 col-lg-4">
                            <div wire:click="selectCategory({{ $cat->id }})"
                                 class="card mb-0 border border-2 cursor-pointer hover-shadow"
                                 style="cursor:pointer;">
                                <div class="card-body p-4 text-center">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                                        <i class="ti ti-medal fs-7"></i>
                                    </div>
                                    <h5 class="fw-semibold text-dark mb-1">{{ $cat->full_name }}</h5>
                                    <p class="text-muted mb-3 fs-2">{{ $cat->registrations_count }} Peserta</p>
                                    <span class="btn btn-sm btn-primary">
                                        <i class="ti ti-edit me-1"></i> Input Nilai
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @elseif($view == 'groups')
        {{-- ========== STEP 1b: PILIH GRUP / BABAK ==========
             Muncul hanya untuk tingkat yang punya grup atau babak final.
             Tingkat polos melewati layar ini sepenuhnya. --}}
        <div class="card w-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <button wire:click="backToCategories" class="btn btn-sm btn-light">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <h5 class="mb-0 text-white fw-semibold">{{ $selectedCategory->full_name }}</h5>
                </div>
                <span class="badge bg-white text-primary fw-semibold">
                    {{ $groups->count() + ($rounds->contains(fn ($r) => $r->isFinal()) ? 1 : 0) + ($ungroupedCount > 0 ? 1 : 0) }} Pilihan
                </span>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">
                    Tingkat ini dibelah jadi beberapa pool penilaian.
                    Pilih satu untuk melihat daftar sekolahnya.
                </p>

                <div class="row g-3">
                    @foreach($groups as $grup)
                        <div class="col-md-6 col-lg-4">
                            <div wire:click="selectGroupScope('{{ $grup->id }}')"
                                 class="card mb-0 border border-2 hover-shadow h-100"
                                 style="cursor:pointer;">
                                <div class="card-body p-4 text-center">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                                        <i class="ti ti-users-group fs-7"></i>
                                    </div>
                                    <h5 class="fw-semibold text-dark mb-1">{{ $grup->name }}</h5>
                                    <p class="text-muted mb-2 fs-2">{{ $groupCounts[$grup->id] ?? 0 }} Peserta</p>
                                    {{-- Grup dan seri dua sumbu bebas, jadi satu grup boleh
                                         dihuni lebih dari satu seri. Tanpa pecahan ini operator
                                         tak tahu lembar nilai mana yang menunggu di dalamnya. --}}
                                    @php $seriGrup = $groupSeriesCounts[$grup->id] ?? []; @endphp
                                    @if($seriGrup !== [])
                                        <div class="d-flex flex-wrap justify-content-center gap-1 mb-3">
                                            @foreach($seriGrup as $s)
                                                <span class="badge {{ $s['tanpa_seri'] ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary' }}">
                                                    @if(! $s['tanpa_seri'])<i class="ti ti-flag-2 me-1"></i>@endif{{ $s['nama'] }} {{ $s['jumlah'] }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    {{-- Juri yang bertugas di grup ini. Grup tanpa juri
                                         ditandai, bukan dibiarkan tampak sama dengan yang
                                         sudah bertugas — pesertanya akan lolos tanpa penilai. --}}
                                    @php $juriGrup = $groupJudgeNames['group:' . $grup->id] ?? []; @endphp
                                    @if($juriGrup !== [])
                                        <div class="mb-3">
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ti ti-user-check me-1"></i>{{ implode(', ', $juriGrup) }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="mb-3">
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="ti ti-alert-triangle me-1"></i>Belum ada juri
                                            </span>
                                        </div>
                                    @endif
                                    @if($rounds->contains(fn ($r) => ! $r->isFinal()))
                                        <span class="badge bg-secondary-subtle text-secondary mb-2">Babak Penyisihan</span>
                                    @endif
                                    <div>
                                        <span class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit me-1"></i> Input Nilai
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($ungroupedCount > 0)
                        <div class="col-md-6 col-lg-4">
                            <div wire:click="selectGroupScope('ungrouped')"
                                 class="card mb-0 border border-2 hover-shadow h-100"
                                 style="cursor:pointer;">
                                <div class="card-body p-4 text-center">
                                    <div class="bg-warning-subtle text-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                                        <i class="ti ti-user-question fs-7"></i>
                                    </div>
                                    <h5 class="fw-semibold text-dark mb-1">Belum Bergrup</h5>
                                    <p class="text-muted mb-3 fs-2">{{ $ungroupedCount }} Peserta</p>
                                    <div>
                                        <span class="btn btn-sm btn-warning">
                                            <i class="ti ti-edit me-1"></i> Input Nilai
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($rounds->contains(fn ($r) => $r->isFinal()))
                        <div class="col-md-6 col-lg-4">
                            <div wire:click="selectGroupScope('final')"
                                 class="card mb-0 border border-2 hover-shadow h-100"
                                 style="cursor:pointer;">
                                <div class="card-body p-4 text-center">
                                    <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                                        <i class="ti ti-flag fs-7"></i>
                                    </div>
                                    <h5 class="fw-semibold text-dark mb-1">Babak Final</h5>
                                    <p class="text-muted mb-3 fs-2">{{ $finalistCount }} Finalis</p>
                                    <div>
                                        <span class="btn btn-sm btn-success">
                                            <i class="ti ti-edit me-1"></i> Input Nilai
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($groups->isNotEmpty())
                        <div class="col-md-6 col-lg-4">
                            <div wire:click="selectGroupScope('all')"
                                 class="card mb-0 border border-2 hover-shadow h-100"
                                 style="cursor:pointer;">
                                <div class="card-body p-4 text-center">
                                    <div class="bg-secondary-subtle text-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                                        <i class="ti ti-list fs-7"></i>
                                    </div>
                                    <h5 class="fw-semibold text-dark mb-1">Semua Grup</h5>
                                    <p class="text-muted mb-3 fs-2">{{ $totalCount }} Peserta</p>
                                    <div>
                                        <span class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-list me-1"></i> Lihat Semua
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    @elseif($view == 'participants')
        {{-- ========== STEP 2: SELECT PARTICIPANT ========== --}}
        <div class="card w-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <button wire:click="backFromParticipants" class="btn btn-sm btn-light">
                        <i class="ti ti-arrow-left"></i>
                    </button>
                    <h5 class="mb-0 text-white fw-semibold">{{ $selectedCategory->full_name }} — Pilih Peserta</h5>
                    @php
                        $grupAktif = $selectedGroupId ? ($groups ?? collect())->firstWhere('id', $selectedGroupId) : null;
                        $babakAktif = $selectedRound ?? null;
                    @endphp
                    @if($grupAktif)
                        <span class="badge bg-white text-primary fw-semibold"><i class="ti ti-users-group me-1"></i>{{ $grupAktif->name }}</span>
                    @elseif($ungroupedOnly)
                        <span class="badge bg-warning text-dark fw-semibold"><i class="ti ti-user-question me-1"></i>Belum Bergrup</span>
                    @elseif($babakAktif && $babakAktif->isFinal())
                        <span class="badge bg-success fw-semibold"><i class="ti ti-flag me-1"></i>{{ $babakAktif->name }}</span>
                    @elseif($groups->isNotEmpty())
                        <span class="badge bg-white text-primary fw-semibold"><i class="ti ti-list me-1"></i>Semua Grup</span>
                    @endif
                    {{-- Babak yang berlaku untuk semua sekolah ditulis apa adanya:
                         satu tingkat punya babak yang sama untuk seluruh peserta,
                         jadi ini bukan pilihan, hanya penanda rubrik mana yang
                         sedang dimuat form input. --}}
                    @if($babakAktif && ! $babakAktif->isFinal())
                        <span class="badge bg-light text-dark fw-semibold">{{ $babakAktif->name }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eventner.scoring.csv', ['category_id' => $selectedCategoryId]) }}" class="btn btn-sm btn-light" target="_blank">
                        <i class="ti ti-file-type-csv text-success me-1"></i> Download CSV
                    </a>
                    <button type="button"
                            class="btn btn-sm btn-success"
                            wire:click="finalizeAllForCategory"
                            wire:loading.attr="disabled"
                            wire:confirm="Finalisasi semua nilai pada kategori ini? Semua nilai tersimpan seluruh peserta kategori {{ $selectedCategory->full_name }} akan dikunci permanen."
                            @if($simulateMode) disabled title="Tidak tersedia di mode simulasi" @endif>
                        <i class="ti ti-lock me-1"></i> Finalisasi Semua
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                {{-- Search --}}
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Cari nama sekolah atau kontingen...">
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0">Daftar Peserta</h6>
                    <span class="badge bg-primary">{{ $participants->count() }} Ditemukan</span>
                </div>

                <div class="row g-3">
                    @forelse($participants as $reg)
                        <div class="col-md-6">
                            <div wire:click="selectParticipant({{ $reg->id }})"
                                 class="card mb-0 border border-2 cursor-pointer {{ $selectedRegistrationId == $reg->id ? 'border-primary bg-primary-subtle' : '' }}"
                                 style="cursor:pointer;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center gap-3">
                                        {{-- Nomor undian = urutan tampil. Daftar diurutkan
                                             dengan angka ini, jadi juri bisa mencocokkan
                                             layar dengan panggilan di lapangan.

                                             bg-dark + text-white, BUKAN bg-dark-subtle +
                                             text-dark: tema ini menyetel
                                             --bs-dark-bg-subtle ke #2a3547 — warna gelap
                                             penuh, bukan tint seperti token -bg-subtle
                                             lainnya — sementara .text-dark di sini juga
                                             gelap. Hasilnya angka gelap di atas gelap, dan
                                             nomor undiannya tak terbaca. --}}
                                        @php $nomorUndian = $reg->nomorUndian($selectedRound); @endphp
                                        @if($nomorUndian)
                                            <span class="badge bg-dark text-white flex-shrink-0" style="min-width:2rem;">{{ $nomorUndian }}</span>
                                        @endif
                                        @if($reg->logo_sekolah)
                                            <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="44" height="44" style="object-fit:cover;" alt="">
                                        @else
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width:44px;height:44px;">
                                                <i class="ti ti-school fs-5"></i>
                                            </div>
                                        @endif
                                        <div class="overflow-hidden">
                                            <h6 class="fw-semibold mb-0 text-truncate">{{ $reg->display_name }}</h6>
                                            <p class="text-muted mb-0 fs-2 text-truncate">Pelatih: {{ $reg->nama_pelatih }}</p>
                                        @if(! $babakFinal && $reg->competitionSeries)
                                            <span class="badge bg-secondary-subtle text-secondary mt-1">{{ $reg->competitionSeries->name }}</span>
                                        @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <i class="ti ti-search-off fs-10 text-muted d-block mb-3"></i>
                            <p class="text-muted">Tidak ada kontingen yang sesuai.</p>
                            <button wire:click="$set('search', '')" class="btn btn-sm btn-outline-primary">Hapus Pencarian</button>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    @elseif($view == 'scoring')
        {{-- ========== STEP 3: SCORING FORM ========== --}}
        <div class="row">
            {{-- Left: Scoring Form --}}
            <div class="col-lg-8">
                {{-- Participant Info Card --}}
                <div class="card w-100 overflow-hidden mb-4">
                    <div class="card-body p-4 {{ $simulateMode ? 'bg-warning' : 'bg-primary' }} text-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <button wire:click="backToParticipants" class="btn btn-sm btn-light me-1">
                                    <i class="ti ti-arrow-left"></i>
                                </button>
                                @if($simulateMode)
                                    <span class="badge bg-dark text-warning fw-bold d-none d-md-inline-flex align-items-center gap-1 me-1">
                                        <i class="ti ti-flask"></i> SIMULASI
                                    </span>
                                @endif
                                @if($selectedRegistration->logo_sekolah)
                                    <img src="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" class="rounded-circle border border-white border-2" width="48" height="48" style="object-fit:cover;" alt="">
                                @else
                                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                        <i class="ti ti-school text-white fs-5"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="text-white fw-semibold mb-0">{{ $selectedRegistration->display_name }}</h5>
                                        {{-- Seri ditulis di sini: dua seri boleh memakai
                                             nama kategori rubrik yang sama persis, jadi tanpa
                                             penanda ini operator tak punya cara tahu lembar
                                             mana yang sedang terbuka.

                                             Babak final dikecualikan: di sana seri diabaikan
                                             seluruhnya (lihat AssessmentCategory::forEntry()),
                                             jadi menuliskan seri — atau "Tanpa Seri" — cuma
                                             menyiratkan lembar yang berbeda padahal sama. --}}
                                        @if(! $babakFinal)
                                            @if($selectedRegistration->competitionSeries)
                                                <span class="badge bg-white text-primary fw-semibold">
                                                    <i class="ti ti-flag-2 me-1"></i>{{ $selectedRegistration->competitionSeries->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark fw-semibold">Tanpa Seri</span>
                                            @endif
                                        @endif
                                    </div>
                                    <p class="text-white text-opacity-75 mb-0 fs-2">Pelatih: {{ $selectedRegistration->nama_pelatih }} &bull; {{ $selectedRegistration->competitionCategory->name ?? '-' }}</p>
                                </div>
                            </div>
                            {{-- Babak yang sedang dibuka ikut dikirim: halaman ini
                                 sudah tahu babaknya, dan tanpa itu lembar yang
                                 tercetak ditentukan tebakan controller — bukan
                                 apa yang sedang dilihat operator. --}}
                            <div class="d-flex align-items-center gap-2">
                                {{-- Peringatan nilai kosong persis di samping PDF:
                                     apa yang menghalangi finalisasi dan apa yang
                                     dipakai memutuskan juara berasal dari angka
                                     yang sama, jadi tempatnya di sebelah lembar
                                     yang mencetaknya.

                                     Hanya kabar belum lengkap yang ditulis. Bila
                                     semua terisi, tombolnya hilang sendiri —
                                     tak perlu pujian yang memakan tempat. --}}
                                @if($nilaiKosong['total'] > 0)
                                    <button type="button"
                                            class="btn btn-sm btn-warning fw-semibold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#nilaiKosongModal">
                                        <i class="ti ti-alert-triangle me-1"></i>
                                        {{ $nilaiKosong['total'] }} nilai masih kosong
                                    </button>
                                @endif

                                <a href="{{ route('eventner.scoring.pdf-participant', array_filter([
                                        'registration_id' => $selectedRegistration->id,
                                        'round_id' => $selectedRoundId,
                                   ])) }}"
                                   class="btn btn-sm btn-light" target="_blank">
                                    <i class="ti ti-file-type-pdf text-danger me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Judge Selector --}}
                @if(count($judges) > 0)
                    <div class="card w-100 mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0 text-white fw-semibold"><i class="ti ti-users me-2"></i>Pilih Juri</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach($judges as $judge)
                                    {{-- Label baris penugasan: juri grup mana pun menilai
                                         seluruh rubrik seri peserta, jadi yang membedakan juri
                                         satu dari yang lain hanyalah dari mana ia ditugaskan. --}}
                                    @php $grupJuri = $judgeGroupLabels[$judge->id] ?? ''; @endphp
                                    <button type="button"
                                        wire:click="$set('selectedJudgeId', {{ $judge->id }})"
                                        class="btn {{ $selectedJudgeId == $judge->id ? 'btn-primary' : 'btn-outline-primary' }} px-3 py-2 text-start">
                                        <span class="fw-semibold d-block"><i class="ti ti-user me-1"></i>{{ $judge->name }}</span>
                                        @if($grupJuri !== '')
                                            <span class="d-block small {{ $selectedJudgeId == $judge->id ? 'text-white text-opacity-75' : 'text-muted' }}">
                                                <i class="ti ti-users-group me-1"></i>{{ $grupJuri }}
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Panel kosong tanpa penjelasan membuat tombol Simpan tampak
                         rusak. Dua sebabnya berbeda dan disebut apa adanya; keduanya
                         dikunci di satu layar, jadi tautannya sama. --}}
                    <div class="card w-100 mb-4 border-warning">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2">
                                <i class="ti ti-alert-triangle text-warning me-1"></i>Belum ada juri untuk peserta ini
                            </h6>
                            @if($barisPenugasan === null)
                                <p class="text-muted fs-2 mb-2">
                                    Tingkat <strong>{{ $selectedRegistration->competitionCategory->name ?? '-' }}</strong>
                                    belum punya baris penugasan sama sekali, jadi tak ada satu pun kolom
                                    untuk mencentang juri. Tetapkan dulu penugasannya untuk grup &mdash;
                                    atau untuk seluruh tingkat kalau memang tidak dibagi grup.
                                </p>
                            @else
                                <p class="text-muted fs-2 mb-2">
                                    Baris <strong>{{ $barisPenugasan }}</strong> sudah ada, tapi belum ada
                                    juri yang dicentang di dalamnya. Peserta ini masuk ke baris itu, jadi
                                    tanpa centang ia tak bisa dinilai siapa pun.
                                </p>
                            @endif
                            <a href="{{ route('eventner.competition-categories.index') }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-arrow-right me-1"></i>Buka Kategori Lomba
                            </a>
                        </div>
                    </div>
                @endif

                @if($assessmentCategories->isEmpty())
                    <div class="card w-100">
                        <div class="card-body text-center py-5">
                            <i class="ti ti-clipboard-off text-warning fs-10 d-block mb-3"></i>
                            <h5 class="fw-semibold">Format Penilaian Belum Tersedia</h5>
                            <p class="text-muted">Silakan atur format penilaian terlebih dahulu di menu <a href="{{ route('eventner.format-nilai.builder') }}">Format Penilaian</a>.</p>
                        </div>
                    </div>
                @else
                    @php $grandTotal = 0; @endphp
                    {{-- Render Assessment Categories & Criteria --}}
                    @foreach($assessmentCategories as $assessmentCat)
                        @php
                            $categoryTotal = 0;
                            foreach ($assessmentCat->subCategories as $sub) {
                                foreach ($sub->criterias as $crit) {
                                    $val = $scores[$crit->id] ?? null;
                                    if ($val !== '' && $val !== null) {
                                        // Dikalikan bobot — sama seperti rekap panitia
                                        // dan papan skor publik. Opsi nilai bisa
                                        // berpecahan ("8.2"), jadi dibaca lewat
                                        // ScoreOptions::value() — cast (int) dulu
                                        // membuang pecahannya diam-diam.
                                        $categoryTotal += \App\Support\ScoreOptions::value($val) * ($crit->weight ?? 1);
                                    }
                                }
                            }
                            $grandTotal += $categoryTotal;
                        @endphp
                        <div class="card w-100 mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-category me-2"></i>{{ $assessmentCat->name }}</h5>
                                <span class="badge bg-white text-primary fw-semibold">Subtotal: {{ \App\Support\ScoreOptions::format($categoryTotal) }}</span>
                            </div>
                            <div class="card-body p-4">
                                @foreach($assessmentCat->subCategories as $subCat)
                                    <div class="mb-4 {{ !$loop->last ? 'pb-3 border-bottom' : '' }}">
                                        <h6 class="fw-semibold text-muted mb-3">
                                            <i class="ti ti-subtask me-1"></i> {{ $subCat->name }}
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                @php
                                                    // Build unique ordered label groups for this sub-category
                                                    $headerGroups = [];
                                                    foreach($subCat->criterias as $c) {
                                                        foreach($c->score_options as $o) {
                                                            $sv = is_array($o) ? $o['score'] : $o;
                                                            $lb = is_array($o) ? ($o['label'] ?? null) : null;
                                                            $key = $lb ?: $sv;
                                                            if (!isset($headerGroups[$key])) {
                                                                $headerGroups[$key] = ['label' => $lb ?: $sv, 'count' => 0];
                                                            }
                                                            $headerGroups[$key]['count']++;
                                                        }
                                                    }
                                                    $totalOpts = array_sum(array_column($headerGroups, 'count'));
                                                @endphp
                                                <thead>
                                                    <tr class="table-light">
                                                        <th class="fw-semibold border-bottom-0" width="20%">Kriteria</th>
                                                        @foreach($headerGroups as $g)
                                                            <th class="text-center border-bottom-0 px-1" colspan="{{ $g['count'] }}" width="{{ floor(80 / $totalOpts * $g['count']) }}%">
                                                                <span class="badge bg-secondary bg-opacity-25 text-dark fw-semibold fs-2 d-inline-block w-100">{{ $g['label'] }}</span>
                                                            </th>
                                                        @endforeach
                                                        <th class="border-bottom-0" width="40px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($subCat->criterias as $criteria)
                                                        @php
                                                            // Group options by label for this criteria
                                                            $critGroups = [];
                                                            foreach($criteria->score_options as $o) {
                                                                $sv = is_array($o) ? $o['score'] : $o;
                                                                $lb = is_array($o) ? ($o['label'] ?? null) : null;
                                                                $key = $lb ?: $sv;
                                                                $critGroups[$key][] = ['score' => $sv, 'label' => $lb, 'key' => $key];
                                                            }
                                                        @endphp
                                                        @php $terisi = isset($scores[$criteria->id]) && $scores[$criteria->id] !== '' && $scores[$criteria->id] !== null; @endphp
                                                        <tr class="{{ $terisi ? 'table-success' : '' }}">
                                                            <td class="fw-semibold">{{ $criteria->name }}</td>
                                                            @foreach($headerGroups as $gKey => $g)
                                                                <td class="text-center px-1" colspan="{{ $g['count'] }}" style="white-space:nowrap;">
                                                                    @if(isset($critGroups[$gKey]))
                                                                        @foreach($critGroups[$gKey] as $opt)
                                                                            @php $selected = isset($scores[$criteria->id]) && $scores[$criteria->id] == $opt['score']; @endphp
                                                                            <button type="button"
                                                                                @if(!$isFinalized) wire:click="$set('scores.{{ $criteria->id }}', '{{ $opt['score'] }}')" @endif
                                                                                {{ $isFinalized ? 'disabled' : '' }}
                                                                                class="btn btn-sm {{ $selected ? 'btn-primary' : 'btn-outline-primary' }} px-1 fw-semibold py-1"
                                                                                style="min-width:32px;">
                                                                                <span>{{ $opt['score'] }}</span>
                                                                            </button>
                                                                        @endforeach
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                            {{-- Hapus hanya baris kriteria ini. Barisnya belum
                                                                 menyentuh DB sampai Simpan Penilaian, jadi tak
                                                                 perlu konfirmasi — salah klik bisa dibatalkan
                                                                 dengan mengklik angka lagi. --}}
                                                            <td class="text-center px-1">
                                                                @if($terisi && !$isFinalized)
                                                                    <button type="button"
                                                                            wire:click="clearScore({{ $criteria->id }})"
                                                                            class="btn btn-sm btn-outline-danger p-1"
                                                                            title="Kosongkan nilai kriteria ini">
                                                                        <i class="ti ti-x"></i>
                                                                    </button>
                                                                @endif
                                                            </td>
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
                @endif
            </div>

            {{-- Right: Save Panel --}}
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card w-100 sticky-top" style="top: 80px; z-index: 10;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white fw-semibold">Simpan Penilaian</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-4">Pastikan semua nilai sudah diisi dengan benar.</p>

                        @if($saveStatus === 'saved')
                            <div class="alert alert-success border-0 bg-success-subtle text-success d-flex align-items-center gap-2 mb-4">
                                <i class="ti ti-check-circle fs-5"></i>
                                Nilai berhasil disimpan!
                            </div>
                        @endif

                        @if($isFinalized)
                            <div class="alert alert-danger border-0 bg-danger-subtle text-danger mb-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-lock fs-5"></i>
                                    <div>
                                        <strong class="d-block">Nilai Terkunci</strong>
                                        <span class="small">Penilaian untuk juri ini telah difinalisasi.</span>
                                    </div>
                                </div>
                                {{-- Tanpa wire:confirm: layout admin menimpa window.confirm dan
                                     menyalurkannya lewat document.activeElement, sedangkan modal
                                     Bootstrap membiarkan fokus di body — di dalam modal, wire:confirm
                                     membatalkan aksinya diam-diam. Konfirmasinya lewat modal alasan. --}}
                                <button type="button"
                                        wire:click="openUnlockModal"
                                        class="btn btn-sm btn-outline-danger mt-2">
                                    <i class="ti ti-lock-open me-1"></i> Buka Kunci
                                </button>
                            </div>
                        @endif

                        {{-- Active Judge Total --}}
                        @if(!$assessmentCategories->isEmpty())
                            <div class="bg-primary text-white rounded p-3 mb-3 text-center">
                                <p class="mb-1 text-white text-opacity-75 small fw-semibold text-uppercase">Nilai Juri Aktif</p>
                                <h2 class="fw-semibold mb-0 text-white">{{ $grandTotal ?? 0 }}</h2>
                                @if($selectedJudgeId && count($judges) > 0)
                                    @php
                                        $activeJudge = collect($judges)->first(fn($j) => $j->id == $selectedJudgeId);
                                    @endphp
                                    @if($activeJudge)
                                        <p class="mb-0 text-white text-opacity-50 small">{{ $activeJudge->name }}</p>
                                    @endif
                                @endif
                            </div>

                            {{-- Per-category subtotals (active judge) --}}
                            <div class="mb-3">
                                <p class="text-muted small fw-semibold text-uppercase mb-2">Subtotal Per Kategori</p>
                                @foreach($assessmentCategories as $assessmentCat)
                                    @php
                                        $catSub = 0;
                                        foreach ($assessmentCat->subCategories as $sub) {
                                            foreach ($sub->criterias as $crit) {
                                                $val = $scores[$crit->id] ?? null;
                                                if ($val !== '' && $val !== null) {
                                                    // Dikalikan bobot, konsisten dengan subtotal di atas.
                                                    $catSub += \App\Support\ScoreOptions::value($val) * ($crit->weight ?? 1);
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <span class="text-muted small fw-semibold text-truncate me-2">{{ $assessmentCat->name }}</span>
                                        <span class="fw-semibold text-dark">{{ \App\Support\ScoreOptions::format($catSub) }}</span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Per-Judge Totals + Combined --}}
                            @if($judgeTotals->count() > 0)
                                <div class="border rounded p-3 mb-4">
                                    <p class="text-muted small fw-semibold text-uppercase mb-2">Rekap Semua Juri</p>
                                    @foreach($judgeTotals as $jt)
                                        <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge {{ $selectedJudgeId == $jt['judge']->id ? 'bg-primary' : 'bg-light text-dark border' }} rounded-circle" style="width:8px;height:8px;"></span>
                                                <span class="small fw-semibold">{{ $jt['judge']->name }}</span>
                                            </div>
                                            <span class="fw-semibold {{ $selectedJudgeId == $jt['judge']->id ? 'text-primary' : 'text-dark' }}">{{ \App\Support\ScoreOptions::format((float) $jt['total']) }}</span>
                                        </div>
                                    @endforeach
                                    {{-- Combined total --}}
                                    @php
                                        $combinedTotal = $judgeTotals->sum('total');
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top border-2">
                                        <span class="fw-bold text-dark"><i class="ti ti-sum me-1"></i> Jumlah Semua Juri</span>
                                        <span class="fw-bold text-primary fs-5">{{ \App\Support\ScoreOptions::format((float) $combinedTotal) }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- ========== PENGURANGAN NILAI ========== --}}
                            @if(count($deductionCategories) > 0 || count($globalDeductionCategories) > 0)
                                <div class="border border-danger rounded p-3 mb-4">
                                    <p class="text-danger small fw-semibold text-uppercase mb-3"><i class="ti ti-minus-circle me-1"></i> Pengurangan Nilai (per Kategori)</p>
                                    @php $dedByAssessment = $deductionCategories->groupBy('assessment_category_id'); @endphp
                                    @foreach($assessmentCategories as $ac)
                                        @php $deds = $dedByAssessment->get($ac->id, collect()); @endphp
                                        @if($deds->isNotEmpty())
                                            @foreach($deds as $deductionCat)
                                                <p class="text-muted small fw-bold mb-2"><i class="ti ti-category me-1"></i>{{ $ac->name }} — {{ $deductionCat->name }}</p>
                                                @foreach($deductionCat->criterias as $deductionCrit)
                                                    <div class="mb-3">
                                                        <span class="d-block small fw-semibold mb-1">{{ $deductionCrit->name }}</span>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <button type="button"
                                                                wire:click="$set('deductions.{{ $deductionCrit->id }}', 0)"
                                                                class="btn btn-sm {{ (isset($deductions[$deductionCrit->id]) && $deductions[$deductionCrit->id] == 0) || !isset($deductions[$deductionCrit->id]) ? 'btn-success' : 'btn-outline-success' }} px-2">
                                                                0
                                                            </button>
                                                            @foreach($deductionCrit->deduction_options as $option)
                                                                <button type="button"
                                                                    @if(!$isFinalized) wire:click="$set('deductions.{{ $deductionCrit->id }}', {{ $option }})" @endif
                                                                    {{ $isFinalized ? 'disabled' : '' }}
                                                                    class="btn btn-sm {{ isset($deductions[$deductionCrit->id]) && $deductions[$deductionCrit->id] == $option ? 'btn-danger' : 'btn-outline-danger' }} px-2">
                                                                    {{ $option }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endforeach
                                        @endif
                                    @endforeach

                                    {{-- Pengurangan tingkat: sanksi yang berlaku untuk semua
                                         kategori penilaian di tingkat lomba peserta ini. Memotong
                                         NILAI AKHIR, di luar kolom kategori. --}}
                                    @if(count($globalDeductionCategories) > 0)
                                        <div class="border-top pt-3 mt-3">
                                            <p class="text-danger small fw-semibold text-uppercase mb-2">
                                                <i class="ti ti-world me-1"></i> Pengurangan Tingkat
                                                <span class="badge bg-danger-subtle text-danger ms-1">semua kategori di tingkat ini</span>
                                            </p>
                                            @foreach($globalDeductionCategories as $deductionCat)
                                                <p class="text-muted small fw-bold mb-2">{{ $deductionCat->name }}</p>
                                                @foreach($deductionCat->criterias as $deductionCrit)
                                                    <div class="mb-3">
                                                        <span class="d-block small fw-semibold mb-1">{{ $deductionCrit->name }}</span>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <button type="button"
                                                                wire:click="$set('deductions.{{ $deductionCrit->id }}', 0)"
                                                                class="btn btn-sm {{ (isset($deductions[$deductionCrit->id]) && $deductions[$deductionCrit->id] == 0) || !isset($deductions[$deductionCrit->id]) ? 'btn-success' : 'btn-outline-success' }} px-2">
                                                                0
                                                            </button>
                                                            @foreach($deductionCrit->deduction_options as $option)
                                                                <button type="button"
                                                                    @if(!$isFinalized) wire:click="$set('deductions.{{ $deductionCrit->id }}', {{ $option }})" @endif
                                                                    {{ $isFinalized ? 'disabled' : '' }}
                                                                    class="btn btn-sm {{ isset($deductions[$deductionCrit->id]) && $deductions[$deductionCrit->id] == $option ? 'btn-danger' : 'btn-outline-danger' }} px-2">
                                                                    {{ $option }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Deduction summary --}}
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="fw-semibold text-danger small">Total Pengurangan</span>
                                        <span class="fw-bold text-danger">-{{ \App\Support\ScoreOptions::format((float) $totalDeductionsKategori + $totalDeductionsGlobal) }}</span>
                                    </div>

                                    @if($deductionSaveStatus === 'saved')
                                        <div class="alert alert-success py-1 px-2 fs-2 mt-2 mb-0">
                                            <i class="ti ti-check-circle me-1"></i> Pengurangan disimpan!
                                        </div>
                                    @endif

                                    @if($simulateMode)
                                        <button type="button" disabled class="btn btn-secondary w-100 py-2 fw-semibold mt-3 opacity-75">
                                            <i class="ti ti-device-floppy me-1"></i> Simulasi — Tidak Tersimpan
                                        </button>
                                    @else
                                        <button wire:click="saveDeductions"
                                                class="btn btn-danger w-100 py-2 fw-semibold mt-3"
                                                {{ $isFinalized ? 'disabled' : '' }}
                                                wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveDeductions">
                                                <i class="ti ti-device-floppy me-1"></i> {{ $isFinalized ? 'Pengurangan Terkunci' : 'Simpan Pengurangan' }}
                                            </span>
                                            <span wire:loading wire:target="saveDeductions">
                                                <span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...
                                            </span>
                                        </button>
                                    @endif
                                </div>

                                {{-- Final Score --}}
                                @if($totalDeductions > 0 || $judgeTotals->count() > 0)
                                    <div class="border rounded p-3 mb-4 bg-dark text-white">
                                        <p class="text-white text-opacity-75 small fw-semibold text-uppercase mb-2">Nilai Akhir</p>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small">Nilai Juri</span>
                                            <span class="fw-semibold">{{ \App\Support\ScoreOptions::format((float) $judgeTotals->sum('total')) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small">Pengurangan Kategori</span>
                                            <span class="fw-semibold {{ $totalDeductionsKategori > 0 ? 'text-danger' : 'text-white text-opacity-50' }}">-{{ \App\Support\ScoreOptions::format((float) $totalDeductionsKategori) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small">Pengurangan Tingkat</span>
                                            <span class="fw-semibold {{ $totalDeductionsGlobal > 0 ? 'text-danger' : 'text-white text-opacity-50' }}">-{{ \App\Support\ScoreOptions::format((float) $totalDeductionsGlobal) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                                            <span class="fw-bold">NILAI AKHIR</span>
                                            <span class="fw-bold fs-4">{{ \App\Support\ScoreOptions::format((float) $judgeTotals->sum('total') - $totalDeductions) }}</span>
                                        </div>
                                    </div>
                                @endif
                            @endif
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
                                <span class="fw-semibold text-primary">{{ $filledCount }}/{{ $totalCriteria }}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success"
                                     role="progressbar"
                                     style="width: {{ $progress }}%"
                                     aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        @if($simulateMode)
                            <div class="alert alert-warning py-2 px-3 small fw-semibold mb-2">
                                <i class="ti ti-flask me-1"></i> Mode simulasi: tombol simpan dinonaktifkan.
                            </div>
                            <button type="button" disabled class="btn btn-secondary w-100 py-2 fw-semibold mb-2 opacity-75">
                                <i class="ti ti-device-floppy me-2"></i> Simulasi — Tidak Tersimpan
                            </button>

                            <button wire:click="resetScores"
                                    class="btn btn-outline-warning w-100 py-2 fw-semibold mb-3">
                                <i class="ti ti-refresh me-1"></i> Kosongkan Nilai Simulasi
                            </button>
                        @elseif($totalCriteria > 0 && !$isFinalized)
                            <button wire:click="saveScores"
                                    class="btn btn-primary w-100 py-8 fw-semibold mb-2"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveScores">
                                    <i class="ti ti-device-floppy me-2"></i> Simpan Penilaian
                                </span>
                                <span wire:loading wire:target="saveScores">
                                    <span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...
                                </span>
                            </button>

                            @if($filledCount == $totalCriteria)
                                <button wire:click="finalizeScores"
                                        wire:confirm="Yakin ingin memfinalisasi nilai? Setelah difinalisasi, nilai tidak dapat diubah kembali."
                                        class="btn btn-success w-100 py-2 fw-semibold mb-2"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="finalizeScores">
                                        <i class="ti ti-lock me-2"></i> Finalisasi & Kunci Nilai
                                    </span>
                                    <span wire:loading wire:target="finalizeScores">
                                        <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                    </span>
                                </button>
                            @endif

                            <button wire:click="resetScores"
                                    wire:confirm="Yakin ingin mereset semua nilai juri ini? Data yang sudah disimpan akan dihapus."
                                    class="btn btn-outline-danger w-100 py-2 fw-semibold mb-3"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="resetScores">
                                    <i class="ti ti-refresh me-1"></i> Reset Nilai Juri Ini
                                </span>
                                <span wire:loading wire:target="resetScores">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Mereset...
                                </span>
                            </button>
                        @endif

                        <button wire:click="backToParticipants"
                                class="btn btn-outline-secondary w-100 py-2 fw-semibold">
                            <i class="ti ti-arrow-left me-1"></i> Kembali ke Daftar Peserta
                        </button>

                        <div class="mt-4 p-3 bg-light rounded">
                            <p class="text-muted small mb-2"><i class="ti ti-info-circle me-1"></i> Tips</p>
                            <ul class="text-muted small mb-0 ps-3">
                                <li>Klik angka nilai untuk menilai tiap kriteria</li>
                                <li>Nilai yang terpilih akan berwarna biru</li>
                                <li>Subtotal & total terupdate otomatis</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Mode Simulasi (Sandbox) --}}
    <div class="modal fade" id="simulateModal" tabindex="-1" aria-labelledby="simulateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header {{ $simulateMode ? 'bg-warning' : 'bg-light' }}">
                    <h5 class="modal-title fw-bold" id="simulateModalLabel">
                        <i class="ti ti-flask me-2 {{ $simulateMode ? 'text-white' : 'text-warning' }}"></i>
                        <span {{ $simulateMode ? 'class="text-white"' : '' }}>Mode Simulasi (Sandbox)</span>
                    </h5>
                    <button type="button" class="btn-close {{ $simulateMode ? 'btn-close-white' : '' }}" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($simulateMode)
                        <div class="alert alert-warning mb-3 d-flex align-items-start gap-2">
                            <i class="ti ti-alert-triangle fs-5 mt-1"></i>
                            <div class="small">
                                <strong>Sedang aktif.</strong> Semua nilai yang diklik saat ini hanya latihan — tidak tersimpan ke database.
                            </div>
                        </div>
                    @endif
                    <p class="text-muted small">
                        Mode Simulasi memungkinkan Anda mencoba alur input nilai lengkap — pilih kategori, peserta, juri, klik nilai, lihat subtotal &amp; pengurangan — <strong>tanpa menyimpan apa pun</strong> ke database.
                    </p>
                    <ul class="text-muted small mb-0 ps-3">
                        <li>Simpan, finalisasi, dan reset dinonaktifkan</li>
                        <li>Rekap juri dihitung dari layar saja</li>
                        <li>Cocok untuk melatih operator/juri sebelum hari-H</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    <button type="button"
                            wire:click="toggleSimulateMode"
                            data-bs-dismiss="modal"
                            class="btn {{ $simulateMode ? 'btn-outline-warning fw-semibold' : 'btn-warning fw-bold' }}">
                        <i class="ti ti-{{ $simulateMode ? 'toggle-right-filled' : 'toggle-left' }} me-1"></i>
                        {{ $simulateMode ? 'Matikan Simulasi' : 'Aktifkan Simulasi' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal rincian nilai kosong. Dipisah dari lencana di kepala lembar
         supaya lencananya tetap satu baris walau yang kosong puluhan kriteria;
         nama kriteria per juri baru ditulis di sini. --}}
    @if($nilaiKosong['total'] > 0)
        <div class="modal fade" id="nilaiKosongModal" tabindex="-1" aria-labelledby="nilaiKosongModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-warning-subtle">
                        <h5 class="modal-title fw-bold" id="nilaiKosongModalLabel">
                            <i class="ti ti-alert-triangle text-warning me-2"></i>
                            {{ $nilaiKosong['total'] }} nilai masih kosong
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            Peserta <strong>{{ $selectedRegistration->display_name }}</strong>
                            @if($selectedRound)
                                pada babak <strong>{{ $selectedRound->name }}</strong>
                            @endif
                            belum lengkap. Nilai yang masih kosong menghalangi finalisasi,
                            jadi perbaiki dulu sebelum mengunci.
                        </p>

                        @foreach($nilaiKosong['judges'] as $baris)
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <span class="badge bg-secondary-subtle text-secondary fw-semibold flex-shrink-0">
                                    {{ $baris['judge']->name }}
                                </span>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($baris['criteria'] as $nama)
                                        <span class="badge bg-warning-subtle text-warning">{{ $nama }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Buka Kunci — satu peserta × satu juri. --}}
    @if($showUnlockModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">
                            <i class="ti ti-lock-open me-1"></i> Buka Kunci Nilai
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeUnlockModal"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row mb-3 small">
                            <dt class="col-5 text-muted fw-normal">Peserta</dt>
                            <dd class="col-7 fw-semibold mb-1">{{ $selectedRegistration?->display_name }}</dd>
                            <dt class="col-5 text-muted fw-normal">Juri</dt>
                            <dd class="col-7 fw-semibold mb-1">
                                {{ collect($judges)->firstWhere('id', $selectedJudgeId)?->name ?? '—' }}
                            </dd>
                            @if($selectedRoundId)
                                <dt class="col-5 text-muted fw-normal">Babak</dt>
                                <dd class="col-7 fw-semibold mb-1">
                                    {{ \App\Models\CompetitionRound::where('eventner_id', $eventner->id)->find($selectedRoundId)?->name }}
                                </dd>
                            @endif
                        </dl>

                        <div class="alert alert-warning border-0 bg-warning-subtle text-warning small mb-3">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Kunci hanya dilepas untuk juri ini, pada peserta ini saja. Nilai bisa
                            diubah lagi sampai difinalisasi ulang.
                        </div>

                        <label for="unlockReason" class="form-label fw-semibold">
                            Alasan <span class="text-danger">*</span>
                        </label>
                        <textarea id="unlockReason"
                                  wire:model="unlockReason"
                                  rows="3"
                                  maxlength="500"
                                  class="form-control @error('unlockReason') is-invalid @enderror"
                                  placeholder="Misalnya: salah juri saat finalisasi, atau nilai tertukar dengan regu lain."></textarea>
                        @error('unlockReason')
                            <span class="text-danger fs-2">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeUnlockModal">Batal</button>
                        <button type="button"
                                wire:click="unlockScores"
                                class="btn btn-danger"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="unlockScores">
                                <i class="ti ti-lock-open me-1"></i> Buka Kunci
                            </span>
                            <span wire:loading wire:target="unlockScores">
                                <span class="spinner-border spinner-border-sm me-1"></span> Membuka...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
