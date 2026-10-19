<div>
    {{-- Modal import lintas event (dibuka dari dropdown Unduh & Import di Builder).
         Sengaja TIDAK menumpuk dengan #importExcelModal: keduanya modal-xl yang
         sama-sama scrollable, dan menumpuknya membuat tombol Close yang atas
         menutup modal yang salah. Skrip di bawah menutup yang lain lebih dulu.

         Tidak ada wire:confirm di dalam modal ini — di dalam modal Bootstrap
         wire:confirm selalu membatalkan aksinya. Konfirmasi simpan lewat
         SweetAlert, memanggil confirmImport() dari JS. --}}
    <div class="modal fade" id="importLintasModal" tabindex="-1" aria-labelledby="importLintasModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white fw-semibold" id="importLintasModalLabel">
                        <i class="ti ti-share me-1"></i> Import Format dari Event Lain
                        <span class="badge bg-white text-primary ms-2 fs-1">Langkah {{ $step }} dari 3</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="closePreview"></button>
                </div>

                <div class="modal-body">
                    {{-- Langkah 1: pilih event sumber + kode berbagi --}}
                    @if($step === 1)
                        <div class="alert alert-info border-0 bg-info-subtle fs-2 py-2">
                            <i class="ti ti-info-circle me-1"></i> Rubrik event lain hanya bisa dibaca bila panitianya
                            memberi Anda <strong>kode berbagi</strong>. Minta kode itu langsung ke panitia event sumber.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Event Sumber</label>
                            <select class="form-select @error('sourceEventnerId') is-invalid @enderror" wire:model="sourceEventnerId">
                                <option value="">— Pilih event —</option>
                                @foreach($this->sumberEvents() as $ev)
                                    <option value="{{ $ev->id }}">{{ $ev->nama_event }}@if($ev->subdomain) — {{ $ev->subdomain }}@endif</option>
                                @endforeach
                            </select>
                            @error('sourceEventnerId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="form-text text-muted">Hanya event yang sudah disetujui yang muncul di daftar. Event Anda sendiri tidak ikut.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kode Berbagi dari Event Sumber</label>
                            <input type="text"
                                   class="form-control font-monospace @error('shareCode') is-invalid @enderror"
                                   wire:model="shareCode"
                                   placeholder="Contoh: PBB26-SMP-4KQ9"
                                   autocomplete="off">
                            @error('shareCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="form-text text-muted">Kode diperiksa saat Anda menekan Buka Pratinjau, bukan saat mengetik.</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" wire:click="verifyAndPreview" wire:loading.attr="disabled">
                                <i class="ti ti-eye me-1"></i> Buka Pratinjau
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click="closePreview">Batal</button>
                            <div wire:loading wire:target="verifyAndPreview" class="text-muted align-self-center">
                                <span class="spinner-border spinner-border-sm me-1"></span> Memeriksa kode...
                            </div>
                        </div>
                    @endif

                    {{-- Langkah 2: pratinjau rubrik event sumber --}}
                    @if($step === 2)
                        <div class="alert alert-success border-0 bg-success-subtle text-success mb-3">
                            <i class="ti ti-check me-1"></i> Kode diterima — rubrik dimuat, <strong>belum disalin</strong>.
                        </div>

                        @if(($previewMeta['rubrikCount'] ?? 0) === 0 && ($previewMeta['penguranganCount'] ?? 0) === 0)
                            <div class="alert alert-warning py-2 fs-3">
                                <i class="ti ti-alert-triangle me-1"></i> <strong>{{ $previewMeta['sourceName'] }}</strong> belum menyusun format penilaian.
                                Belum ada yang bisa disalin.
                            </div>
                        @endif

                        <div class="row g-2 mb-2">
                            <div class="col-auto"><span class="badge bg-primary">{{ $previewMeta['rubrikCount'] }} kategori rubrik</span></div>
                            <div class="col-auto"><span class="badge bg-primary">{{ $previewMeta['criteriaCount'] }} kriteria</span></div>
                            <div class="col-auto"><span class="badge bg-danger">{{ $previewMeta['penguranganCount'] }} kelompok pengurangan</span></div>
                            <div class="col-auto"><span class="badge bg-danger">{{ $previewMeta['deductionCriteriaCount'] }} kriteria pengurangan</span></div>
                        </div>

                        <p class="fs-3 text-muted mb-3">
                            <i class="ti ti-building me-1"></i> Asal: <strong>{{ $previewMeta['sourceName'] }}</strong>
                            @if(!empty($previewMeta['sourceSubdomain']))<span class="font-monospace fs-2"> · {{ $previewMeta['sourceSubdomain'] }}</span>@endif
                        </p>

                        @php $senama = $previewMeta['senama'] ?? []; @endphp
                        @if(!empty($previewMeta['namaKategori']))
                            <div class="mb-3">
                                <div class="fw-semibold fs-3 mb-1">Kategori yang akan disalin</div>
                                <ul class="list-unstyled mb-0 fs-3">
                                    @foreach($previewMeta['namaKategori'] as $nama)
                                        <li class="mb-1">
                                            <i class="ti ti-point text-muted"></i> {{ $nama }}
                                            @if(in_array($nama, $senama, true))
                                                <span class="badge bg-warning-subtle text-warning ms-1">nama sudah ada di tingkat ini</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0 border">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="fw-semibold">Tipe</th>
                                        <th class="fw-semibold">Kategori</th>
                                        <th class="fw-semibold">Sub-Kategori</th>
                                        <th class="fw-semibold">Kriteria</th>
                                        <th class="fw-semibold" width="30%">Skor</th>
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
                                            <td class="text-center"><span class="text-muted">{{ $row['bobot'] ? $row['bobot'].'x' : '—' }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada baris untuk ditampilkan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-primary"
                                    wire:click="lanjutPilihTingkat"
                                    @disabled(($previewMeta['rubrikCount'] ?? 0) === 0 && ($previewMeta['penguranganCount'] ?? 0) === 0)>
                                <i class="ti ti-arrow-right me-1"></i> Lanjut — Pilih Tingkat Tujuan
                            </button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="closePreview">Batal</button>
                        </div>
                    @endif

                    {{-- Langkah 3: tingkat tujuan + konfirmasi simpan --}}
                    @if($step === 3)
                        @if($this->competitionCategories()->isEmpty())
                            <div class="alert alert-warning py-3">
                                <i class="ti ti-alert-triangle me-1"></i> <strong>Buat tingkat lomba dulu.</strong>
                                Rubrik harus menempel pada sebuah tingkat lomba. Buat tingkatnya lebih dahulu, lalu buka
                                modal ini lagi.
                                <div class="mt-2">
                                    <a href="{{ route('eventner.competition-categories.index') }}" class="btn btn-sm btn-warning">
                                        <i class="ti ti-plus me-1"></i> Buka Kategori Lomba
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Simpan untuk Tingkat</label>
                                <select class="form-select @error('activeTab') is-invalid @enderror" wire:model="activeTab">
                                    <option value="">— Pilih tingkat —</option>
                                    @foreach($this->competitionCategories() as $cc)
                                        <option value="{{ $cc->id }}">{{ $cc->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('activeTab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="form-text text-muted">Hanya tingkat lomba milik event Anda. Induk yang punya anak tidak ditawarkan.</small>
                            </div>

                            <div class="alert alert-warning border-0 bg-warning-subtle fs-2 py-2">
                                <i class="ti ti-alert-triangle me-1"></i>
                                <strong>Menambah, bukan mengganti.</strong>
                                {{ $previewMeta['rubrikCount'] }} kategori dari <strong>{{ $previewMeta['sourceName'] }}</strong>
                                akan <strong>ditambahkan</strong> ke <strong>{{ $previewMeta['targetName'] }}</strong>.
                                Rubrik yang sudah ada di tingkat ini tidak dihapus — kategori dengan nama sama akan
                                muncul dua kali, jadi periksa dulu sebelum menyimpan.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" wire:click="mintaKonfirmasiSimpan" wire:loading.attr="disabled">
                                    <i class="ti ti-check me-1"></i> Simpan ke Format
                                </button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="kembaliKePratinjau">
                                    <i class="ti ti-arrow-left me-1"></i> Kembali
                                </button>
                                <div wire:loading wire:target="confirmImport" class="text-muted align-self-center">
                                    <span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // Dua modal import sama-sama modal-xl. Menutup yang lain lebih dulu supaya
    // tombol Close dan backdrop tidak menunjuk modal yang salah.
    const modalEl = document.getElementById('importLintasModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', () => {
            const lain = document.getElementById('importExcelModal');
            if (lain && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(lain).hide();
            }
        });
    }

    // Konfirmasi simpan. Bukan wire:confirm — di dalam modal Bootstrap
    // wire:confirm selalu membatalkan aksinya.
    Livewire.on('lintas:simpan', (event) => {
        const d = (event && event.detail) || event || {};
        const jumlah = d.jumlah || 0;
        const asal = d.asal || 'event sumber';
        const tujuan = d.tujuan || 'tingkat tujuan';

        if (!window.Swal) {
            const comp = Livewire.find(d.komponen);
            if (comp) comp.call('confirmImport');
            return;
        }

        Swal.fire({
            title: 'Simpan Salinan Rubrik?',
            html: '<strong>' + jumlah + ' kategori</strong> dari <strong>' + asal + '</strong> akan '
                + '<strong>ditambahkan</strong> ke <strong>' + tujuan + '</strong>.<br>'
                + '<small>Rubrik yang sudah ada di tingkat itu tidak dihapus.</small>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                const comp = Livewire.find(d.komponen);
                if (comp) comp.call('confirmImport');
            }
        });
    });

    // Tutup modal + toast setelah tersimpan. `import:done` yang sama dipakai
    // jalur Excel, jadi Builder ikut tersegarkan lewat listener-nya sendiri.
    Livewire.on('import:done', (event) => {
        const el = document.getElementById('importLintasModal');
        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        }

        // Param bernama → objek. Tetap dibaca `detail || event` karena jalur
        // Excel bisa mengirim bentuk lain.
        const d = (event && event.detail) || event || {};
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
