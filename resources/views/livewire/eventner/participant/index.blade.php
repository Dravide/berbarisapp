<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Daftar Rekapitulasi Peserta</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Peserta</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- ===== SUMMARY STATS CARDS ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
            <div class="card border-start border-primary border-3 shadow-none">
                <div class="card-body px-3 py-3 d-flex align-items-center gap-3">
                    <span class="rounded-2 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width:44px;height:44px;">
                        <i class="ti ti-school fs-6"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-bold fs-5">{{ $summary['total_registrations'] }}</h5>
                        <span class="text-muted" style="font-size:12px;">Total Kontingen</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card border-start border-info border-3 shadow-none">
                <div class="card-body px-3 py-3 d-flex align-items-center gap-3">
                    <span class="rounded-2 d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info" style="width:44px;height:44px;">
                        <i class="ti ti-users fs-6"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-bold fs-5">{{ $summary['total_anggota'] }}</h5>
                        <span class="text-muted" style="font-size:12px;">Total Anggota</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card border-start border-warning border-3 shadow-none">
                <div class="card-body px-3 py-3 d-flex align-items-center gap-3">
                    <span class="rounded-2 d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning" style="width:44px;height:44px;">
                        <i class="ti ti-clock fs-6"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-bold fs-5">{{ $summary['booking'] }}</h5>
                        <span class="text-muted" style="font-size:12px;">Booking</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card border-start border-success border-3 shadow-none">
                <div class="card-body px-3 py-3 d-flex align-items-center gap-3">
                    <span class="rounded-2 d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success" style="width:44px;height:44px;">
                        <i class="ti ti-circle-check fs-6"></i>
                    </span>
                    <div>
                        <h5 class="mb-0 fw-bold fs-5">{{ $summary['verified'] }}</h5>
                        <span class="text-muted" style="font-size:12px;">Terverifikasi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-semibold mb-0">Kelola Sekolah Pendaftar</h5>
                <button wire:click="openModal('{{ $activeTab }}')" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="ti ti-plus"></i> Tambah Pendaftar
                </button>
            </div>

            <!-- Kategori Select + Pencarian -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div class="input-group" style="max-width: 380px;">
                    <span class="input-group-text bg-primary text-white"><i class="ti ti-category"></i></span>
                    <select class="form-select" wire:model.live="activeTab">
                        @forelse ($categories as $category)
                            @php $tabLabel = !empty($category['parent']) ? $category['parent']['name'] . ' — ' . $category['name'] : $category['name']; @endphp
                            <option value="{{ $category['id'] }}">{{ $tabLabel }}</option>
                        @empty
                            <option value="">Belum ada Kategori Lomba</option>
                        @endforelse
                    </select>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <div class="input-group" style="width: 260px;">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" placeholder="Cari sekolah / NPSN / pelatih..." wire:model.live.debounce.300ms="search">
                        @if($search !== '')
                            <button class="btn btn-outline-secondary" type="button" wire:click="$set('search', '')" title="Hapus pencarian">
                                <i class="ti ti-x"></i>
                            </button>
                        @endif
                    </div>
                    <div class="input-group" style="width: 210px;">
                        <span class="input-group-text bg-warning text-white"><i class="ti ti-filter"></i></span>
                        <select class="form-select" wire:model.live="statusFilter" title="Filter status">
                            <option value="all">Semua Status</option>
                            <option value="draft">Draft (Belum Final)</option>
                            <option value="finalized">Finalized</option>
                            <option value="booking">Booking</option>
                            <option value="menunggu">Menunggu Verifikasi</option>
                            <option value="terverifikasi">Terverifikasi</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    <a href="{{ route('eventner.participants.qr-batch', ['category_id' => $activeTab]) }}" target="_blank" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-1" title="Cetak QR Semua Peserta">
                        <i class="ti ti-qrcode fs-4"></i> QR
                    </a>
                    <span class="badge bg-light text-dark d-flex align-items-center px-3">{{ $registrations->count() }} peserta</span>
                    <button wire:click="openModal(activeTab)" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus me-1"></i> Tambah
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <div class="tab-pane active" role="tabpanel">
                    @if($registrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table text-nowrap align-middle mb-0">
                            <thead class="text-dark fs-4 bg-light">
                                <tr>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">#</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Sekolah</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Pelatih / Kontak</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Status</h6></th>
                                    <th class="border-bottom-0"><h6 class="fw-semibold mb-0">Data</h6></th>
                                    <th class="border-bottom-0 text-center"><h6 class="fw-semibold mb-0">Aksi</h6></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($registrations as $idx => $reg)
                                    <tr>
                                        <td>{{ $idx + 1 }}</td>
                                        <td>
                                            <h6 class="fw-semibold mb-1">{{ $reg->display_name }}</h6>
                                            <span class="fw-normal text-muted">NPSN: {{ $reg->npsn }}</span>
                                        </td>
                                        <td>
                                            <p class="mb-0 fw-normal">{{ $reg->nama_pelatih }}</p>
                                            <span class="badge bg-light-success text-success mt-1"><i class="ti ti-phone"></i> {{ $reg->no_hp }}</span>
                                        </td>
                                        <td>
                                            @if($reg->status_berkas === 'booking')
                                                <span class="badge bg-secondary-subtle text-secondary rounded-3 fw-semibold"><i class="ti ti-clock me-1"></i>Booking</span>
                                            @elseif($reg->status_berkas === 'confirmed' || $reg->status_berkas === 'Menunggu')
                                                <span class="badge bg-warning rounded-3 fw-semibold">Menunggu Verifikasi</span>
                                            @elseif($reg->status_berkas === 'Terverifikasi')
                                                <span class="badge bg-success rounded-3 fw-semibold">Terverifikasi</span>
                                            @elseif($reg->status_berkas === 'dibatalkan')
                                                <span class="badge bg-dark rounded-3 fw-semibold">Dibatalkan</span>
                                            @elseif($reg->status_berkas === 'Ditolak')
                                                <span class="badge bg-danger rounded-3 fw-semibold">Ditolak</span>
                                            @else
                                                <span class="badge bg-light text-muted rounded-3 fw-semibold">{{ $reg->status_berkas }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-light-info text-info">
                                                    {{ $reg->danton_nama ? '1 Danton' : '0 Danton' }} + {{ $reg->participants->count() }} Anggota
                                                </span>
                                                @if($reg->is_finalized)
                                                    <span class="badge bg-info text-white"><i class="ti ti-lock"></i> Finalized</span>
                                                @else
                                                    <span class="badge bg-light-secondary text-secondary">Draft</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                {{-- Tombol Verifikasi --}}
                                                <button wire:click="openVerifyModal({{ $reg->id }})" class="btn btn-sm btn-info text-white" title="Verifikasi Data & Berkas">
                                                    <i class="ti ti-checklist fs-4"></i> Verifikasi
                                                </button>

                                                @if($reg->status_berkas === 'Terverifikasi')
                                                    <a href="{{ route('eventner.participants.pdf', $reg->id) }}" target="_blank" class="btn btn-sm btn-success text-white" title="Lihat Formulir PDF">
                                                        <i class="ti ti-download fs-4"></i> Formulir
                                                    </a>
                                                @endif

                                                @if($reg->payment_status === 'paid')
                                                    <a href="{{ route('eventner.participants.invoice', $reg->id) }}" target="_blank" class="btn btn-sm btn-outline-success text-success" title="Unduh Invoice PDF">
                                                        <i class="ti ti-receipt fs-4"></i> Invoice
                                                    </a>
                                                @endif

                                                <a href="{{ route('eventner.participants.qr', $reg->id) }}" target="_blank" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-1" title="Cetak QR">
                                                    <i class="ti ti-qrcode fs-4"></i>
                                                </a>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                    onclick="navigator.clipboard.writeText('{{ url('/reg/' . $reg->magic_token) }}'); alert('Magic Link berhasil disalin!\n\n{{ url('/reg/' . $reg->magic_token) }}');"
                                                    title="Salin Magic Link"
                                                >
                                                    <i class="ti ti-link fs-4"></i>
                                                </button>
                                                <a href="{{ url('/reg/' . $reg->magic_token) }}" target="_blank" class="btn btn-sm btn-light-primary text-primary" title="Preview Portal">
                                                    <i class="ti ti-external-link fs-4"></i>
                                                </a>
                                                <button wire:click="edit({{ $reg->id }})" class="btn btn-sm btn-warning" title="Edit Identitas">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button wire:click="delete({{ $reg->id }})" wire:confirm="Yakin ingin menghapus pendaftar ini?" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="ti ti-trash fs-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-users-minus fs-10 text-muted"></i>
                            <p class="text-muted mt-3 mb-0">Belum ada sekolah yang mendaftar di kategori ini.</p>
                            <button wire:click="openModal('{{ $activeTab }}')" class="btn btn-sm btn-outline-primary mt-3">
                                <i class="ti ti-plus"></i> Tambahkan Sekarang
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Pendaftar -->
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">{{ $editId ? 'Edit' : 'Tambah' }} Pendaftar</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kategori Lomba <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="competition_category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $cat)
                                    @php $label = $cat['parent_id'] ? \App\Models\CompetitionCategory::find($cat['parent_id'])?->name . ' — ' . $cat['name'] : $cat['name']; @endphp
                                    <option value="{{ $cat['id'] }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('competition_category_id') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NPSN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="npsn" placeholder="Nomor Pokok Sekolah Nasional" required>
                                @error('npsn') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Sekolah <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="nama_sekolah" placeholder="Nama sekolah sesuai data resmi" required>
                                @error('nama_sekolah') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="no_hp" placeholder="08xxxxxxxxxx" required>
                                @error('no_hp') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Sekolah</label>
                                <input type="email" class="form-control" wire:model="school_email" placeholder="Alamat email sekolah">
                                @error('school_email') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Pelatih</label>
                                <input type="text" class="form-control" wire:model="nama_pelatih" placeholder="Nama pelatih / pembina">
                                @error('nama_pelatih') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            @if(!$editId)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jumlah Pasukan <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" wire:model="jumlah_pasukan" min="1" max="20" placeholder="Jumlah pasukan yang didaftarkan" required>
                                @error('jumlah_pasukan') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                                <small class="text-muted">Semua pasukan akan masuk dalam 1 Magic Link.</small>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="closeModal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> {{ $editId ? 'Simpan Perubahan' : 'Simpan & Buat Magic Link' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Verifikasi Detail -->
    @if($showVerifyModal && $selectedRegistration)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.6); z-index: 1050;">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 rounded-4">
                {{-- Header --}}
                <div class="modal-header bg-primary text-white rounded-top-4 p-4 border-bottom-0">
                    <div>
                        <h4 class="modal-title fw-bold mb-1 text-white">
                            <i class="ti ti-checklist me-2"></i>Verifikasi Pendaftaran
                        </h4>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold fs-4">{{ $selectedRegistration->display_name }}</span>
                            <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1">{{ $selectedRegistration->competitionCategory->full_name }}</span>
                            @if($selectedRegistration->is_finalized)
                                <span class="badge bg-white bg-opacity-25 text-white rounded-pill px-3 py-1"><i class="ti ti-lock me-1"></i>Finalized</span>
                            @else
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1"><i class="ti ti-alert-circle me-1"></i>Belum Final</span>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeVerifyModal"></button>
                </div>

                {{-- Body: Checklist-style verification --}}
                <div class="modal-body p-0">
                    @if(!$selectedRegistration->is_finalized)
                        <div class="alert alert-warning m-4 mb-0 rounded-3 border-0 d-flex align-items-center gap-3 shadow-sm">
                            <i class="ti ti-alert-triangle fs-8 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Data Belum Difinalisasi</h6>
                                <p class="mb-0 small">Sekolah belum menekan tombol "Finalisasi" pada portal. Data mungkin masih berubah.</p>
                            </div>
                        </div>
                    @endif

                    {{-- Row 1: Berkas Checklist --}}
                    <div class="p-4 pb-0">
                        <h5 class="fw-bold mb-3 text-uppercase small text-primary"><i class="ti ti-file-check me-1"></i> Ceklis Berkas Persyaratan</h5>
                    </div>
                    <div class="row g-0 px-4">
                        {{-- Logo Sekolah --}}
                        <div class="col-md-4 p-3 border-end border-bottom">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($selectedRegistration->logo_sekolah)
                                        <img src="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" class="rounded-3 border" style="width:60px;height:60px;object-fit:cover;">
                                    @else
                                        <div class="bg-danger bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center border border-danger border-opacity-25" style="width:60px;height:60px;">
                                            <i class="ti ti-photo-off text-danger fs-4"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">Logo Sekolah</h6>
                                    @if($selectedRegistration->logo_sekolah)
                                        <a href="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" target="_blank" class="small text-primary"><i class="ti ti-external-link"></i> Lihat</a>
                                        <span class="badge bg-success-subtle text-success ms-2"><i class="ti ti-check"></i></span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger mt-1"><i class="ti ti-x"></i> Belum diunggah</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Surat Tugas --}}
                        <div class="col-md-4 p-3 border-end border-bottom">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($selectedRegistration->surat_tugas)
                                        <div class="bg-success bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center border border-success border-opacity-25" style="width:60px;height:60px;">
                                            <i class="ti ti-file-text text-success fs-4"></i>
                                        </div>
                                    @else
                                        <div class="rounded-3 d-flex align-items-center justify-content-center border {{ $selectedRegistration->eventner->surat_tugas_required ? 'bg-danger bg-opacity-10 border-danger border-opacity-25' : 'bg-light text-muted' }}" style="width:60px;height:60px;">
                                            <i class="ti ti-file-off fs-4 {{ $selectedRegistration->eventner->surat_tugas_required ? 'text-danger' : 'text-muted' }}"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">Surat Tugas</h6>
                                    @if($selectedRegistration->surat_tugas)
                                        <a href="{{ asset('storage/' . $selectedRegistration->surat_tugas) }}" target="_blank" class="small text-primary"><i class="ti ti-external-link"></i> Lihat</a>
                                        <span class="badge bg-success-subtle text-success ms-2"><i class="ti ti-check"></i></span>
                                    @else
                                        @if($selectedRegistration->eventner->surat_tugas_required)
                                            <span class="badge bg-danger-subtle text-danger mt-1"><i class="ti ti-x"></i> Wajib, belum ada</span>
                                        @else
                                            <span class="text-muted small mt-1">Tidak wajib</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Kwitansi --}}
                        <div class="col-md-4 p-3 border-bottom">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0">
                                    @if($selectedRegistration->bukti_pendaftaran || $selectedRegistration->payment_proof)
                                        <img src="{{ asset('storage/' . ($selectedRegistration->bukti_pendaftaran ?: $selectedRegistration->payment_proof)) }}" class="rounded-3 border" style="width:60px;height:60px;object-fit:cover;">
                                    @else
                                        <div class="rounded-3 d-flex align-items-center justify-content-center border {{ $selectedRegistration->eventner->kwitansi_required ? 'bg-danger bg-opacity-10 border-danger border-opacity-25' : 'bg-light text-muted' }}" style="width:60px;height:60px;">
                                            <i class="ti ti-receipt-off fs-4 {{ $selectedRegistration->eventner->kwitansi_required ? 'text-danger' : 'text-muted' }}"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">Kwitansi</h6>
                                    @if($selectedRegistration->bukti_pendaftaran || $selectedRegistration->payment_proof)
                                        <a href="{{ asset('storage/' . ($selectedRegistration->bukti_pendaftaran ?: $selectedRegistration->payment_proof)) }}" target="_blank" class="small text-primary"><i class="ti ti-external-link"></i> Lihat</a>
                                        <span class="badge bg-success-subtle text-success ms-2"><i class="ti ti-check"></i></span>
                                    @else
                                        @if($selectedRegistration->eventner->kwitansi_required)
                                            <span class="badge bg-danger-subtle text-danger mt-1"><i class="ti ti-x"></i> Wajib, belum ada</span>
                                        @else
                                            <span class="text-muted small mt-1">Tidak wajib</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Pelatih & Danton --}}
                    <div class="p-4 pb-0">
                        <h5 class="fw-bold mb-3 text-uppercase small text-primary"><i class="ti ti-user-check me-1"></i> Data Pelatih & Danton</h5>
                    </div>
                    <div class="row g-0 px-4">
                        <div class="col-md-6 p-3 border-end border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                @if($selectedRegistration->foto_pelatih)
                                    <img src="{{ asset('storage/' . $selectedRegistration->foto_pelatih) }}" class="rounded-circle border" style="width:56px;height:56px;object-fit:cover;">
                                @else
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center border border-warning border-opacity-25" style="width:56px;height:56px;">
                                        <i class="ti ti-user-off text-warning fs-5"></i>
                                    </div>
                                @endif
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $selectedRegistration->nama_pelatih ?: '---' }}</h6>
                                    <small class="text-muted"><i class="ti ti-phone me-1"></i>{{ $selectedRegistration->no_hp }}</small>
                                    @if(!$selectedRegistration->foto_pelatih)
                                        <span class="badge bg-warning-subtle text-warning ms-2"><i class="ti ti-alert-circle"></i> Foto belum ada</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 p-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                @if($selectedRegistration->danton_foto)
                                    <img src="{{ asset('storage/' . $selectedRegistration->danton_foto) }}" class="rounded-3 border" style="width:44px;height:56px;object-fit:cover;">
                                @else
                                    <div class="bg-warning bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center border border-warning border-opacity-25" style="width:44px;height:56px;">
                                        <i class="ti ti-user-off text-warning fs-5"></i>
                                    </div>
                                @endif
                                <div>
                                    <h6 class="fw-bold mb-1">{{ $selectedRegistration->danton_nama ?: '---' }}</h6>
                                    <small class="text-muted">NISN: {{ $selectedRegistration->danton_nisn ?: '-' }}</small>
                                    @if(!$selectedRegistration->danton_foto || !$selectedRegistration->danton_nama)
                                        <span class="badge bg-warning-subtle text-warning ms-2"><i class="ti ti-alert-circle"></i> Belum lengkap</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 3: Anggota Pasukan --}}
                    <div class="p-4 pb-0">
                        <h5 class="fw-bold mb-3 text-uppercase small text-primary"><i class="ti ti-users-group me-1"></i> Anggota Pasukan
                            <span class="badge bg-primary-subtle text-primary ms-2 fs-2">{{ $selectedRegistration->participants->count() }} orang</span>
                        </h5>
                    </div>
                    <div class="p-4 pt-2">
                        @if($selectedRegistration->participants->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 border">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3" style="width:40px;">No</th>
                                            <th style="width:60px;">Foto</th>
                                            <th>Nama</th>
                                            <th>NISN</th>
                                            <th class="text-center" style="width:80px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedRegistration->participants as $idx => $p)
                                            <tr>
                                                <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                                <td>
                                                    @if($p->foto)
                                                        <img src="{{ asset('storage/' . $p->foto) }}" class="rounded-2 border" style="width:36px;height:44px;object-fit:cover;">
                                                    @else
                                                        <div class="bg-warning bg-opacity-10 rounded-2 d-flex align-items-center justify-content-center border border-warning border-opacity-25" style="width:36px;height:44px;">
                                                            <i class="ti ti-user-off text-warning fs-6"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="fw-semibold">{{ $p->nama }}</td>
                                                <td class="text-muted small">{{ $p->nisn ?: '-' }}</td>
                                                <td class="text-center">
                                                    @if($p->foto)
                                                        <span class="badge bg-success-subtle text-success"><i class="ti ti-check"></i></span>
                                                    @else
                                                        <span class="badge bg-warning-subtle text-warning"><i class="ti ti-photo-off"></i></span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 bg-light rounded-3 border border-dashed text-muted">
                                <i class="ti ti-users-off fs-8 d-block mb-2"></i>
                                <span class="fw-semibold">Belum ada data anggota pasukan.</span>
                            </div>
                        @endif
                    </div>

                    {{-- Verdict Summary --}}
                    <div class="p-4 pt-0">
                        @php
                            $checkCount = 0; $totalChecks = 0;
                            // Logo
                            $totalChecks++; if($selectedRegistration->logo_sekolah) $checkCount++;
                            // Surat tugas
                            if($selectedRegistration->eventner->surat_tugas_required) { $totalChecks++; if($selectedRegistration->surat_tugas) $checkCount++; }
                            // Kwitansi
                            if($selectedRegistration->eventner->kwitansi_required) { $totalChecks++; if($selectedRegistration->bukti_pendaftaran || $selectedRegistration->payment_proof) $checkCount++; }
                            // Pelatih
                            $totalChecks++; if($selectedRegistration->nama_pelatih) $checkCount++;
                            // Danton
                            $totalChecks++; if($selectedRegistration->danton_nama) $checkCount++;
                            // Anggota
                            if($selectedRegistration->participants->count() > 0) { $totalChecks++; $checkCount++; }
                            $pct = $totalChecks > 0 ? round($checkCount / $totalChecks * 100) : 0;
                            $verdictColor = $pct == 100 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
                        @endphp
                        <div class="d-flex align-items-center justify-content-between bg-{{ $verdictColor }}-subtle rounded-3 p-3 border border-{{ $verdictColor }} border-opacity-25">
                            <div>
                                <span class="fw-bold text-{{ $verdictColor }}"><i class="ti ti-clipboard-check me-1"></i>Kelengkapan Berkas:</span>
                                <span class="ms-2 fw-bold">{{ $checkCount }}/{{ $totalChecks }} item ({{ $pct }}%)</span>
                            </div>
                            <div class="progress flex-grow-1 mx-3" style="height:8px;max-width:200px;">
                                <div class="progress-bar bg-{{ $verdictColor }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="modal-footer bg-light border-top rounded-bottom-4 p-4 justify-content-between">
                    <div>
                        <span class="text-muted small">Status Saat Ini:</span>
                        @if($selectedRegistration->status_berkas === 'booking')
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 ms-2 fw-bold"><i class="ti ti-clock me-1"></i>Booking</span>
                        @elseif($selectedRegistration->status_berkas === 'confirmed' || $selectedRegistration->status_berkas === 'Menunggu')
                            <span class="badge bg-warning rounded-pill px-3 py-1 ms-2 fw-bold">Menunggu Verifikasi</span>
                        @elseif($selectedRegistration->status_berkas === 'Terverifikasi')
                            <span class="badge bg-success rounded-pill px-3 py-1 ms-2 fw-bold">Sudah ACC</span>
                        @elseif($selectedRegistration->status_berkas === 'dibatalkan')
                            <span class="badge bg-dark rounded-pill px-3 py-1 ms-2 fw-bold">Dibatalkan</span>
                        @elseif($selectedRegistration->status_berkas === 'Ditolak')
                            <span class="badge bg-danger rounded-pill px-3 py-1 ms-2 fw-bold">Ditolak</span>
                        @else
                            <span class="badge bg-light text-muted rounded-pill px-3 py-1 ms-2 fw-bold">{{ $selectedRegistration->status_berkas }}</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light px-4 rounded-pill fw-semibold" wire:click="closeVerifyModal">
                            <i class="ti ti-x me-1"></i>Tutup
                        </button>
                        <button type="button" class="btn btn-danger px-4 rounded-pill fw-semibold" wire:click="verifyStatus('Ditolak')" wire:confirm="Yakin ingin MENOLAK berkas pendaftaran ini?" @if(!$selectedRegistration->is_finalized) disabled title="Data masih draft" @endif>
                            <i class="ti ti-x me-1"></i> Tolak
                        </button>
                        <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold shadow-sm" wire:click="verifyStatus('Terverifikasi')" wire:confirm="Nyatakan semua data & berkas sudah LENGKAP dan BENAR?" @if(!$selectedRegistration->is_finalized) disabled title="Data masih draft" @endif>
                            <i class="ti ti-check me-1"></i> Verifikasi / ACC
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
