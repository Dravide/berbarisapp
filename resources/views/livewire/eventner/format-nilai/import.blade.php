<div>
    {{-- Modal Import (dibuka dari dropdown di halaman Builder) --}}
    <div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white fw-semibold" id="importExcelModalLabel">
                        <i class="ti ti-file-import me-1"></i> Import Format Penilaian dari Excel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="closePreview"></button>
                </div>

                <div class="modal-body">
                    @if(session('import_error'))
                        <div class="alert alert-danger py-2 fs-3">
                            <i class="ti ti-alert-circle me-1"></i> {{ session('import_error') }}
                        </div>
                    @endif

                    @if(!$showPreview)
                        {{-- Langkah 1: pilih file & target --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih File Excel (.xlsx / .xls)</label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" wire:model="file" accept=".xlsx,.xls">
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="form-text text-muted">Gunakan template di atas untuk struktur yang benar. Max 2 MB.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Simpan untuk Tingkat</label>
                            <select class="form-select" wire:model="activeTab">
                                <option value="">Semua Tingkat (Global)</option>
                                @foreach($this->competitionCategories() as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->full_name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Kategori hasil import menempel ke tingkat ini. Sinkron dengan pilihan tingkat di halaman.</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" wire:click="uploadExcel" wire:loading.attr="disabled">
                                <i class="ti ti-file-text me-1"></i> Upload &amp; Pratinjau
                            </button>
                            <div wire:loading wire:target="uploadExcel" class="text-muted align-self-center">
                                <span class="spinner-border spinner-border-sm me-1"></span> Membaca file...
                            </div>
                        </div>
                    @else
                        {{-- Langkah 2: preview sebelum simpan --}}
                        <div class="alert alert-success border-0 bg-success-subtle text-success mb-3">
                            <i class="ti ti-check me-1"></i> Preview berhasil. Data di bawah <strong>belum disimpan</strong> ke database.
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-auto"><span class="badge bg-primary">{{ $previewMeta['rubrikCount'] }} kategori rubrik</span></div>
                            <div class="col-auto"><span class="badge bg-primary">{{ $previewMeta['criteriaCount'] }} kriteria</span></div>
                            <div class="col-auto"><span class="badge bg-danger">{{ $previewMeta['penguranganCount'] }} kelompok pengurangan</span></div>
                            <div class="col-auto"><span class="badge bg-danger">{{ $previewMeta['deductionCriteriaCount'] }} kriteria pengurangan</span></div>
                            <div class="col-auto"><span class="badge bg-secondary">{{ $previewMeta['targetName'] }}</span></div>
                        </div>

                        <div class="alert alert-info border-0 bg-info-subtle fs-2 py-2">
                            <i class="ti ti-info-circle me-1"></i> Perilaku import: <strong>menambah</strong> kategori baru pada
                            <strong>{{ $previewMeta['targetName'] }}</strong> — data format yang sudah ada tidak dihapus.
                        </div>

                        @if(!empty($rowErrors))
                            <div class="alert alert-warning py-2 fs-3">
                                <i class="ti ti-alert-triangle me-1"></i> <strong>{{ count($rowErrors) }} baris dilewati</strong> (tidak ikut disimpan):
                                <ul class="mb-0 mt-1 ps-4">
                                    @foreach($rowErrors as $err)
                                        <li>Baris {{ $err['row'] }}: {{ $err['message'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0 border">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="fw-semibold">Tipe</th>
                                        <th class="fw-semibold">Kategori</th>
                                        <th class="fw-semibold">Sub-Kategori</th>
                                        <th class="fw-semibold">Kriteria</th>
                                        <th class="fw-semibold" width="35%">Skor</th>
                                        <th class="fw-semibold text-center" width="60px">Bobot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($previewData as $row)
                                        <tr>
                                            <td>
                                                @if($row['tipe'] === 'Pengurangan')
                                                    <span class="badge bg-danger">Pengurangan</span>
                                                @else
                                                    <span class="badge bg-primary">Rubrik</span>
                                                @endif
                                            </td>
                                            <td class="fw-semibold">{{ $row['kategori'] }}</td>
                                            <td>{{ $row['sub'] }}</td>
                                            <td>{{ $row['kriteria'] }}</td>
                                            <td><span class="text-muted fs-2">{{ $row['skor'] }}</span></td>
                                            <td class="text-center">
                                                @if($row['tipe'] === 'Rubrik' && $row['bobot'] != '1')
                                                    <span class="badge bg-info text-dark">{{ $row['bobot'] }}x</span>
                                                @else
                                                    <span class="text-muted">1x</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada baris valid untuk ditampilkan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-success" wire:click="confirmImport" wire:confirm="Yakin simpan import ke format? Data baru akan DITAMBAHKAN ke tingkat terpilih." wire:loading.attr="disabled">
                                <i class="ti ti-check me-1"></i> Simpan ke Format
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="closePreview">
                                <i class="ti ti-x me-1"></i> Batal
                            </button>
                            <div wire:loading wire:target="confirmImport" class="text-muted align-self-center">
                                <span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tutup modal import setelah sukses disimpan, lalu tampilkan toast SweetAlert2.
// Hasil import sudah dirender ulang oleh Builder (event import:done) — tanpa refresh.
document.addEventListener('livewire:init', () => {
    Livewire.on('import:done', (event) => {
        const modalEl = document.getElementById('importExcelModal');
        if (modalEl && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }

        const d = (event && event.detail) || {};
        const message = typeof d === 'string' ? d : (d.message || 'Import berhasil.');
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                toastClass: 'border-start border-4 border-success',
            });
        }
    });
});
</script>
