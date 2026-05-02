<div>
    {{-- Banner --}}
    <section class="bg-primary-subtle pt-7 pb-5">
        <div class="custom-container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    @if($eventner->logo_event)
                        <img src="{{ asset('storage/' . $eventner->logo_event) }}" class="rounded-circle border border-3 border-white mb-3" width="80" height="80" style="object-fit:cover;" alt="">
                    @endif
                    <span class="badge bg-primary text-white mb-3 px-3 py-2 fw-semibold">Pendaftaran Peserta</span>
                    <h1 class="text-dark fw-semibold fs-10 mb-2">Booking Slot Pasukan</h1>
                    <p class="fs-5 text-muted mb-4">
                        {{ $eventner->nama_event }} — <em>{{ $eventner->diselenggarakan_oleh }}</em>
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="btn btn-outline-primary px-4">
                            <i class="ti ti-arrow-left me-1"></i> Kembali
                        </a>
                        <a href="{{ route('event.participant', $eventner->slug) }}" class="btn btn-outline-secondary px-4">
                            <i class="ti ti-users me-1"></i> Daftar Peserta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Flash Messages --}}
    <section class="pt-4">
        <div class="custom-container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if(session('error'))
                        <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Progress Stepper --}}
    <section class="pt-0 pb-0">
        <div class="custom-container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="d-flex align-items-center gap-2">
                            @for($i = 1; $i <= 3; $i++)
                                <div class="d-flex align-items-center">
                                    <div class="{{ $step >= $i ? 'bg-primary text-white' : 'bg-light text-muted' }} rounded-circle d-flex align-items-center justify-content-center fw-semibold" style="width:36px;height:36px;">
                                        @if($step > $i)
                                            <i class="ti ti-check"></i>
                                        @else
                                            {{ $i }}
                                        @endif
                                    </div>
                                    <span class="ms-2 fw-semibold {{ $step >= $i ? 'text-dark' : 'text-muted' }} d-none d-sm-inline">
                                        @if($i == 1) Pilih Kategori
                                        @elseif($i == 2) Data Sekolah
                                        @else Konfirmasi
                                        @endif
                                    </span>
                                </div>
                                @if($i < 3)
                                    <div class="mx-3" style="width:40px;height:2px;background:{{ $step > $i ? '#635BFF' : '#dee2e6' }};"></div>
                                @endif
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Form Content --}}
    <section class="py-4">
        <div class="custom-container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    {{-- STEP 1: Pilih Kategori --}}
                    @if($step === 1)
                        <div class="card w-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-category me-2"></i>Pilih Kategori Lomba</h5>
                            </div>
                            <div class="card-body p-4">
                                @error('selectedCategories')
                                    <div class="alert alert-danger border-0 bg-danger-subtle text-danger py-2 mb-3">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <p class="text-muted mb-4">Pilih kategori lomba dan jumlah pasukan yang ingin didaftarkan.</p>

                                @foreach($categories as $cat)
                                    @php
                                        $isFull = $cat->kuota && $cat->registrations_count >= $cat->kuota;
                                        $maxPerSchool = $cat->max_registrations_per_school ?? 1;
                                        $selected = in_array($cat->id, $selectedCategories);
                                    @endphp
                                    <div class="card w-100 mb-3 border {{ $selected ? 'border-primary' : '' }} {{ $isFull ? 'opacity-50' : '' }}">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="form-check mt-1">
                                                    <input type="checkbox"
                                                        class="form-check-input"
                                                        wire:model="selectedCategories"
                                                        value="{{ $cat->id }}"
                                                        id="cat_{{ $cat->id }}"
                                                        {{ $isFull ? 'disabled' : '' }}>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label for="cat_{{ $cat->id }}" class="fw-semibold text-dark fs-4 mb-1" style="cursor:pointer;">
                                                        {{ $cat->name }}
                                                    </label>
                                                    <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                                                        <span class="badge bg-primary-subtle text-primary px-2 py-1">
                                                            <i class="ti ti-users me-1"></i>{{ $cat->registrations_count }} / {{ $cat->kuota ?? '∞' }} terisi
                                                        </span>
                                                        <span class="badge bg-info-subtle text-info px-2 py-1">
                                                            <i class="ti ti-repeat me-1"></i>Max {{ $maxPerSchool }} pasukan/sekolah
                                                        </span>
                                                        @if($isFull)
                                                            <span class="badge bg-danger-subtle text-danger px-2 py-1">
                                                                <i class="ti ti-ban me-1"></i>Kuota Penuh
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- Team count selector (visible when checked) --}}
                                                    @if($selected && $maxPerSchool > 1)
                                                        <div class="mt-3 d-flex align-items-center gap-2">
                                                            <span class="text-muted small">Jumlah pasukan:</span>
                                                            @for($i = 1; $i <= $maxPerSchool; $i++)
                                                                <button type="button"
                                                                    wire:click="$set('teamCounts.{{ $cat->id }}', {{ $i }})"
                                                                    class="btn btn-sm {{ ($teamCounts[$cat->id] ?? 1) == $i ? 'btn-primary' : 'btn-outline-primary' }} px-3">
                                                                    {{ $i }}
                                                                </button>
                                                            @endfor
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($categories->isEmpty())
                                    <div class="text-center py-4">
                                        <i class="ti ti-mood-sad fs-7 text-muted d-block mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada kategori lomba yang tersedia.</p>
                                    </div>
                                @endif

                                <div class="text-end mt-4">
                                    <button wire:click="nextStep" class="btn btn-primary px-5">
                                        Lanjut <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- STEP 2: Data Sekolah --}}
                    @if($step === 2)
                        <div class="card w-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-school me-2"></i>Data Sekolah</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">NPSN <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="npsn" class="form-control" placeholder="Nomor Pokok Sekolah Nasional" maxlength="20">
                                        @error('npsn') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nama Sekolah <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="nama_sekolah" class="form-control" placeholder="Nama sekolah sesuai data resmi">
                                        @error('nama_sekolah') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">No HP / WhatsApp <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
                                        @error('no_hp') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email Sekolah</label>
                                        <input type="email" wire:model="school_email" class="form-control" placeholder="opsional">
                                        @error('school_email') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nama Pelatih</label>
                                        <input type="text" wire:model="nama_pelatih" class="form-control" placeholder="Nama pelatih / pembina">
                                        @error('nama_pelatih') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-12"><hr class="my-1"></div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                        <input type="password" wire:model="password" class="form-control" placeholder="Minimal 6 karakter">
                                        @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
                                        <small class="text-muted">Digunakan untuk mengelola pendaftaran Anda nanti.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                                        <input type="password" wire:model="password_confirmation" class="form-control" placeholder="Ulangi password">
                                        @error('password_confirmation') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button wire:click="prevStep" class="btn btn-outline-secondary px-4">
                                        <i class="ti ti-arrow-left me-1"></i> Kembali
                                    </button>
                                    <button wire:click="nextStep" class="btn btn-primary px-5">
                                        Lanjut <i class="ti ti-arrow-right ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- STEP 3: Review & Submit --}}
                    @if($step === 3)
                        <div class="card w-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-clipboard-check me-2"></i>Konfirmasi Booking</h5>
                            </div>
                            <div class="card-body p-4">
                                {{-- School Info --}}
                                <h6 class="fw-semibold text-dark mb-3"><i class="ti ti-school me-1"></i> Data Sekolah</h6>
                                <div class="bg-light-subtle p-3 rounded mb-4">
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <span class="text-muted small">NPSN</span>
                                            <p class="fw-semibold mb-0">{{ $npsn }}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small">Nama Sekolah</span>
                                            <p class="fw-semibold mb-0">{{ $nama_sekolah }}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small">No HP</span>
                                            <p class="fw-semibold mb-0">{{ $no_hp }}</p>
                                        </div>
                                        @if($school_email)
                                            <div class="col-sm-6">
                                                <span class="text-muted small">Email</span>
                                                <p class="fw-semibold mb-0">{{ $school_email }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Category Summary --}}
                                <h6 class="fw-semibold text-dark mb-3"><i class="ti ti-category me-1"></i> Kategori & Pasukan</h6>
                                @foreach($categories->whereIn('id', $selectedCategories) as $cat)
                                    <div class="d-flex align-items-center justify-content-between bg-light-subtle p-3 rounded mb-2">
                                        <div>
                                            <span class="fw-semibold text-dark">{{ $cat->name }}</span>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                            {{ $teamCounts[$cat->id] ?? 1 }} pasukan
                                        </span>
                                    </div>
                                @endforeach

                                <div class="bg-warning-subtle p-3 rounded mt-4 mb-4">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="ti ti-info-circle text-warning mt-1"></i>
                                        <div>
                                            <p class="fw-semibold text-dark mb-1">Status: Booking</p>
                                            <p class="text-muted small mb-0">
                                                Pendaftaran Anda akan berstatus <strong>Booking</strong>.
                                                Setelah Technical Meeting, Anda bisa mengkonfirmasi dan melengkapi data pasukan
                                                melalui link yang akan dikirim.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button wire:click="prevStep" class="btn btn-outline-secondary px-4">
                                        <i class="ti ti-arrow-left me-1"></i> Kembali
                                    </button>
                                    <button wire:click="submit" class="btn btn-success px-5" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="submit">
                                            <i class="ti ti-check me-1"></i> Booking Sekarang
                                        </span>
                                        <span wire:loading wire:target="submit">
                                            <span class="spinner-border spinner-border-sm me-1"></span> Memproses...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </section>
</div>
