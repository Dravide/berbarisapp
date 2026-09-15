@php
    // Label status tiket dipakai di tabel & modal detail — satu tempat saja.
    $ticketLabel = function ($venue) {
        if ($venue->ticket_price === null && $venue->ticket_kuota === null) {
            return ['text' => 'Ikut default event', 'class' => 'text-muted'];
        }

        if ($venue->ticket_price !== null) {
            return ['text' => 'Rp ' . number_format($venue->ticket_price, 0, ',', '.'), 'class' => 'text-dark fw-semibold'];
        }

        return ['text' => 'Kuota saja', 'class' => 'text-muted'];
    };
@endphp

<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Tempat Pelaksanaan</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Tempat Pelaksanaan</li>
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
        <div class="card-header bg-primary d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h5 class="mb-0 text-white fw-semibold">Daftar Tempat</h5>
            <button type="button" class="btn btn-sm btn-light fw-semibold" wire:click="create">
                <i class="ti ti-plus"></i> Tambah Tempat
            </button>
        </div>
        <div class="card-body p-4">
            @if($this->venues->isEmpty())
                <div class="text-center py-5">
                    <h5 class="fw-semibold text-muted">Belum ada Tempat Pelaksanaan</h5>
                    <p class="mb-3">Tambahkan tempat bila lomba digelar di lebih dari satu lokasi &mdash; misalnya LOBB di SMA 1 dan RUKIBRA di SMA 2.</p>
                    <button type="button" class="btn btn-primary" wire:click="create">
                        <i class="ti ti-plus"></i> Tambah Tempat
                    </button>
                </div>
            @else
                {{-- Tabel ringkas: alamat panjang, peta, dan token gerbang ada di modal Detail. --}}
                <div class="table-responsive">
                    <table class="table align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-0 border-0 fw-semibold text-dark">Nama Tempat</th>
                                <th class="border-0 fw-semibold text-dark">Alamat</th>
                                <th class="border-0 fw-semibold text-dark text-center">Tingkat</th>
                                <th class="border-0 fw-semibold text-dark">Tiket</th>
                                <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->venues as $venue)
                                @php $stat = $this->ticketStats[$venue->id]; @endphp
                                <tr>
                                    <td class="ps-0">
                                        <h6 class="fw-semibold mb-0 text-primary">
                                            <i class="ti ti-map-pin"></i> {{ $venue->name }}
                                        </h6>
                                        @if($venue->checkin_token)
                                            <span class="fs-2 text-success"><i class="ti ti-door-enter"></i> Gerbang sendiri</span>
                                        @else
                                            <span class="fs-2 text-muted"><i class="ti ti-door-enter"></i> Pakai token event</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 260px;">
                                        @if($venue->alamat)
                                            {{-- Teks penuh ada di modal Detail; di sini dipotong agar kolom lain lega. --}}
                                            <div class="text-truncate" title="{{ $venue->alamat }}">{{ $venue->alamat }}</div>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($venue->competition_categories_count > 0)
                                            <span class="badge bg-light-primary text-primary">{{ $venue->competition_categories_count }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    @php $tiket = $ticketLabel($venue); @endphp
                                    <td>
                                        <div class="{{ $tiket['class'] }} fs-2">{{ $tiket['text'] }}</div>
                                        <div class="fs-2">
                                            <span class="text-dark">{{ $stat['sold'] }} terjual</span>
                                            @if($stat['remaining'] === null)
                                                <span class="text-muted">&middot; tanpa kuota</span>
                                            @else
                                                <span class="{{ $stat['remaining'] > 0 ? 'text-success' : 'text-danger' }}">&middot; sisa {{ $stat['remaining'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" wire:click="toggleActive({{ $venue->id }})" class="btn btn-sm border-0 btn-link p-0">
                                            @if($venue->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary p-1 me-1" wire:click="openDetail({{ $venue->id }})" title="Detail Tempat">
                                            <i class="ti ti-info-circle fs-4"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $venue->id }})" title="Edit Tempat">
                                            <i class="ti ti-edit fs-4"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $venue->id }})" title="Hapus Tempat"
                                            wire:confirm="Hapus tempat ini?">
                                            <i class="ti ti-trash fs-4"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Detail Tempat --}}
    @if($showDetailModal && $this->detailVenue)
        @php
            $venue = $this->detailVenue;
            $stat = $this->ticketStats[$venue->id];
        @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="closeDetailModal">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white fw-semibold">
                            <i class="ti ti-map-pin"></i> {{ $venue->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDetailModal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted fs-2 mb-1">Alamat</div>
                                @if($venue->alamat)
                                    <p class="mb-1 text-dark">{{ $venue->alamat }}</p>
                                @else
                                    <p class="mb-1 text-muted">Belum diisi</p>
                                @endif
                                @if($venue->google_maps_url)
                                    <a href="{{ $venue->google_maps_url }}" target="_blank" rel="noopener" class="fs-2 text-decoration-underline">
                                        <i class="ti ti-map-2"></i> Buka di Google Maps
                                    </a>
                                @else
                                    <span class="fs-2 text-muted">Belum ada link peta</span>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted fs-2 mb-1">Koordinat</div>
                                @if($venue->latitude || $venue->longitude)
                                    <p class="mb-1 text-dark">{{ $venue->latitude ?: '-' }}, {{ $venue->longitude ?: '-' }}</p>
                                @else
                                    <p class="mb-1 text-muted">Belum diisi</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted fs-2 mb-1">Dipakai tingkat lomba</div>
                                @if($venue->competition_categories_count > 0)
                                    <p class="mb-0 text-dark">{{ $venue->competition_categories_count }} tingkat lomba
                                        <span class="text-muted fs-2">&mdash; pindahkan tingkatnya bila tempat ini ingin dihapus.</span>
                                    </p>
                                @else
                                    <p class="mb-0 text-muted">Belum dipakai tingkat lomba mana pun</p>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <div class="text-muted fs-2 mb-1">Status</div>
                                <button type="button" wire:click="toggleActive({{ $venue->id }})" class="btn btn-sm border-0 btn-link p-0">
                                    @if($venue->is_active)
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                    @endif
                                </button>
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                                <div class="text-muted fs-2 mb-2">Tiket tempat ini</div>
                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted fs-2">Harga</div>
                                        <div class="fw-semibold text-dark">
                                            @if($venue->ticket_price !== null)
                                                Rp {{ number_format($venue->ticket_price, 0, ',', '.') }}
                                            @else
                                                <span class="text-muted fw-normal">Ikut harga default event</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted fs-2">Kuota</div>
                                        <div class="fw-semibold text-dark">
                                            {{ $venue->ticket_kuota === null ? 'Tanpa batas' : number_format($venue->ticket_kuota, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted fs-2">Terjual</div>
                                        <div class="fw-semibold text-dark">{{ $stat['sold'] }} tiket</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted fs-2">Sisa</div>
                                        <div class="fw-semibold {{ $stat['remaining'] === null ? 'text-muted' : ($stat['remaining'] > 0 ? 'text-success' : 'text-danger') }}">
                                            {{ $stat['remaining'] === null ? 'Tanpa kuota' : $stat['remaining'] . ' tiket' }}
                                        </div>
                                    </div>
                                </div>
                                @if($stat['pending'] > 0)
                                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 fs-2">
                                        <i class="ti ti-clock"></i> {{ $stat['pending'] }} tiket masih menunggu pembayaran.
                                    </div>
                                @endif
                            </div>

                            <div class="col-12">
                                <hr class="my-2">
                                <div class="text-muted fs-2 mb-1">Token Gerbang</div>
                                <p class="fs-2 text-muted mb-2">
                                    Token khusus tempat ini, terpisah dari token event. Berguna saat tiap tempat punya petugas gerbang sendiri
                                    &mdash; merotasi token event tidak ikut mematikan gerbang ini.
                                </p>
                                @if($venue->checkin_token)
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <code class="fs-2">{{ \Illuminate\Support\Str::limit($venue->checkin_token, 20) }}</code>
                                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2 fs-2"
                                            wire:click="regenerateCheckinToken({{ $venue->id }})"
                                            wire:confirm="Rotasi token gerbang {{ $venue->name }}? Tautan lama tidak berlaku lagi.">
                                            Rotasi
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fs-2"
                                            wire:click="revokeCheckinToken({{ $venue->id }})"
                                            wire:confirm="Cabut token gerbang {{ $venue->name }}?">
                                            Cabut
                                        </button>
                                    </div>
                                @else
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 fs-2"
                                            wire:click="generateCheckinToken({{ $venue->id }})">
                                            Buat token gerbang
                                        </button>
                                        <span class="fs-2 text-muted">Saat ini memakai token event.</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeDetailModal">Tutup</button>
                        <button type="button" class="btn btn-primary" wire:click="edit({{ $venue->id }})">
                            <i class="ti ti-edit"></i> Edit Tempat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Form Tambah / Edit Tempat --}}
    @if($showFormModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:keydown.escape="closeFormModal">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white fw-semibold">{{ $isEditMode ? 'Edit Tempat' : 'Tambah Tempat' }}</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeFormModal" aria-label="Tutup"></button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                            <div class="mb-3">
                                <label class="form-label">Nama Tempat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="name" placeholder="Misal: SMA 1" required>
                                <small class="form-text text-muted">Sebutan singkat yang dipakai panitia &amp; dicetak di tiket.</small>
                                @error('name') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat <span class="text-muted">(Opsional)</span></label>
                                <input type="text" class="form-control" wire:model="alamat" placeholder="Misal: Jl. Melati No. 3, Bandung">
                                @error('alamat') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Link Google Maps <span class="text-muted">(Opsional)</span></label>
                                <input type="url" class="form-control" wire:model="google_maps_url" placeholder="https://maps.app.goo.gl/...">
                                <small class="form-text text-muted">Dicetak sebagai tautan yang bisa dipindai di tiket.</small>
                                @error('google_maps_url') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Latitude <span class="text-muted">(Opsional)</span></label>
                                    <input type="text" class="form-control" wire:model="latitude" placeholder="-6.9147">
                                    @error('latitude') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Longitude <span class="text-muted">(Opsional)</span></label>
                                    <input type="text" class="form-control" wire:model="longitude" placeholder="107.6098">
                                    @error('longitude') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <hr class="my-3">
                            <p class="fs-2 text-muted mb-2">
                                Tiket untuk tempat ini &mdash; kosongkan bila tempat ini tidak dijual terpisah.
                                Kalau ada lebih dari satu tempat berjualan, pembeli memilih tempat saat membeli.
                                Harga di sini <strong>menang</strong> atas harga default di
                                <a href="{{ route('eventner.tickets.settings') }}">Pengaturan Tiket</a>.
                            </p>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Harga Tiket <span class="text-muted">(Rp)</span></label>
                                    <input type="number" class="form-control" wire:model="ticket_price" placeholder="35000" min="0" step="1000">
                                    <small class="form-text text-muted">Kosong = pakai harga default event.</small>
                                    @error('ticket_price') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Kuota Tiket</label>
                                    <input type="number" class="form-control" wire:model="ticket_kuota" placeholder="100" min="0">
                                    <small class="form-text text-muted">Kosong = tanpa batas.</small>
                                    @error('ticket_kuota') <span class="text-danger fs-2 d-block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="venue-active" wire:model="is_active">
                                <label class="form-check-label" for="venue-active">Aktifkan tempat ini</label>
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
