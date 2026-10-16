<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Denah Lapangan</h4>
                    <p class="mb-0 text-muted small">
                        Gambar denah lapangan per tingkat lomba: arena, pos penilaian, tribun, sekretariat,
                        lengkap dengan DP 1&ndash;5. Bisa diunduh PNG/PDF dan diterbitkan ke halaman publik event.
                    </p>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" style="max-height: 80px;">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title fw-semibold mb-0">Daftar Denah</h5>
                <button class="btn btn-primary" wire:click="create">
                    <i class="ti ti-plus me-1"></i> Denah Baru
                </button>
            </div>

            @if($layouts->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-layout-board-split text-muted" style="font-size: 2.5rem;"></i>
                    <p class="text-muted mt-3 mb-1">Belum ada denah.</p>
                    <p class="text-muted small mb-0">
                        Buat denah pertama, lalu gambar arena dan susun DP 1&ndash;5 otomatis dengan satu klik.
                    </p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama Denah</th>
                                <th style="width: 190px;">Tingkat Lomba</th>
                                <th style="width: 120px;">Papan</th>
                                <th style="width: 70px;">Zona</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 230px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($layouts as $l)
                                <tr wire:key="layout-{{ $l->id }}">
                                    <td>
                                        <a href="{{ route('eventner.denah.show', $l->id) }}" class="fw-semibold text-decoration-none">
                                            {{ $l->name }}
                                        </a>
                                        @if($l->venue)
                                            <small class="text-muted d-block"><i class="ti ti-map-pin"></i> {{ $l->venue->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($l->competitionCategory)
                                            <span class="text-muted small">{{ $l->competitionCategory->full_name }}</span>
                                        @else
                                            <span class="badge bg-light text-muted">Denah umum</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ rtrim(rtrim(number_format((float) $l->ground_width, 1, ',', '.'), '0'), ',') }}
                                        &times;
                                        {{ rtrim(rtrim(number_format((float) $l->ground_height, 1, ',', '.'), '0'), ',') }} m
                                    </td>
                                    <td class="text-muted small">{{ $l->zones_count }}</td>
                                    <td>
                                        @if($l->is_published)
                                            <span class="badge bg-success-subtle text-success">Terbit</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">Draf</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('eventner.denah.show', $l->id) }}" class="btn btn-outline-primary" title="Buka editor">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            {{-- Tautan publik hanya saat denahnya terbit:
                                                 denah draf tak punya rute publik. --}}
                                            @if($l->is_published)
                                                <a href="{{ event_url($l->eventner, 'denah') }}"
                                                   class="btn btn-outline-secondary" title="Lihat di halaman publik" target="_blank" rel="noopener">
                                                    <i class="ti ti-external-link"></i>
                                                </a>
                                                <button class="btn btn-outline-secondary" title="Tarik dari publik" wire:click="tarik({{ $l->id }})">
                                                    <i class="ti ti-eye-off"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-outline-success" title="Terbitkan" wire:click="terbitkan({{ $l->id }})">
                                                    <i class="ti ti-send"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-outline-primary" title="Ubah nama &amp; ukuran" wire:click="edit({{ $l->id }})">
                                                <i class="ti ti-settings"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Hapus"
                                                    wire:click="delete({{ $l->id }})"
                                                    wire:confirm="Hapus denah ini? Seluruh zona di dalamnya ikut terhapus.">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal form denah: harus DI DALAM root <div>, karena modal Livewire
         setelah </div> root tak pernah tampil walau assertSee tetap lulus. --}}
    @if($showFormModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);" wire:key="modal-denah">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingId ? 'Ubah Denah' : 'Denah Baru' }}</h5>
                        <button type="button" class="btn-close" wire:click="tutupForm"></button>
                    </div>
                    <form wire:submit="simpan">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Denah <span class="text-danger">*</span></label>
                                <input class="form-control" wire:model="name" placeholder="mis. Denah PBB Tingkat SD" required>
                                @error('name')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Lebar Papan (m)</label>
                                    <input type="number" step="0.1" min="1" class="form-control" wire:model="ground_width" required>
                                    @error('ground_width')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Tinggi Papan (m)</label>
                                    <input type="number" step="0.1" min="1" class="form-control" wire:model="ground_height" required>
                                    @error('ground_height')<span class="text-danger small">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="form-text mb-3">
                                Ukuran lapangan sungguhan. Dipakai hanya untuk menghitung skala meter; denahnya sendiri tetap proporsional.
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tingkat Lomba</label>
                                <select class="form-select" wire:model="competition_category_id">
                                    <option value="">Denah umum (lintas tingkat lomba)</option>
                                    @foreach($kategoris as $k)
                                        <option value="{{ $k->id }}">{{ $k->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Tempat Lomba</label>
                                <select class="form-select" wire:model="eventner_venue_id">
                                    <option value="">Belum ditentukan</option>
                                    @foreach($tempat as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="tutupForm">Batal</button>
                            <button class="btn btn-primary">{{ $editingId ? 'Simpan' : 'Buat Denah' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
