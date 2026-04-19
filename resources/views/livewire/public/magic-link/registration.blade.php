<div>
    <!-- Banner Start -->
    <section class="bg-primary-subtle pt-7 pb-5">
        <div class="custom-container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center text-lg-start">
                    <span class="badge bg-primary text-white mb-3 px-3 py-2 rounded-pill">Portal Pendaftaran Mandiri</span>
                    <h1 class="text-dark fw-bolder fs-10 mb-3">
                        Formulir Registrasi Kontingen
                    </h1>
                    <p class="fs-5 text-muted mb-0">
                        Event: <strong>{{ $registration->eventner->nama_event }}</strong> | Kategori: <strong class="text-primary">{{ $registration->competitionCategory->name }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- Banner End -->

    <section class="py-5">
        <div class="custom-container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    
                    @if(session()->has('success'))
                        <div class="alert alert-success d-flex align-items-center gap-3 shadow-sm rounded-4 border-0 mb-4 px-4 py-4">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="ti ti-check fs-6"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="fw-semibold mb-1 text-success">Berhasil!</h5>
                                <p class="mb-0">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Notification Status Verifikasi --}}
                    @if($registration->status_berkas === 'Terverifikasi')
                        <div class="alert alert-success d-flex align-items-center gap-3 shadow-sm rounded-4 border-0 mb-4 px-4 py-4 bg-success-subtle">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="ti ti-discount-check fs-6"></i>
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-1 text-success">Pendaftaran Terverifikasi (ACC)</h5>
                                <p class="mb-0">Selamat! Berkas dan data pendaftaran Anda telah dinyatakan **LENGKAP & SAH** oleh Panitia. Sampai jumpa di hari perlombaan!</p>
                            </div>
                        </div>
                    @elseif($registration->status_berkas === 'Ditolak')
                        <div class="alert alert-danger d-flex align-items-center gap-3 shadow-sm rounded-4 border-0 mb-4 px-4 py-4 bg-danger-subtle">
                            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="ti ti-alert-triangle fs-6"></i>
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-1 text-danger">Revisi Diperlukan (Ditolak)</h5>
                                <p class="mb-0">Mohon maaf, terdapat berkas atau data yang tidak sesuai. Silakan perbaiki data Anda di bawah ini dan kirimkan kembali (Finalisasi) untuk diverifikasi ulang.</p>
                            </div>
                        </div>
                    @elseif($registration->is_finalized)
                        <div class="alert alert-info d-flex align-items-center gap-3 shadow-sm rounded-4 border-0 mb-4 px-4 py-4 bg-info-subtle">
                            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="ti ti-clock fs-6"></i>
                            </div>
                            <div>
                                <h5 class="fw-semibold mb-1 text-info">Menunggu Verifikasi Panitia</h5>
                                <p class="mb-0">Data Anda sudah terkunci dan sedang dalam antrean peninjauan oleh Panitia. Harap cek halaman ini secara berkala untuk meilhat status verifikasi.</p>
                            </div>
                        </div>
                    @endif

                    <!-- School Identity Card -->
                    <div class="card shadow-md border-0 rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h4 class="fs-5 fw-semibold mb-3 border-bottom pb-2">Identitas Sekolah</h4>
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <span class="text-muted fs-3 d-block">Nama Kontingen/Sekolah</span>
                                    <h5 class="fw-bold mb-0">{{ $registration->nama_sekolah }}</h5>
                                </div>
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <span class="text-muted fs-3 d-block">NPSN</span>
                                    <h6 class="fw-semibold mb-0">{{ $registration->npsn }}</h6>
                                </div>
                            </div>
                            <div class="row align-items-center mt-3 pt-3 border-top">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <span class="text-muted fs-3 d-block">Nama Pelatih</span>
                                    <h6 class="fw-semibold mb-0">{{ $registration->nama_pelatih }}</h6>
                                </div>
                                <div class="col-md-6 mb-0">
                                    <span class="text-muted fs-3 d-block">Nomor HP / WhatsApp</span>
                                    <h6 class="fw-semibold mb-0">{{ $registration->no_hp }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Start --}}
                    {{-- Form dikunci jika is_finalized=true (kecuali status ditolak) ATAU jika status=Terverifikasi --}}
                    @php
                        $isLocked = ($registration->is_finalized && $registration->status_berkas !== 'Ditolak') || $registration->status_berkas === 'Terverifikasi';
                    @endphp

                    <fieldset {{ $isLocked ? 'disabled' : '' }}>
                        
                        <!-- Pelatih Section -->
                        <div class="card shadow-md border-0 rounded-4 mb-4">
                            <div class="card-body p-4">
                                <h4 class="fs-5 fw-semibold mb-3 border-bottom pb-2">Data Pelatih / Pembina</h4>
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <span class="text-muted fs-3 d-block">Nama Pelatih</span>
                                        <h5 class="fw-bold mb-0">{{ $registration->nama_pelatih }}</h5>
                                        <span class="text-muted fs-2">HP: {{ $registration->no_hp }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Unggah Foto Pelatih (.png/.jpg)</label>
                                        <input type="file" class="form-control" wire:model="fotoPelatih" accept="image/*">
                                        <div wire:loading wire:target="fotoPelatih" class="text-primary mt-2 fs-2"><i class="ti ti-loader"></i> Mengunggah...</div>
                                        @error('fotoPelatih') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        @if($registration->foto_pelatih)
                                            <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check"></i> Foto pelatih sudah diunggah.</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form wire:submit.prevent="submit(false)">
                            <!-- Documents Upload -->
                            <div class="card shadow-md border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <h4 class="fs-5 fw-semibold mb-3 border-bottom pb-2">Berkas Persyaratan Kontingen</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Unggah Logo Sekolah (.png/.jpg)</label>
                                            <input type="file" class="form-control" wire:model="logoSekolah" accept="image/*">
                                            @error('logoSekolah') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                            @if($registration->logo_sekolah)
                                                <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check"></i> Logo sudah diunggah.</span>
                                            @endif
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Surat Tugas / Rekomendasi (.pdf/.jpg)</label>
                                            <input type="file" class="form-control" wire:model="suratTugas">
                                            @error('suratTugas') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                            @if($registration->surat_tugas)
                                                <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check"></i> Surat tugas sudah diunggah.</span>
                                            @endif
                                        </div>
                                        <div class="col-md-12 mt-2">
                                            <div class="bg-primary-subtle p-3 rounded-3 border border-primary border-opacity-25">
                                                <label class="form-label fw-bold text-primary mb-2"><i class="ti ti-receipt me-1"></i> Kwitansi Pendaftaran (.png/.jpg)</label>
                                                <input type="file" class="form-control border-primary" wire:model="buktiPendaftaran" accept="image/*">
                                                <small class="text-muted d-block mt-1">Silakan unggah foto bukti pembayaran pendaftaran kontingen Anda.</small>
                                                @error('buktiPendaftaran') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                                @if($registration->bukti_pendaftaran)
                                                    <span class="text-success fs-2 mt-1 d-block fw-semibold"><i class="ti ti-check"></i> Kwitansi pendaftaran sudah diunggah.</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Participants List -->
                            <div class="card shadow-md border-0 rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
                                        <h4 class="fs-5 fw-semibold mb-0">Daftar Komandan & Anggota Peserta</h4>
                                        @if(!$isLocked)
                                            <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" wire:click="addParticipant">
                                                <i class="ti ti-plus"></i> Tambah Anggota
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Bagian Danton -->
                                    <div class="row align-items-start mb-4 pb-4 border-bottom bg-light-primary rounded rounded-4 p-3 mx-0">
                                        <h5 class="fw-bold text-primary mb-3"><i class="ti ti-star"></i> Komandan Pleton (Danton)</h5>
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">Nama Lengkap Danton</label>
                                            <input type="text" class="form-control border-primary" wire:model="dantonNama" placeholder="Ketik nama Danton...">
                                            @error('dantonNama') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-3 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">NISN Danton</label>
                                            <input type="text" class="form-control border-primary" wire:model="dantonNisn" placeholder="NISN...">
                                            @error('dantonNisn') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-5 mb-3 mb-md-0">
                                            <label class="form-label fw-semibold">Unggah Pasfoto (Berwarna)</label>
                                            <input type="file" class="form-control form-control-sm border-primary" wire:model="dantonFoto" accept="image/*">
                                            @error('dantonFoto') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                            @if($registration->danton_foto)
                                                <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check"></i> Foto Danton ada.</span>
                                            @endif
                                        </div>
                                    </div>

                                    <h5 class="fw-semibold mt-4 mb-3 ps-2">Anggota Pasukan</h5>
                                    @foreach($participants as $index => $participant)
                                        <div class="row align-items-start mb-4 pb-4 border-bottom {{ $loop->last ? 'border-0 mb-0 pb-0' : '' }}">
                                            <div class="col-md-4 mb-3 mb-md-0">
                                                <label class="form-label fw-semibold">Nama Peserta #{{ $index + 1 }}</label>
                                                <input type="text" class="form-control" wire:model="participants.{{ $index }}.nama" placeholder="Nama lengkap...">
                                                @error('participants.'.$index.'.nama') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3 mb-3 mb-md-0">
                                                <label class="form-label fw-semibold">NISN</label>
                                                <input type="text" class="form-control" wire:model="participants.{{ $index }}.nisn" placeholder="NISN...">
                                            </div>
                                            <div class="col-md-4 mb-3 mb-md-0">
                                                <label class="form-label fw-semibold">Pasfoto</label>
                                                <input type="file" class="form-control form-control-sm" wire:model="participants.{{ $index }}.foto" accept="image/*">
                                                @if(isset($participant['existing_foto']) && $participant['existing_foto'])
                                                    <span class="text-success fs-2 mt-1 d-block"><i class="ti ti-check"></i> Foto sudah ada.</span>
                                                @endif
                                            </div>
                                            <div class="col-md-1 text-end mt-4 pt-1">
                                                @if(count($participants) > 1 && !$isLocked)
                                                    <button type="button" class="btn btn-sm btn-light-danger text-danger round-40 p-0 rounded-circle d-flex align-items-center justify-content-center" wire:click="removeParticipant({{ $index }})">
                                                        <i class="ti ti-trash fs-5"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if(!$isLocked)
                                <!-- Action Buttons -->
                                <div class="text-center mt-5 d-flex flex-column flex-md-row justify-content-center gap-3">
                                    <button type="submit" class="btn btn-outline-primary btn-lg px-5 shadow-sm rounded-pill fw-semibold">
                                        <i class="ti ti-device-floppy me-2"></i> Simpan Draft
                                    </button>
                                    
                                    <button type="button" 
                                            class="btn btn-primary btn-lg px-5 shadow-md rounded-pill fw-semibold"
                                            onclick="confirmFinalization()">
                                        <i class="ti ti-send me-2"></i> Kirim & Finalisasi Data
                                    </button>
                                </div>
                                <p class="text-muted mt-3 fs-2 text-center">Pastikan semua berkas dan data anggota sudah lengkap sebelum melakukan Finalisasi.</p>
                            @else
                                <div class="text-center mt-5">
                                    @if($registration->status_berkas === 'Terverifikasi')
                                        <div class="d-inline-flex align-items-center gap-2 bg-success text-white px-5 py-3 rounded-pill fw-bold border-0 shadow-sm">
                                            <i class="ti ti-award fs-6"></i> TERVERIFIKASI PANITIA
                                        </div>
                                    @else
                                        <div class="d-inline-flex align-items-center gap-2 bg-info-subtle text-info px-5 py-3 rounded-pill fw-bold border border-info">
                                            <i class="ti ti-clock fs-6"></i> MENUNGGU VERIFIKASI
                                        </div>
                                    @endif
                                    <p class="text-muted mt-3 fs-3">Pendaftaran Anda telah resmi diterima dan tidak dapat diubah.</p>
                                </div>
                            @endif
                        </form>
                    </fieldset>

                </div>
            </div>
        </div>
    </section>

    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmFinalization() {
            Swal.fire({
                title: 'Konfirmasi Finalisasi?',
                text: "Apakah Anda yakin data ini sudah BENAR? Setelah dikirim data TIDAK DAPAT diubah kembali.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#5d87ff',
                cancelButtonColor: '#fa896b',
                confirmButtonText: 'Ya, Kirim Sekarang!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary px-4 py-2 rounded-pill fw-semibold border-0',
                    cancelButton: 'btn btn-light-danger px-4 py-2 rounded-pill fw-semibold border-0 ms-2'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('submit', true);
                }
            })
        }
    </script>
</div>
