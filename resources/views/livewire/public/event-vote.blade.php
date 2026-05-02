<div>
    {{-- Banner --}}
    <section class="bg-primary-subtle pt-7 pb-5">
        <div class="custom-container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-semibold">Laman Voting Digital</span>
                    <h1 class="text-dark fw-semibold fs-10 mb-2">Dukung Tim Jagoan Anda!</h1>
                    <p class="fs-5 text-muted mb-4">
                        Setiap vote sangat berarti untuk menentukan juara favorit di <strong>{{ $eventner->nama_event }}</strong>.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="btn btn-outline-primary px-4">
                            <i class="ti ti-info-circle me-1"></i> Info Event
                        </a>
                        <a href="{{ route('event.participant', $eventner->slug) }}" class="btn btn-outline-secondary px-4">
                            <i class="ti ti-users me-1"></i> Daftar Peserta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="custom-container">
            <div class="row">
                {{-- Left: Team Selection --}}
                <div class="col-lg-8">
                    @if (session()->has('error'))
                        <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show mb-4">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($view == 'categories')
                        {{-- View A: Categories --}}
                        <div class="mb-5">
                            <h4 class="fw-semibold text-dark mb-4">Pilih Kategori Lomba</h4>
                            <div class="row g-4">
                                @foreach($categories as $cat)
                                    <div class="col-md-6">
                                        <div wire:click="selectCategory({{ $cat->id }})"
                                             class="card w-100 mb-0"
                                             style="cursor:pointer;">
                                            <div class="card-body p-4 text-center">
                                                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px;">
                                                    <i class="ti ti-medal fs-8"></i>
                                                </div>
                                                <h5 class="fw-semibold text-dark mb-1">{{ $cat->name }}</h5>
                                                <p class="text-muted mb-0 fs-3">{{ $cat->registrations_count }} Kontingen Terdaftar</p>
                                                <div class="mt-3">
                                                    <span class="btn btn-sm btn-primary px-3">Pilih Kategori</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        {{-- View B: Participants --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <button wire:click="backToCategories" class="btn btn-sm btn-light d-flex align-items-center justify-content-center">
                                    <i class="ti ti-arrow-left fs-5"></i>
                                </button>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0">
                                        <li class="breadcrumb-item"><a href="#" wire:click.prevent="backToCategories">Kategori</a></li>
                                        <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">{{ $selectedCategory->name }}</li>
                                    </ol>
                                </nav>
                            </div>

                            <div class="card w-100 mb-4">
                                <div class="card-body p-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0">
                                            <i class="ti ti-search text-muted"></i>
                                        </span>
                                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control border-start-0" placeholder="Cari nama sekolah atau kontingen...">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h4 class="fw-semibold text-dark mb-0">Pilih Peserta</h4>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold">{{ $participants->count() }} Ditemukan</span>
                            </div>

                            <div class="row g-3">
                                @forelse($participants as $reg)
                                    <div class="col-md-6">
                                        <div wire:click="selectTeam({{ $reg->id }})"
                                             class="card w-100 mb-0 border {{ $selectedRegistrationId == $reg->id ? 'border-primary' : '' }}"
                                             style="cursor:pointer;">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="position-relative">
                                                        @if($reg->logo_sekolah)
                                                            <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded-circle border" width="56" height="56" style="object-fit:cover;" alt="">
                                                        @else
                                                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                                                <i class="ti ti-school fs-6"></i>
                                                            </div>
                                                        @endif
                                                        @if($selectedRegistrationId == $reg->id)
                                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-primary p-1">
                                                                <i class="ti ti-check text-white fs-2"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <h6 class="fw-semibold mb-0 fs-4 text-truncate">{{ $reg->nama_sekolah }}</h6>
                                                        <p class="text-muted mb-0 fs-2 text-truncate">Pelatih: {{ $reg->nama_pelatih }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12 py-5 text-center">
                                        <img src="{{ asset('templates/assets/images/backgrounds/search-error.svg') }}" width="200" class="mb-3" alt="">
                                        <p class="text-muted fs-4">Tidak ada kontingen yang sesuai dengan pencarian Anda.</p>
                                        <button wire:click="$set('search', '')" class="btn btn-link text-primary p-0">Hapus Pencarian</button>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right: Vote Form --}}
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card w-100 sticky-top" style="top: 100px; z-index: 10;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-heart me-2"></i>Form Voting</h5>
                        </div>
                        <div class="card-body p-4">
                            <form wire:submit.prevent="submitVote">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Jumlah Vote</label>
                                    <div class="input-group">
                                        <button class="btn btn-outline-primary" type="button" wire:click="$set('voteCount', {{ max(1, $voteCount - 1) }})">-</button>
                                        <input type="number" class="form-control text-center fw-semibold" wire:model.live="voteCount" min="1">
                                        <button class="btn btn-outline-primary" type="button" wire:click="$set('voteCount', {{ $voteCount + 1 }})">+</button>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 px-1">
                                        <button type="button" wire:click="$set('voteCount', 10)" class="btn btn-sm btn-outline-primary px-2">10</button>
                                        <button type="button" wire:click="$set('voteCount', 50)" class="btn btn-sm btn-outline-primary px-2">50</button>
                                        <button type="button" wire:click="$set('voteCount', 100)" class="btn btn-sm btn-outline-primary px-2">100</button>
                                        <button type="button" wire:click="$set('voteCount', 500)" class="btn btn-sm btn-outline-primary px-2">500</button>
                                    </div>
                                    @error('voteCount') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Lengkap Anda</label>
                                    <input type="text" class="form-control" wire:model="voterName" placeholder="Contoh: Budi Santoso">
                                    @error('voterName') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Email (Untuk Bukti)</label>
                                    <input type="email" class="form-control" wire:model="voterEmail" placeholder="email@contoh.com">
                                    @error('voterEmail') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>

                                <div class="bg-primary-subtle p-3 mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Harga per Vote</span>
                                        <span class="fw-semibold text-dark">Rp 1.000</span>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-2">
                                        <span class="fw-semibold text-dark fs-5">Total Bayar</span>
                                        <span class="fw-semibold text-primary fs-5">Rp {{ number_format($voteCount * 1000, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <button type="submit"
                                        class="btn btn-primary w-100 py-8 fw-semibold"
                                        {{ !$selectedRegistrationId ? 'disabled' : '' }}
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove>Lanjutkan Ke Pembayaran</span>
                                    <span wire:loading>
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Memproses...
                                    </span>
                                </button>

                                @if(!$selectedRegistrationId)
                                    <p class="text-center text-danger small mt-2">Silakan pilih kontingen terlebih dahulu</p>
                                @endif

                                <div class="mt-4 text-center">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" height="25" alt="QRIS">
                                    <p class="text-muted small mb-0 mt-2">Pembayaran aman via Xendit QRIS</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
