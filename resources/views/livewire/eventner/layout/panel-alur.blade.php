{{--
    Panel kanan saat kanvas dalam mode Alur.

    Dipisah dari show.blade.php supaya tiap mode punya satu berkas yang bisa
    dibaca utuh; digabung, satu form berisi sepuluh blok @if jadi tak terbaca.

    Panel ini menggantikan panel zona, bukan menemani: saat panitia menggambar
    alur, daftar DP dan sifat zona tak relevan dan hanya menambah gulir.
--}}
<div class="card"><div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold mb-0">Alur Gerak Pasukan</h6>
        @if($layout->punya_alur)
            <span class="badge bg-light text-dark">{{ count($layout->alur_titik) }} titik</span>
        @endif
    </div>

    @if(! $layout->punya_alur)
        <p class="text-muted small">
            Belum ada alur. Alur menggambar rute pasukan: lurus, lalu belok kanan atau kiri,
            sampai titik selesai.
        </p>
        <button class="btn btn-primary btn-sm w-100" wire:click="mulaiAlur">
            <i class="ti ti-route me-1"></i> Buat Alur
        </button>
    @else
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="alurPublik"
                   wire:click="toggleAlurPublik" @checked($layout->route_visible)>
            <label class="form-check-label small" for="alurPublik">
                Tampilkan di halaman publik
            </label>
        </div>

        @if($titikTerpilih !== null)
            <form wire:submit="simpanSifatTitik" class="border rounded p-2 mb-3 bg-light-subtle">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold">Titik {{ $titikTerpilih + 1 }}</span>
                    <button type="button" class="btn-close btn-sm" title="Tutup"
                            wire:click="batalPilihTitik"></button>
                </div>

                <div class="mb-2">
                    <label class="form-label small mb-1">Jenis Titik</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        @foreach($jenisTitik as $kunci => $label)
                            <button type="button"
                                    class="btn {{ ($layout->alur_titik[$titikTerpilih]['kind'] ?? '') === $kunci ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    wire:click="ubahJenisTitik('{{ $kunci }}')">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small mb-1">Label</label>
                    <input class="form-control form-control-sm" wire:model="titikLabel"
                           placeholder="mis. Hadap kanan, Hormat">
                    @error('titikLabel')<span class="text-danger small">{{ $message }}</span>@enderror
                </div>

                <div class="mb-2">
                    <label class="form-label small mb-1">Arah Hadap (&deg;)</label>
                    <input type="number" min="0" max="359" class="form-control form-control-sm"
                           wire:model="titikHadap" placeholder="kosongkan bila tanpa panah">
                    <div class="form-text">0&deg; = menghadap atas denah, searah jarum jam.</div>
                    @error('titikHadap')<span class="text-danger small">{{ $message }}</span>@enderror
                </div>

                <div class="d-grid gap-1">
                    <button class="btn btn-primary btn-sm">Simpan Titik</button>
                    <button type="button" class="btn btn-outline-danger btn-sm"
                            wire:click="hapusTitik({{ $titikTerpilih }})"
                            wire:confirm="Hapus titik ini dari alur?">
                        Hapus Titik
                    </button>
                </div>
            </form>
        @else
            <p class="text-muted small">
                Klik satu titik di kanvas untuk mengubah jenis, label, dan arah hadapnya.
            </p>
        @endif

        <h6 class="fw-semibold small mb-2">Urutan Alur</h6>
        <ol class="list-group list-group-flush list-group-numbered mb-3" style="max-height: 300px; overflow-y: auto;">
            @foreach($layout->alur_titik as $i => $t)
                <li wire:key="urutan-{{ $i }}"
                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-2 py-1 {{ $titikTerpilih === $i ? 'active' : '' }}"
                    role="button" wire:click="pilihTitik({{ $i }})">
                    <span class="text-truncate small">
                        {{ $t['label'] !== '' ? $t['label'] : $jenisTitik[$t['kind']] }}
                    </span>
                    @if($t['heading'] !== null)
                        <span class="badge bg-light text-dark ms-auto">{{ $t['heading'] }}&deg;</span>
                    @endif
                </li>
            @endforeach
        </ol>

        <div class="d-grid gap-1">
            <button class="btn btn-outline-secondary btn-sm" wire:click="tambahTitikAkhir">
                <i class="ti ti-plus me-1"></i> Tambah Titik di Ujung
            </button>
            <button class="btn btn-outline-danger btn-sm" wire:click="hapusAlur"
                    wire:confirm="Hapus seluruh alur denah ini?">
                <i class="ti ti-trash me-1"></i> Hapus Alur
            </button>
        </div>

        <div class="form-text mt-2">
            Klik garis di kanvas untuk menyisipkan titik di tengah ruas.
        </div>
    @endif
</div></div>
