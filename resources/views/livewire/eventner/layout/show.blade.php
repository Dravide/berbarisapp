<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">{{ $layout->name }}</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('eventner.denah.index') }}">Denah Lapangan</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">{{ $layout->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Batang aksi denah: tombol di sini mengubah denah sebagai satu kesatuan
         (terbit, ukuran papan, unduhan), bukan satu zona terpilih. --}}
    <div class="card mb-4">
        <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($layout->is_published)
                    <span class="badge bg-success-subtle text-success">Terbit</span>
                @else
                    <span class="badge bg-warning-subtle text-warning">Draf</span>
                @endif
                <span class="text-muted small">
                    @if($layout->competitionCategory)
                        {{ $layout->competitionCategory->full_name }}
                    @else
                        Denah umum
                    @endif
                    &middot;
                    {{ rtrim(rtrim(number_format((float) $layout->ground_width, 1, ',', '.'), '0'), ',') }}
                    &times;
                    {{ rtrim(rtrim(number_format((float) $layout->ground_height, 1, ',', '.'), '0'), ',') }} m
                    &middot; {{ $zones->count() }} zona
                </span>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @if($layout->is_published)
                    <button class="btn btn-outline-secondary btn-sm" wire:click="tarik">
                        <i class="ti ti-eye-off me-1"></i> Tarik
                    </button>
                @else
                    <button class="btn btn-success btn-sm" wire:click="terbitkan">
                        <i class="ti ti-send me-1"></i> Terbitkan
                    </button>
                @endif
                <button class="btn btn-outline-secondary btn-sm" wire:click="bukaPapanModal">
                    <i class="ti ti-ruler-measure me-1"></i> Ukuran Papan
                </button>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" wire:click="unduhPng">
                        <i class="ti ti-photo me-1"></i> PNG
                    </button>
                    <a class="btn btn-outline-primary" href="{{ route('eventner.denah.pdf', $layout->id) }}" target="_blank" rel="noopener">
                        <i class="ti ti-file-type-pdf me-1"></i> PDF
                    </a>
                </div>
                <button class="btn btn-primary btn-sm" wire:click="bukaDpModal">
                    <i class="ti ti-wand me-1"></i> Susun DP Otomatis
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Kanvas dapat 8 dari 12 kolom karena ia satu-satunya tempat kerja
             di halaman ini; panel kanan cukup 4 kolom untuk tiga field pendek. --}}
        <div class="col-lg-8">
            <div class="card"><div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-semibold mb-0">Kanvas</h6>
                    <small class="text-muted">
                        Seret kotak untuk memindahkan &middot; tarik sudut kanan-bawah untuk mengubah ukuran
                    </small>
                </div>

                @include('partials.denah-kanvas', [
                    'layout' => $layout,
                    'zones' => $zones,
                    'interaktif' => true,
                    'terpilihId' => $selectedId,
                    'kisi' => true,
                ])
            </div></div>
        </div>

        <div class="col-lg-4">
            <div class="card"><div class="card-body p-3">
                <h6 class="fw-semibold mb-2">Tambah Zona</h6>
                <div class="d-grid gap-1 mb-3">
                    @foreach($tipeTersedia as $kunci => $label)
                        <button class="btn btn-outline-secondary btn-sm text-start" wire:click="tambahZona('{{ $kunci }}')">
                            <i class="ti ti-plus me-1"></i> {{ $label }}
                        </button>
                    @endforeach
                </div>
                <div class="form-text">
                    Setelah ditambahkan, seret kotaknya di kanvas. Klik untuk mengubah nama &amp; kapasitasnya.
                </div>
            </div></div>

            <div class="card"><div class="card-body p-3">
                <h6 class="fw-semibold mb-2">Zona ({{ $zones->count() }})</h6>

                @if($zones->isEmpty())
                    <p class="text-muted small mb-0">
                        Belum ada zona. Pakai <strong>Susun DP Otomatis</strong> untuk arena + DP 1&ndash;5 sekaligus.
                    </p>
                @else
                    <div class="list-group list-group-flush" style="max-height: 420px; overflow-y: auto;">
                        @foreach($zones as $z)
                            <button
                                type="button"
                                wire:key="daftar-{{ $z->id }}"
                                class="list-group-item list-group-item-action px-2 py-1 {{ $selectedId === $z->id ? 'active' : '' }}"
                                wire:click="pilihZona({{ $z->id }})"
                            >
                                <div class="d-flex align-items-center gap-2">
                                    <span class="zone-dot {{ $z->kelas_tipe }}"></span>
                                    <span class="text-truncate small">{{ $z->name }}</span>
                                    @if($z->dp_number)
                                        <span class="badge bg-light text-dark ms-auto">DP {{ $z->dp_number }}</span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div></div>

            <div class="card"><div class="card-body p-3">
                @if($terpilih)
                    <h6 class="fw-semibold mb-2">
                        {{ $terpilih->label_tipe }}
                        <span class="badge bg-light text-dark">{{ $terpilih->name }}</span>
                    </h6>

                    <form wire:submit="simpanZona">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Nama</label>
                            <input class="form-control form-control-sm" wire:model="nama" required>
                            @error('nama')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        @if($terpilih->type === \App\Models\LayoutZone::TYPE_DP)
                            <div class="mb-2">
                                <label class="form-label small mb-1">Nomor DP</label>
                                <input type="number" min="1" max="99" class="form-control form-control-sm" wire:model="nomorDp" placeholder="mis. 1">
                                <div class="form-text">DP 1 selalu yang paling dekat arena.</div>
                                @error('nomorDp')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label small mb-1">Kapasitas</label>
                            <input class="form-control form-control-sm" wire:model="kapasitas" placeholder="mis. 1 pasukan">
                            @error('kapasitas')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small mb-1">Catatan</label>
                            <textarea class="form-control form-control-sm" rows="2" wire:model="catatan" placeholder="Opsional"></textarea>
                            @error('catatan')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-3 text-muted small">
                            <i class="ti ti-ruler-measure"></i> {{ $terpilih->ukuranMeter($layout) }}<br>
                            <span class="text-muted">
                                X {{ rtrim(rtrim(number_format((float) $terpilih->x, 1, ',', '.'), '0'), ',') }}%
                                &middot; Y {{ rtrim(rtrim(number_format((float) $terpilih->y, 1, ',', '.'), '0'), ',') }}%
                            </span>
                        </div>

                        <div class="d-grid gap-1">
                            <button class="btn btn-primary btn-sm">Simpan Zona</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="duplikatZona({{ $terpilih->id }})">
                                Duplikat
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    wire:click="hapusZona({{ $terpilih->id }})"
                                    wire:confirm="Hapus zona ini?">
                                Hapus Zona
                            </button>
                        </div>
                    </form>
                @else
                    <h6 class="fw-semibold mb-2">Sifat Zona</h6>
                    <p class="text-muted small mb-0">
                        Pilih satu zona di kanvas atau di daftar sebelah kiri untuk mengubah nama,
                        kapasitas, dan catatannya.
                    </p>
                @endif
            </div></div>
        </div>
    </div>

    {{-- Modal Susun DP: harus DI DALAM root <div>, karena modal Livewire
         setelah </div> root tak pernah tampil walau assertSee tetap lulus. --}}
    @if($showDpModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:key="modal-dp">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Susun DP Otomatis</h5>
                        <button type="button" class="btn-close" wire:click="tutupDpModal"></button>
                    </div>
                    <form wire:submit="susunDp">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Jumlah DP</label>
                                    <input type="number" min="1" max="12" class="form-control" wire:model.live="dpJumlah" required>
                                    @error('dpJumlah')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Tinggi Tiap DP (m)</label>
                                    <input type="number" step="0.5" min="1" max="30" class="form-control" wire:model.live="dpTinggi" required>
                                    @error('dpTinggi')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Letak Barisan</label>
                                <select class="form-select" wire:model.live="dpLetak">
                                    <option value="bawah">Di bawah arena</option>
                                    <option value="atas">Di atas arena</option>
                                    <option value="samping">Di samping kiri arena</option>
                                </select>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="dpPanah" wire:model="dpDenganPanah">
                                <label class="form-check-label" for="dpPanah">
                                    Tambahkan penunjuk arah gerak pasukan
                                </label>
                            </div>

                            <div class="alert alert-warning mb-2 py-2 small">
                                <i class="ti ti-alert-triangle me-1"></i>
                                DP dan panah yang sudah ada akan <strong>diganti</strong>. Arena, tribun, pos penilaian,
                                dan zona lain yang sudah Anda gambar tidak tersentuh.
                            </div>
                            <p class="text-muted small mb-0">
                                DP 1 ditempatkan di ujung yang paling dekat arena; nomor berikutnya menjauh.
                                Barisannya bisa digeser satu per satu setelah tersusun.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="tutupDpModal">Batal</button>
                            <button class="btn btn-primary" wire:loading.attr="disabled">
                                <i class="ti ti-wand me-1"></i> Susun {{ $dpJumlah }} DP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($showPapanModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:key="modal-papan">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ukuran Papan</h5>
                        <button type="button" class="btn-close" wire:click="tutupPapanModal"></button>
                    </div>
                    <form wire:submit="simpanPapan">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Lebar (m)</label>
                                    <input type="number" step="0.1" min="1" class="form-control" wire:model="papanLebar" required>
                                    @error('papanLebar')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Tinggi (m)</label>
                                    <input type="number" step="0.1" min="1" class="form-control" wire:model="papanTinggi" required>
                                    @error('papanTinggi')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <p class="text-muted small mb-0">
                                Denahnya tetap proporsional; yang berubah hanya arti satu persen dalam meter,
                                termasuk angka ukuran zona.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="tutupPapanModal">Batal</button>
                            <button class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Potret kanvas jadi PNG.
    //
    // html2canvas diambil saat tombolnya diklik: ±200 KB skrip itu tak perlu
    // ditanggung panitia yang tak pernah mengunduh denah.
    //
    // Kisi latar & penanda pilihan dilepas dulu saat memotret; keduanya alat
    // kerja editor, bukan bagian dari denah yang dibagikan ke peserta.
    $wire.on('unduh-denah-png', async ({ nama }) => {
        const kanvas = document.querySelector('[data-denah-editor]');
        if (!kanvas) return;

        const kisi = kanvas.classList.contains('denah-kisi');
        kanvas.classList.remove('denah-kisi', 'denah-interaktif');
        kanvas.querySelectorAll('.is-terpilih').forEach((el) => el.classList.remove('is-terpilih'));

        try {
            if (!window.html2canvas) {
                await new Promise((ok, gagal) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                    s.onload = ok;
                    s.onerror = gagal;
                    document.head.appendChild(s);
                });
            }

            const hasil = await window.html2canvas(kanvas, { backgroundColor: '#f8fafc', scale: 2 });
            const tautan = document.createElement('a');
            tautan.download = nama + '.png';
            tautan.href = hasil.toDataURL('image/png');
            tautan.click();

            window.beToast?.('Denah diunduh sebagai PNG.', 'success');
        } catch (e) {
            window.beToast?.('Gagal membuat PNG. Pakai unduh PDF sebagai gantinya.', 'error');
        } finally {
            if (kisi) kanvas.classList.add('denah-kisi');
            kanvas.classList.add('denah-interaktif');
            $wire.dispatch('denah-refresh');
        }
    });
</script>
@endscript

@once
    {{-- Layout admin hanya punya @stack('scripts'), tak ada @stack('styles');
         jadi gayanya ditulis langsung, bukan lewat push. --}}
    <style>
        .zone-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            border: 1.5px solid #64748b;
            background: #e2e8f0;
            flex: none;
        }
        .zone-dot.z-arena  { background: #dcfce7; border-color: #16a34a; }
        .zone-dot.z-dp     { background: #dbeafe; border-color: #2563eb; }
        .zone-dot.z-juri   { background: #fef9c3; border-color: #ca8a04; }
        .zone-dot.z-tribun { background: #f1f5f9; border-color: #94a3b8; }
        .zone-dot.z-sekre  { background: #fae8ff; border-color: #a21caf; }
        .zone-dot.z-jalur  { background: #ffe4e6; border-color: #e11d48; }
        .zone-dot.z-arah,
        .zone-dot.z-teks   { background: transparent; }
    </style>
@endonce
