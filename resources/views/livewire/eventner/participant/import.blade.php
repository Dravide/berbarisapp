<div>
    {{-- Tombol pemicu di toolbar halaman induk --}}
    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
            wire:click="openImportModal('{{ $activeTab }}')">
        <i class="ti ti-file-import"></i> Import Pendaftar
    </button>

    {{--
        Modal pratinjau import.

        Gaya `show d-block` + latar gelap mengikuti modal di halaman ini
        (participant/index.blade.php), bukan Bootstrap Modal JS: komponen ini
        nested di dalam halaman itu, jadi perilakunya harus sama — dan tanpa
        `bootstrap.Modal` tidak perlu id tetap untuk menutupnya.
    --}}
    @if($showImportModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">
                            <i class="ti ti-file-import me-1"></i> Import Pendaftar dari Excel
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeImportModal"></button>
                    </div>

                    <div class="modal-body">
                        @if($pesanError !== '')
                            <div class="alert alert-danger py-2 fs-3">
                                <i class="ti ti-alert-circle me-1"></i> {{ $pesanError }}
                            </div>
                        @endif

                        {{-- Daftar baris bermasalah tampil di kedua langkah: berkas
                             yang tidak punya satu pun baris baru tetap perlu
                             menunjukkan baris mana yang menyebabkannya. --}}
                        @if(!empty($rowErrors))
                            <div class="alert alert-warning py-2 fs-3">
                                <i class="ti ti-alert-triangle me-1"></i>
                                <strong>{{ count($rowErrors) }} baris tidak ikut disimpan</strong>:
                                <ul class="mb-0 mt-1 ps-4" style="max-height: 160px; overflow-y: auto;">
                                    @foreach($rowErrors as $err)
                                        <li>Baris {{ $err['row'] }}: {{ $err['message'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(empty($previewData))
                            {{-- Langkah 1: unggah berkas --}}
                            <div class="alert alert-info border-0 bg-info-subtle fs-3 py-2">
                                <i class="ti ti-info-circle me-1"></i>
                                File dibaca dan ditampilkan dulu sebagai tabel.
                                <strong>Belum ada data yang tersimpan</strong> sampai Anda menekan Simpan.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">File Excel (.xlsx / .xls)</label>
                                <input type="file" class="form-control @error('file') is-invalid @enderror"
                                       wire:model="file" accept=".xlsx,.xls">
                                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="form-text text-muted">
                                    Gunakan tombol Download Template untuk struktur kolom yang benar. Maks
                                    {{ \App\Support\PendaftarImport::MAX_FILE_KB }} KB.
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Simpan ke Kategori</label>
                                <div class="form-control bg-light" readonly>{{ $this->targetName() }}</div>
                                <small class="form-text text-muted">
                                    Mengikuti kategori yang sedang dibuka di halaman ini. Ganti kategori di
                                    halaman, lalu buka import lagi.
                                </small>
                            </div>

                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-primary" wire:click="uploadExcel" wire:loading.attr="disabled">
                                    <i class="ti ti-file-text me-1"></i> Unggah &amp; Pratinjau
                                </button>
                                <a href="{{ route('eventner.participants.template') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-download me-1"></i> Download Template
                                </a>
                                <div wire:loading wire:target="uploadExcel" class="text-muted">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Membaca file...
                                </div>
                            </div>
                        @else
                            {{-- Langkah 2: pratinjau sebelum simpan --}}
                            <div class="alert alert-success border-0 bg-success-subtle text-success mb-3">
                                <i class="ti ti-check me-1"></i>
                                Pratinjau berhasil. Data di bawah <strong>belum disimpan</strong> ke database.
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-auto"><span class="badge bg-primary">{{ $previewMeta['baru'] ?? 0 }} siap diimpor</span></div>
                                <div class="col-auto"><span class="badge bg-warning text-dark">{{ $previewMeta['duplikat'] ?? 0 }} duplikat dilewati</span></div>
                                <div class="col-auto"><span class="badge bg-danger">{{ $previewMeta['error'] ?? 0 }} bermasalah</span></div>
                                <div class="col-auto"><span class="badge bg-secondary">{{ $previewMeta['targetName'] ?? '-' }}</span></div>
                                @if(!empty($previewMeta['kolomDiabaikan']))
                                    <div class="col-auto">
                                        <span class="badge bg-light text-dark border"
                                              title="Kolom ini tidak dikenal dan tidak ikut diimpor">
                                            Kolom diabaikan: {{ implode(', ', $previewMeta['kolomDiabaikan']) }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                <table class="table table-sm align-middle mb-0 border">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th class="fw-semibold" width="60px">Baris</th>
                                            <th class="fw-semibold">Nama Sekolah</th>
                                            <th class="fw-semibold">NPSN</th>
                                            <th class="fw-semibold">Nama Pasukan</th>
                                            <th class="fw-semibold">Pelatih</th>
                                            <th class="fw-semibold" width="120px">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($previewData as $row)
                                            <tr class="{{ $row['status'] === 'baru' ? '' : 'table-warning' }}">
                                                <td class="text-muted">{{ $row['row'] }}</td>
                                                <td class="fw-semibold">{{ $row['nama_sekolah'] }}</td>
                                                <td>{{ $row['npsn'] !== '' ? $row['npsn'] : '—' }}</td>
                                                <td>{{ $row['label_pasukan'] !== '' ? $row['label_pasukan'] : '—' }}</td>
                                                <td>{{ $row['pelatih'] }}</td>
                                                <td>
                                                    @if($row['status'] === 'baru')
                                                        <span class="badge bg-primary">Baru</span>
                                                    @elseif($row['status'] === 'duplikat')
                                                        <span class="badge bg-warning text-dark" title="{{ $row['catatan'] }}">Duplikat</span>
                                                    @else
                                                        <span class="badge bg-danger" title="{{ $row['catatan'] }}">Bermasalah</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada baris untuk ditampilkan.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 d-flex gap-2 align-items-center">
                                <button type="button" class="btn btn-primary" wire:click="confirmImport"
                                        wire:confirm="Simpan {{ $previewMeta['baru'] ?? 0 }} pendaftar baru ke kategori {{ $previewMeta['targetName'] ?? '' }}?"
                                        wire:loading.attr="disabled">
                                    <i class="ti ti-check me-1"></i> Simpan {{ $previewMeta['baru'] ?? 0 }} Pendaftar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="closePreview">
                                    <i class="ti ti-x me-1"></i> Batal
                                </button>
                                <div wire:loading wire:target="confirmImport" class="text-muted">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
// Tutup modal + toast setelah simpan. Daftar di halaman induk sudah disegarkan
// lewat event $refresh dari komponen, jadi tidak perlu reload halaman.
document.addEventListener('livewire:init', () => {
    Livewire.on('import:done', (event) => {
        const d = (event && event.detail) || event || {};
        const message = typeof d === 'string' ? d : (d.message || 'Import berhasil.');

        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: message,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
                toastClass: 'border-start border-4 border-success',
            });
        }
    });
});
</script>
