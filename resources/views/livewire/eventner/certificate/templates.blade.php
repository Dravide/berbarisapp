<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-semibold mb-0">
            <i class="ti ti-certificate me-2"></i> Sertifikat Juara
        </h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" wire:click="createTemplate">
                <i class="ti ti-plus me-1"></i> Template Baru
            </button>
        </div>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Template Form --}}
    @if($showTemplateForm)
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">{{ $editingTemplate ? 'Edit Template' : 'Tambah Template' }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nama Template</label>
                    <input type="text" class="form-control" wire:model="templateForm.name" placeholder="Contoh: Sertifikat Juara Umum SD">
                    @error('templateForm.name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ukuran Kertas</label>
                    <select class="form-select" wire:model.live="presetKey">
                        <option value="">-- Pilih Preset --</option>
                        @foreach($paperPresets as $key => $preset)
                            <option value="{{ $key }}">{{ $preset['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lebar (mm)</label>
                    <input type="number" class="form-control" wire:model="templateForm.width" step="0.1" min="50" max="1000">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tinggi (mm)</label>
                    <input type="number" class="form-control" wire:model="templateForm.height" step="0.1" min="50" max="1000">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Upload Gambar Template</label>
                    <input type="file" class="form-control" wire:model="templateImage" accept="image/png,image/jpeg,image/webp">
                    @error('templateImage') <small class="text-danger">{{ $message }}</small> @enderror
                    <small class="text-muted">Format PNG/JPG/WebP. Maks 10MB.</small>
                </div>
                <div class="col-md-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="show-besign" wire:model.live="templateForm.show_besign">
                        <label class="form-check-label" for="show-besign">Tampilkan BeSign<br><small class="text-muted">Watermark/lisensi kecil di pojok sertifikat</small></label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teks BeSign</label>
                    <input type="text" class="form-control" wire:model="templateForm.besign_text"
                           placeholder="Contoh: Diterbitkan oleh BARIS APP">
                    <small class="text-muted">Ditampilkan sebagai watermark kecil. Kosongkan untuk default "Diterbitkan oleh {nama_event}"</small>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-success" wire:click="saveTemplate">
                    <i class="ti ti-check me-1"></i> Simpan
                </button>
                <button type="button" class="btn btn-outline-secondary" wire:click="$set('showTemplateForm', false)">
                    Batal
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Template List --}}
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-semibold">Daftar Template</h5>
            <span class="badge bg-primary-subtle text-primary">{{ count($templates) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Preview</th>
                        <th>Nama Template</th>
                        <th>Ukuran</th>
                        <th>Field Teks</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tpl)
                        <tr>
                            <td>
                                <div style="width: 50px; height: 35px; background: #f0f0f0; border-radius: 3px; overflow: hidden; border: 1px solid #ddd;">
                                    @if($tpl['image_url'])
                                        <img src="{{ $tpl['image_url'] }}" style="width:100%; height:100%; object-fit: cover;">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                            <i class="ti ti-photo fs-6"></i>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="fw-semibold">{{ $tpl['name'] }}</td>
                            <td class="text-muted">{{ $tpl['width'] }} × {{ $tpl['height'] }} mm</td>
                            <td>
                                <span class="badge bg-info-subtle text-info">{{ $tpl['fields_count'] }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('eventner.certificate.editor', $tpl['id']) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-edit me-1"></i> Atur Field
                                    </a>
                                    <button class="btn btn-sm btn-light" wire:click="editTemplate({{ $tpl['id'] }})"
                                        title="Ubah nama/ukuran">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light text-danger"
                                        wire:click="deleteTemplate({{ $tpl['id'] }})"
                                        wire:confirm="Hapus template ini? Semua field teks juga akan dihapus."
                                        title="Hapus">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="ti ti-certificate-off fs-9"></i>
                                <p class="mb-0 mt-2">Belum ada template</p>
                                <small>Klik "Template Baru" untuk memulai</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Download Section --}}
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0 fw-semibold">
                <i class="ti ti-download me-2"></i> Download Sertifikat
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Template</label>
                    <select class="form-select form-select-sm" wire:model.live="downloadTemplateId">
                        <option value="">-- Pilih Template --</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Kategori Juara</label>
                    <select class="form-select form-select-sm" wire:model.live="downloadChampionCategoryId">
                        <option value="">-- Pilih Kategori Juara --</option>
                        @foreach($championCategories as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Kategori Lomba</label>
                    <select class="form-select form-select-sm" wire:model.live="downloadCompetitionCategoryId">
                        <option value="">-- Pilih Kategori Lomba --</option>
                        @foreach($competitionCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('eventner.certificate.pdf', [
                        'template_id' => $downloadTemplateId,
                        'champion_category_id' => $downloadChampionCategoryId,
                        'competition_category_id' => $downloadCompetitionCategoryId,
                    ]) }}"
                       target="_blank"
                       class="btn btn-primary {{ !$downloadTemplateId || !$downloadChampionCategoryId || !$downloadCompetitionCategoryId ? 'disabled' : '' }}">
                        <i class="ti ti-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
            @if(!$downloadTemplateId || !$downloadChampionCategoryId || !$downloadCompetitionCategoryId)
                <small class="text-muted d-block mt-2">Pilih template, kategori juara, dan kategori lomba untuk mendownload.</small>
            @endif
        </div>
    </div>
</div>
