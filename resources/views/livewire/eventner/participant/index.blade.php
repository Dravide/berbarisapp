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

            <!-- Tabs -->
            <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                @forelse ($categories as $category)
                    <li class="nav-item" role="presentation">
                        <button 
                            class="nav-link {{ $activeTab == $category['id'] ? 'active bg-primary text-white' : '' }}" 
                            wire:click="switchTab('{{ $category['id'] }}')"
                            type="button" 
                            role="tab"
                        >
                            <i class="ti ti-medal me-2"></i> {{ $category['name'] }}
                        </button>
                    </li>
                @empty
                    <li class="nav-item">
                        <span class="nav-link text-muted">Belum ada Kategori Lomba. <a href="{{ route('eventner.competition-categories.index') }}">Tambahkan di sini</a>.</span>
                    </li>
                @endforelse
            </ul>

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
                                            <h6 class="fw-semibold mb-1">{{ $reg->nama_sekolah }}</h6>
                                            <span class="fw-normal text-muted">NPSN: {{ $reg->npsn }}</span>
                                        </td>
                                        <td>
                                            <p class="mb-0 fw-normal">{{ $reg->nama_pelatih }}</p>
                                            <span class="badge bg-light-success text-success mt-1"><i class="ti ti-phone"></i> {{ $reg->no_hp }}</span>
                                        </td>
                                        <td>
                                            @if($reg->status_berkas === 'Menunggu')
                                                <span class="badge bg-warning rounded-3 fw-semibold">Menunggu</span>
                                            @elseif($reg->status_berkas === 'Terverifikasi')
                                                <span class="badge bg-success rounded-3 fw-semibold">Terverifikasi</span>
                                            @else
                                                <span class="badge bg-danger rounded-3 fw-semibold">Ditolak</span>
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
                                    <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                @endforeach
                            </select>
                            @error('competition_category_id') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Sekolah / Kontingen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="nama_sekolah" placeholder="Cth: SMP Negeri 1 Cianjur" required>
                            @error('nama_sekolah') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">NPSN <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="npsn" placeholder="Cth: 20202021" required>
                            @error('npsn') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Pelatih / Pembina <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="nama_pelatih" placeholder="Cth: Budi Santoso" required>
                            @error('nama_pelatih') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">No. HP / WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="no_hp" placeholder="Cth: 081234567890" required>
                            @error('no_hp') <span class="text-danger fs-2">{{ $message }}</span> @enderror
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
                <div class="modal-header bg-light border-bottom-0 rounded-top-4 p-4">
                    <div>
                        <h4 class="modal-title fw-bold mb-1">Verifikasi Pendaftaran</h4>
                        <p class="text-muted mb-0">{{ $selectedRegistration->nama_sekolah }} | <span class="badge bg-primary-subtle text-primary">{{ $selectedRegistration->competitionCategory->name }}</span></p>
                    </div>
                    <button type="button" class="btn-close" wire:click="closeVerifyModal"></button>
                </div>
                <div class="modal-body p-4 bg-light-subtle">
                    
                    @if(!$selectedRegistration->is_finalized)
                        <div class="alert alert-warning mb-4 rounded-4 border-0 shadow-sm d-flex align-items-center gap-3">
                            <i class="ti ti-alert-circle fs-7"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Peringatan: Data Belum Final</h6>
                                <p class="mb-0 small">Sekolah ini belum menekan tombol "Finalisasi" pada portal mereka. Data mungkin masih berubah.</p>
                            </div>
                        </div>
                    @endif

                    <div class="row g-4">
                        <!-- Kolom Kiri: Berkas Utama -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 h-100 mb-0">
                                <div class="card-body p-4">
                                    <h5 class="fw-semibold mb-3 border-bottom pb-2 text-primary"><i class="ti ti-file-description"></i> Berkas Utama</h5>
                                    
                                    <div class="mb-4 text-center">
                                        <label class="d-block text-muted small mb-2 text-start fw-semibold">Logo Sekolah</label>
                                        @if($selectedRegistration->logo_sekolah)
                                            <a href="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $selectedRegistration->logo_sekolah) }}" class="img-fluid rounded-3 border p-2" style="max-height: 120px;" alt="Logo">
                                            </a>
                                        @else
                                            <div class="py-4 bg-light rounded-3 border border-dashed text-muted small">Belum diunggah</div>
                                        @endif
                                    </div>

                                    <div class="mb-4">
                                        <label class="d-block text-muted small mb-2 fw-semibold">Surat Tugas / Rekomendasi</label>
                                        @if($selectedRegistration->surat_tugas)
                                            <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 border">
                                                <span class="small text-truncate me-2"><i class="ti ti-file"></i> Surat_Tugas...</span>
                                                <a href="{{ asset('storage/' . $selectedRegistration->surat_tugas) }}" target="_blank" class="btn btn-sm btn-primary py-1 px-2"><i class="ti ti-download fs-2"></i></a>
                                            </div>
                                        @else
                                            <div class="p-2 bg-light rounded-3 border border-dashed text-center text-muted small">Belum diunggah</div>
                                        @endif
                                    </div>

                                    <div class="mb-0">
                                        <label class="d-block text-primary small mb-2 fw-bold"><i class="ti ti-receipt"></i> Kwitansi Pendaftaran</label>
                                        @if($selectedRegistration->bukti_pendaftaran)
                                            <a href="{{ asset('storage/' . $selectedRegistration->bukti_pendaftaran) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $selectedRegistration->bukti_pendaftaran) }}" class="img-fluid rounded-3 border p-1" alt="Kwitansi">
                                            </a>
                                        @else
                                            <div class="py-5 bg-warning-subtle text-warning rounded-3 border border-dashed text-center small fw-semibold">BELUM UNGGAH KWITANSI</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Tengah & Kanan: Pasukan -->
                        <div class="col-lg-8">
                            <!-- Data Pelatih & Danton -->
                            <div class="card border-0 shadow-sm rounded-4 mb-4">
                                <div class="card-body p-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 border-end">
                                            <h5 class="fw-semibold mb-3 text-primary small text-uppercase"><i class="ti ti-user-star"></i> Pelatih/Pembina</h5>
                                            <div class="d-flex align-items-center gap-3">
                                                @if($selectedRegistration->foto_pelatih)
                                                    <img src="{{ asset('storage/' . $selectedRegistration->foto_pelatih) }}" class="rounded-circle border" style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px;"><i class="ti ti-user text-muted"></i></div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $selectedRegistration->nama_pelatih }}</h6>
                                                    <small class="text-muted">{{ $selectedRegistration->no_hp }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 ps-md-4">
                                            <h5 class="fw-semibold mb-3 text-primary small text-uppercase"><i class="ti ti-star"></i> Komandan Pleton (Danton)</h5>
                                            <div class="d-flex align-items-center gap-3">
                                                @if($selectedRegistration->danton_foto)
                                                    <img src="{{ asset('storage/' . $selectedRegistration->danton_foto) }}" class="rounded-3 border" style="width: 50px; height: 65px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light rounded-3 d-flex align-items-center justify-content-center border" style="width: 50px; height: 65px;"><i class="ti ti-user text-muted"></i></div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $selectedRegistration->danton_nama ?: '--- belum diisi ---' }}</h6>
                                                    <small class="text-muted">NISN: {{ $selectedRegistration->danton_nisn ?: '-' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Daftar Anggota -->
                            <div class="card border-0 shadow-sm rounded-4 mb-0">
                                <div class="card-body p-4">
                                    <h5 class="fw-semibold mb-4 border-bottom pb-2 text-primary"><i class="ti ti-users"></i> Daftar Anggota ({{ $selectedRegistration->participants->count() }})</h5>
                                    
                                    @if($selectedRegistration->participants->count() > 0)
                                        <div class="row g-3">
                                            @foreach($selectedRegistration->participants as $p)
                                                <div class="col-md-6 col-xl-4">
                                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 bg-light-subtle">
                                                        @if($p->foto)
                                                            <img src="{{ asset('storage/' . $p->foto) }}" class="rounded-2" style="width: 40px; height: 50px; object-fit: cover;">
                                                        @else
                                                            <div class="bg-light rounded-2 border d-flex align-items-center justify-content-center" style="width: 40px; height: 50px;"><i class="ti ti-user text-muted fs-4"></i></div>
                                                        @endif
                                                        <div class="overflow-hidden">
                                                            <h6 class="mb-0 small fw-bold text-truncate">{{ $p->nama }}</h6>
                                                            <small class="text-muted fs-2 text-truncate d-block">NISN: {{ $p->nisn ?: '-' }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted small border border-dashed rounded-3">
                                            Belum ada data anggota peserta.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 rounded-bottom-4 p-4 justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">Status Saat Ini:</span>
                        @if($selectedRegistration->status_berkas === 'Menunggu')
                            <span class="badge bg-warning rounded-pill px-3 fw-bold">Menunggu Verifikasi</span>
                        @elseif($selectedRegistration->status_berkas === 'Terverifikasi')
                            <span class="badge bg-success rounded-pill px-3 fw-bold">Sudah ACC</span>
                        @else
                            <span class="badge bg-danger rounded-pill px-3 fw-bold">Status: Ditolak</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light px-4 py-2 rounded-pill fw-semibold" wire:click="closeVerifyModal">Tutup</button>
                        
                        <div class="vr mx-2"></div>

                        <button 
                            type="button" 
                            class="btn btn-danger px-4 py-2 rounded-pill fw-semibold shadow-sm"
                            wire:click="verifyStatus('Ditolak')"
                            wire:confirm="Yakin ingin MENOLAK berkas pendaftaran ini?"
                        >
                            <i class="ti ti-x me-1"></i> Tolak Berkas
                        </button>

                        <button 
                            type="button" 
                            class="btn btn-success px-4 py-2 rounded-pill fw-semibold shadow-md"
                            wire:click="verifyStatus('Terverifikasi')"
                            wire:confirm="Nyatakan semua data & berkas sudah LENGKAP dan BENAR?"
                        >
                            <i class="ti ti-check me-1"></i> Verifikasi / ACC
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
