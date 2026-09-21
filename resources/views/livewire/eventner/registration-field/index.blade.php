@php
    // Label tipe field — dipakai di tabel & modal, satu tempat saja.
    $tipeLabel = [
        'text' => 'Teks Singkat',
        'textarea' => 'Teks Panjang',
        'number' => 'Angka',
        'date' => 'Tanggal',
        'select' => 'Pilihan',
        'wilayah' => 'Wilayah (Provinsi/Kab/Kec)',
        'file' => 'Unggah Berkas',
        'image' => 'Unggah Gambar',
    ];

    $tipeIkon = [
        'text' => 'ti-forms',
        'textarea' => 'ti-align-left',
        'number' => 'ti-123',
        'date' => 'ti-calendar',
        'select' => 'ti-list',
        'wilayah' => 'ti-map-pin',
        'file' => 'ti-paperclip',
        'image' => 'ti-photo',
    ];
@endphp

<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Field Pendaftaran</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Field Pendaftaran</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="card w-100 position-relative overflow-hidden">
        <div class="card-body p-4 pb-2">
            <p class="mb-2 text-dark">
                Atur sendiri data apa saja yang diminta saat sekolah mendaftar &mdash; tambah pertanyaan baru,
                ubah label, atau matikan yang tidak dipakai. Field yang aktif langsung muncul di
                formulir pendaftaran, portal pendaftar, halaman panitia, cetakan PDF, dan sertifikat.
            </p>
            <p class="mb-0 fs-2 text-muted">
                Urutan di sini menentukan urutan tampil di formulir. Field bertanda
                <span class="badge bg-light-primary text-primary">Bawaan</span>
                berasal dari sistem &mdash; label &amp; wajibnya boleh diubah, tapi tidak bisa dihapus
                karena dipakai halaman publik (beranda, hasil, dan voting).
            </p>

            @if($this->emailFieldDisabled)
                <div class="alert alert-warning py-2 px-3 mt-3 mb-0 fs-2">
                    <i class="ti ti-alert-triangle"></i>
                    Field <strong>Email Penanggung Jawab</strong> sedang dimatikan, jadi tautan portal
                    pendaftaran tidak bisa dikirim otomatis ke sekolah. Sekolah tetap bisa membuka portalnya
                    dari tautan yang di-copy panitia di halaman Peserta.
                </div>
            @endif
        </div>
    </div>

    <div class="card w-100 position-relative overflow-hidden">
        <div class="card-header bg-primary d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h5 class="mb-0 text-white fw-semibold">Daftar Field</h5>
            <button type="button" class="btn btn-sm btn-light fw-semibold" wire:click="create">
                <i class="ti ti-plus"></i> Tambah Field
            </button>
        </div>
        <div class="card-body p-4">
            @if($this->fields->isEmpty())
                <div class="text-center py-5">
                    <h5 class="fw-semibold text-muted">Belum ada field pendaftaran</h5>
                    <p class="mb-3">Tambahkan field untuk meminta data tambahan dari sekolah, misalnya asal kabupaten atau berkas pendukung.</p>
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="ti ti-plus"></i> Tambah Field
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-0 border-0 fw-semibold text-dark" style="width: 70px;">Urutan</th>
                                <th class="border-0 fw-semibold text-dark">Field</th>
                                <th class="border-0 fw-semibold text-dark">Tipe</th>
                                <th class="border-0 fw-semibold text-dark text-center">Wajib</th>
                                <th class="border-0 fw-semibold text-dark text-center">Terisi</th>
                                <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->fields as $field)
                                <tr>
                                    <td class="ps-0 text-nowrap">
                                        <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted"
                                            wire:click="moveUp({{ $field->id }})" title="Naikkan urutan">
                                            <i class="ti ti-chevron-up fs-4"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted"
                                            wire:click="moveDown({{ $field->id }})" title="Turunkan urutan">
                                            <i class="ti ti-chevron-down fs-4"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <h6 class="fw-semibold mb-0 text-primary">
                                            {{ $field->label }}
                                            @if($field->is_builtin)
                                                <span class="badge bg-light-primary text-primary ms-1 fs-2">Bawaan</span>
                                            @endif
                                        </h6>
                                        <div class="fs-2">
                                            <code class="text-muted">{{ $field->field_key }}</code>
                                            @if($field->builtin_source)
                                                <span class="text-muted">&middot; tersimpan di kolom <code>{{ $field->builtin_source }}</code></span>
                                            @endif
                                        </div>
                                        @if($field->help_text)
                                            <div class="fs-2 text-muted">{{ $field->help_text }}</div>
                                        @endif
                                        @if($field->options)
                                            <div class="fs-2 text-muted">
                                                Pilihan: {{ collect($field->options)->pluck('label')->implode(', ') }}
                                            </div>
                                        @endif
                                        @if($field->isFile() && $field->max_kb)
                                            <div class="fs-2 text-muted">Maksimal {{ number_format($field->max_kb, 0, ',', '.') }} KB</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-dark">
                                            <i class="ti {{ $tipeIkon[$field->type] ?? 'ti-forms' }}"></i>
                                            {{ $tipeLabel[$field->type] ?? $field->type }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($field->is_required)
                                            <span class="badge bg-warning-subtle text-warning">Wajib</span>
                                        @else
                                            <span class="text-muted">Opsional</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $terisi = (int) ($this->usageCounts[$field->id] ?? 0); @endphp
                                        @if($terisi > 0)
                                            <span class="text-dark">{{ $terisi }}</span>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" wire:click="toggleActive({{ $field->id }})" class="btn btn-sm border-0 btn-link p-0"
                                            title="{{ $field->is_active ? 'Klik untuk mematikan' : 'Klik untuk mengaktifkan' }}">
                                            @if($field->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $field->id }})" title="Edit Field">
                                            <i class="ti ti-edit fs-4"></i>
                                        </button>
                                        @if($field->is_builtin)
                                            <button type="button" class="btn btn-sm btn-outline-secondary p-1" disabled title="Field bawaan tidak bisa dihapus">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $field->id }})" title="Hapus Field"
                                                wire:confirm="Hapus field &quot;{{ $field->label }}&quot; dari formulir pendaftaran?">
                                                <i class="ti ti-trash fs-4"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Form Tambah / Edit Field --}}
    @if($showFormModal)
        @php $fieldSedangDiedit = $isEditMode ? $this->fields->firstWhere('id', $editingId) : null; @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="closeFormModal">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white fw-semibold">
                            {{ $isEditMode ? 'Edit Field' : 'Tambah Field' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeFormModal" aria-label="Tutup"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                            @if($fieldSedangDiedit?->is_builtin)
                                <div class="alert alert-info py-2 px-3 mb-3 fs-2">
                                    <i class="ti ti-info-circle"></i>
                                    Ini field bawaan sistem. Label, wajib, status, dan petunjuknya boleh diubah;
                                    tipenya terkunci karena nilainya dipakai halaman publik.
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Label Field <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="label" placeholder="Misal: Asal Kabupaten / Kota" required>
                                <small class="form-text text-muted">Pertanyaan yang dibaca pendaftar di formulir.</small>
                                @error('label') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tipe Field <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="type" @disabled($fieldSedangDiedit?->is_builtin)>
                                    @foreach($tipeLabel as $nilai => $teks)
                                        <option value="{{ $nilai }}">{{ $teks }}</option>
                                    @endforeach
                                </select>
                                @error('type') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>

                            @if($type === 'select')
                                <div class="mb-3">
                                    <label class="form-label">Pilihan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" rows="5" wire:model="optionsText"
                                        placeholder="Putra | Beregu Putra&#10;Putri | Beregu Putri&#10;Campuran"></textarea>
                                    <small class="form-text text-muted">
                                        Satu pilihan per baris. Format <code>nilai | label</code> &mdash;
                                        label boleh dikosongkan bila sama dengan nilainya.
                                    </small>
                                    @error('optionsText') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if($type === 'wilayah')
                                <div class="mb-3">
                                    <label class="form-label">Tingkat Wilayah <span class="text-danger">*</span></label>
                                    <select class="form-select" wire:model="wilayahLevel">
                                        <option value="auto">Ikut tingkat lomba event</option>
                                        <option value="provinsi">Provinsi saja</option>
                                        <option value="kabupaten">Provinsi + Kabupaten/Kota</option>
                                        <option value="kecamatan">Provinsi + Kabupaten/Kota + Kecamatan</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Daftarnya diambil langsung dari <code>api.datawilayah.com</code>.
                                        Pilihan <em>Ikut tingkat lomba</em> menyesuaikan sendiri:
                                        event tingkat nasional hanya menawarkan provinsi, tingkat
                                        provinsi menawarkan sampai kabupaten/kota.
                                    </small>
                                    @error('wilayahLevel') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            @if(in_array($type, ['file', 'image'], true))
                                <div class="mb-3">
                                    <label class="form-label">Batas Ukuran (KB) <span class="text-muted">(Opsional)</span></label>
                                    <input type="number" class="form-control" wire:model="max_kb" placeholder="5120" min="1">
                                    <small class="form-text text-muted">
                                        Kosongkan untuk batas bawaan 5 MB.
                                        {{ $type === 'image' ? 'Hanya berkas gambar yang diterima.' : 'Berkas PDF, JPG, atau PNG.' }}
                                    </small>
                                    @error('max_kb') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Petunjuk <span class="text-muted">(Opsional)</span></label>
                                <input type="text" class="form-control" wire:model="help_text" placeholder="Misal: Ditulis lengkap, mis. Kabupaten Bogor">
                                <small class="form-text text-muted">Teks kecil di bawah input formulir pendaftaran.</small>
                                @error('help_text') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>

                            @if(! in_array($type, ['file', 'image', 'wilayah'], true))
                                <div class="mb-3">
                                    <label class="form-label">Nilai Awal <span class="text-muted">(Opsional)</span></label>
                                    <input type="text" class="form-control" wire:model="default_value" placeholder="Misal: Kabupaten Lampung Selatan">
                                    <small class="form-text text-muted">Terisi otomatis di formulir, pendaftar masih bisa mengubahnya.</small>
                                    @error('default_value') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <hr class="my-3">

                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="field-required" wire:model="is_required">
                                <label class="form-check-label" for="field-required">Wajib diisi pendaftar</label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="field-active" wire:model="is_active">
                                <label class="form-check-label" for="field-active">Tampilkan di formulir pendaftaran</label>
                                <div class="fs-2 text-muted">
                                    Mematikan field menyembunyikannya dari formulir, tapi jawaban yang sudah masuk tetap tersimpan.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeFormModal">Batal</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <i class="ti ti-{{ $isEditMode ? 'device-floppy' : 'plus' }}"></i> {{ $isEditMode ? 'Simpan' : 'Tambahkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
