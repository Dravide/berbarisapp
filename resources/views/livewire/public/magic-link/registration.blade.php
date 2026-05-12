<div>
    <div class="custom-container mt-4">
        {{-- Breadcrumb Card --}}
        <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
            <div class="card-body px-4 py-3">
                <div class="row align-items-center">
                    <div class="col-9">
                        <h4 class="fw-semibold mb-8">Kelola Pendaftaran</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted text-decoration-none" href="{{ url('/') }}">Home</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $registration->eventner->nama_event }}</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-3 text-end mb-n5">
                        <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}"
                             alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">

                {{-- Flash Messages --}}
                @if(session()->has('success'))
                    <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show mb-4">
                        <i class="ti ti-check me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session()->has('error'))
                    <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show mb-4">
                        <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Status Badge --}}
                @php
                    $statusConfig = [
                        'booking' => ['bg-warning-subtle text-warning border-warning', 'ti-clock', 'Booking'],
                        'confirmed' => ['bg-info-subtle text-info border-info', 'ti-send', 'Menunggu Verifikasi'],
                        'Terverifikasi' => ['bg-success-subtle text-success border-success', 'ti-circle-check', 'Terverifikasi'],
                        'Ditolak' => ['bg-danger-subtle text-danger border-danger', 'ti-alert-triangle', 'Ditolak — Perlu Revisi'],
                    ];
                    $sc = $statusConfig[$registration->status_berkas] ?? $statusConfig['booking'];
                @endphp
                <div class="card w-100 mb-4 border {{ $sc[0] }}">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="{{ explode(' ', $sc[0])[0] }} rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="ti {{ $sc[1] }} fs-6"></i>
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-0">{{ $sc[2] }}</h6>
                            <p class="text-muted small mb-0">
                                @if($registration->status_berkas === 'booking')
                                    Slot Anda sudah dipesan. Lengkapi data pasukan dan konfirmasi setelah Technical Meeting.
                                @elseif($registration->status_berkas === 'confirmed')
                                    Data telah dikirim ke panitia. Menunggu verifikasi.
                                @elseif($registration->status_berkas === 'Terverifikasi')
                                    Pendaftaran Anda telah disetujui panitia. Sampai jumpa di hari perlombaan!
                                @elseif($registration->status_berkas === 'Ditolak')
                                    Ada berkas/data yang perlu diperbaiki. Silakan revisi dan kirim ulang.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Team Tabs (if multiple registrations) --}}
                @if($siblingRegistrations->count() > 1)
                    <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                        @foreach($siblingRegistrations as $sib)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $activeRegId == $sib->id ? 'active bg-primary text-white' : '' }}"
                                    wire:click="switchRegistration({{ $sib->id }})"
                                    type="button" role="tab">
                                    <i class="ti ti-users me-1"></i> {{ $sib->competitionCategory->name }}
                                    @if($sib->participants->count() > 0)
                                        <span class="badge {{ $activeRegId == $sib->id ? 'bg-white text-primary' : 'bg-primary-subtle text-primary' }} ms-1">{{ $sib->participants->count() }}</span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Active Category Header --}}
                <div class="card w-100 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white fw-semibold">
                            <i class="ti ti-medal me-2"></i> {{ $registration->competitionCategory->name }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark px-2 py-1 fw-semibold">
                                <i class="ti ti-school me-1"></i>{{ $registration->nama_sekolah }} (NPSN: {{ $registration->npsn }})
                            </span>
                            @if($registration->nama_pelatih)
                                <span class="badge bg-light text-dark px-2 py-1 fw-semibold">
                                    <i class="ti ti-user me-1"></i>Pelatih: {{ $registration->nama_pelatih }}
                                </span>
                            @endif
                            @if($registration->participants->count() > 0)
                                <span class="badge bg-light text-dark px-2 py-1 fw-semibold">
                                    <i class="ti ti-users me-1"></i>{{ $registration->participants->count() }} anggota
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- BOOKING STATE: Confirm button --}}
                @if($registration->status_berkas === 'booking')
                    <div class="card w-100 mb-4 border-primary">
                        <div class="card-body p-4">
                            <h5 class="fw-semibold mb-3"><i class="ti ti-info-circle text-primary me-2"></i>Lengkapi Data & Konfirmasi</h5>

                            @if($registration->eventner->technical_meeting && now()->lt($registration->eventner->technical_meeting))
                                <div class="alert alert-warning border-0 bg-warning-subtle text-warning mb-0">
                                    <i class="ti ti-clock me-1"></i> Konfirmasi baru bisa dilakukan setelah Technical Meeting:
                                    <strong>{{ \Carbon\Carbon::parse($registration->eventner->technical_meeting)->translatedFormat('d F Y, H:i') }}</strong>.
                                    Anda sudah bisa mengisi draft data di bawah ini.
                                </div>
                            @else
                                <div class="alert alert-success border-0 bg-success-subtle text-success mb-0">
                                    <i class="ti ti-check me-1"></i> Technical Meeting sudah dilaksanakan. Silakan lengkapi data dan tekan <strong>"Konfirmasi"</strong>.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                @php
                    $isLocked = ($registration->is_finalized && $registration->status_berkas !== 'Ditolak') || $registration->status_berkas === 'Terverifikasi';
                @endphp

                <fieldset {{ $isLocked ? 'disabled' : '' }}>

                    {{-- Nama Pelatih --}}
                    <div class="card w-100 mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-semibold mb-4 border-bottom pb-3"><i class="ti ti-user me-2"></i>Data Pelatih</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Pelatih <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="namaPelatih" class="form-control" placeholder="Nama lengkap pelatih">
                                    @error('namaPelatih') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">No HP</label>
                                    <input type="text" class="form-control" value="{{ $registration->no_hp }}" disabled>
                                    <small class="text-muted">Tidak bisa diubah</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Foto Pelatih</label>
                                    <input type="file" class="form-control" wire:model="fotoPelatih" accept="image/*">
                                    @error('fotoPelatih') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                    @if($registration->foto_pelatih)
                                        <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check me-1"></i>Sudah diunggah</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <form wire:submit.prevent="submit(false)">
                        {{-- Documents --}}
                        <div class="card w-100 mb-4">
                            <div class="card-body p-4">
                                <h5 class="fw-semibold mb-4 border-bottom pb-3"><i class="ti ti-file-text me-2"></i>Berkas Persyaratan</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Logo Sekolah</label>
                                        <input type="file" class="form-control" wire:model="logoSekolah" accept="image/*">
                                        @error('logoSekolah') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        @if($registration->logo_sekolah)
                                            <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check me-1"></i>Sudah diunggah</span>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Surat Tugas (.pdf/.jpg)</label>
                                        <input type="file" class="form-control" wire:model="suratTugas">
                                        @error('suratTugas') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        @if($registration->surat_tugas)
                                            <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check me-1"></i>Sudah diunggah</span>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Kwitansi Pendaftaran</label>
                                        <input type="file" class="form-control" wire:model="buktiPendaftaran" accept="image/*">
                                        @error('buktiPendaftaran') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        @if($registration->bukti_pendaftaran)
                                            <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check me-1"></i>Sudah diunggah</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Danton --}}
                        <div class="card w-100 mb-4">
                            <div class="card-body p-4">
                                <h5 class="fw-semibold mb-4 border-bottom pb-3"><i class="ti ti-star text-warning me-2"></i>Komandan Pleton (Danton)</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Nama Danton <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" wire:model="dantonNama" placeholder="Nama lengkap...">
                                        @error('dantonNama') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">NISN</label>
                                        <input type="text" class="form-control" wire:model="dantonNisn" placeholder="NISN...">
                                        @error('dantonNisn') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold">Pas Foto</label>
                                        <input type="file" class="form-control" wire:model="dantonFoto" accept="image/*">
                                        @error('dantonFoto') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        @if($registration->danton_foto)
                                            <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check me-1"></i>Sudah diunggah</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Participants --}}
                        <div class="card w-100 mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                                    <h5 class="fw-semibold mb-0"><i class="ti ti-users me-2"></i>Anggota Pasukan</h5>
                                    @if(!$isLocked)
                                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addParticipant">
                                            <i class="ti ti-plus me-1"></i> Tambah
                                        </button>
                                    @endif
                                </div>

                                @foreach($participants as $index => $participant)
                                    <div class="row g-2 mb-3 pb-3 border-bottom align-items-start">
                                        <div class="col-auto">
                                            <span class="badge bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;">{{ $index + 1 }}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fs-2 fw-semibold">Nama <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" wire:model="participants.{{ $index }}.nama" placeholder="Nama lengkap...">
                                            @error('participants.'.$index.'.nama') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fs-2 fw-semibold">NISN</label>
                                            <input type="text" class="form-control" wire:model="participants.{{ $index }}.nisn" placeholder="NISN...">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fs-2 fw-semibold">Pas Foto</label>
                                            <input type="file" class="form-control" wire:model="participants.{{ $index }}.foto" accept="image/*">
                                            @if(isset($participant['existing_foto']) && $participant['existing_foto'])
                                                <span class="text-success fs-2"><i class="ti ti-check"></i> Sudah ada</span>
                                            @endif
                                        </div>
                                        <div class="col-auto">
                                            @if(count($participants) > 1 && !$isLocked)
                                                <button type="button" class="btn btn-sm btn-outline-danger mt-4" wire:click="removeParticipant({{ $index }})">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        @if(!$isLocked)
                            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-5">
                                <button type="submit" class="btn btn-outline-primary px-4">
                                    <i class="ti ti-device-floppy me-1"></i> Simpan Draft
                                </button>

                                @if($registration->status_berkas === 'booking' && (!$registration->eventner->technical_meeting || now()->gte($registration->eventner->technical_meeting)))
                                    <button type="button" class="btn btn-success px-4" onclick="confirmAction()">
                                        <i class="ti ti-check me-1"></i> Konfirmasi & Kirim
                                    </button>
                                @elseif($registration->status_berkas !== 'booking')
                                    <button type="button" class="btn btn-primary px-4" onclick="confirmFinalization()">
                                        <i class="ti ti-send me-1"></i> Finalisasi Data
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="text-center mb-5">
                                @if($registration->status_berkas === 'Terverifikasi')
                                    <span class="badge bg-success-subtle text-success border-0 px-4 py-2 fs-3">
                                        <i class="ti ti-circle-check me-1"></i> TERVERIFIKASI PANITIA
                                    </span>
                                @else
                                    <span class="badge bg-info-subtle text-info border-0 px-4 py-2 fs-3">
                                        <i class="ti ti-clock me-1"></i> MENUNGGU VERIFIKASI
                                    </span>
                                @endif
                            </div>
                        @endif
                    </form>
                </fieldset>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmFinalization() {
            Swal.fire({
                title: 'Finalisasi Data?',
                text: "Data akan dikunci dan tidak bisa diubah lagi.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#5d87ff',
                cancelButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('submit', true);
                }
            })
        }

        function confirmAction() {
            Swal.fire({
                title: 'Konfirmasi Pendaftaran?',
                text: "Pastikan semua data pasukan sudah benar. Data akan dikirim ke panitia untuk diverifikasi.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#57de00',
                cancelButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Konfirmasi!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('confirm');
                }
            })
        }
    </script>
</div>
