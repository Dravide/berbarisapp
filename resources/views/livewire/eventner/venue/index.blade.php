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

    <div class="row">
        {{-- Panel List Tempat --}}
        <div class="col-lg-8">
            <div class="card w-100 position-relative overflow-hidden">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white fw-semibold">Daftar Tempat</h5>
                </div>
                <div class="card-body p-4">
                    @if($this->venues->isEmpty())
                        <div class="text-center py-5">
                            <h5 class="fw-semibold text-muted">Belum ada Tempat Pelaksanaan</h5>
                            <p>Tambahkan tempat bila lomba digelar di lebih dari satu lokasi &mdash; misalnya LOBB di SMA 1 dan RUKIBRA di SMA 2.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-0 border-0 fw-semibold text-dark">Nama Tempat</th>
                                        <th class="border-0 fw-semibold text-dark">Alamat</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Dipakai</th>
                                        <th class="border-0 fw-semibold text-dark">Tiket</th>
                                        <th class="border-0 fw-semibold text-dark">Gerbang</th>
                                        <th class="border-0 fw-semibold text-dark text-center">Status</th>
                                        <th class="border-0 fw-semibold text-dark text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->venues as $venue)
                                        <tr>
                                            <td class="ps-0">
                                                <h6 class="fw-semibold mb-1 text-primary">
                                                    <i class="ti ti-map-pin"></i> {{ $venue->name }}
                                                </h6>
                                                @if($venue->maps_url)
                                                    <a href="{{ $venue->maps_url }}" target="_blank" class="fs-2 text-muted text-decoration-underline">
                                                        <i class="ti ti-map-2"></i> Buka di Google Maps
                                                    </a>
                                                @else
                                                    <span class="fs-2 text-muted">Belum ada link peta</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($venue->alamat)
                                                    <span class="text-dark">{{ $venue->alamat }}</span>
                                                @else
                                                    <span class="text-muted">&mdash;</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($venue->competition_categories_count > 0)
                                                    <span class="badge bg-light-primary text-primary">{{ $venue->competition_categories_count }} tingkat lomba</span>
                                                @else
                                                    <span class="badge bg-light text-muted">Belum dipakai</span>
                                                @endif
                                            </td>
                                            @php $stat = $this->ticketStats[$venue->id]; @endphp
                                            <td>
                                                @if($venue->ticket_price === null && $venue->ticket_kuota === null)
                                                    <span class="fs-2 text-muted">Ikut harga event</span>
                                                @else
                                                    <div class="fs-2 text-dark">
                                                        @if($venue->ticket_price !== null)
                                                            Rp {{ number_format($venue->ticket_price, 0, ',', '.') }}
                                                        @else
                                                            <span class="text-muted">Harga event</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                <div class="fs-2">
                                                    <span class="text-dark">{{ $stat['sold'] }} terjual</span>
                                                    @if($stat['remaining'] === null)
                                                        <span class="text-muted">&middot; tanpa kuota</span>
                                                    @else
                                                        <span class="{{ $stat['remaining'] > 0 ? 'text-success' : 'text-danger' }}">&middot; sisa {{ $stat['remaining'] }}</span>
                                                    @endif
                                                </div>
                                                @if($stat['pending'] > 0)
                                                    <div class="fs-2 text-warning">{{ $stat['pending'] }} tiket menunggu bayar</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($venue->checkin_token)
                                                    <code class="fs-2">{{ \Illuminate\Support\Str::limit($venue->checkin_token, 14) }}</code>
                                                    <div class="mt-1">
                                                        <button class="btn btn-sm btn-outline-warning py-0 px-2 fs-2"
                                                            wire:click="regenerateCheckinToken({{ $venue->id }})"
                                                            wire:confirm="Rotasi token gerbang {{ $venue->name }}? Tautan lama tidak berlaku lagi.">
                                                            Rotasi
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger py-0 px-2 fs-2"
                                                            wire:click="revokeCheckinToken({{ $venue->id }})"
                                                            wire:confirm="Cabut token gerbang {{ $venue->name }}?">
                                                            Cabut
                                                        </button>
                                                    </div>
                                                @else
                                                    <button class="btn btn-sm btn-outline-primary py-0 px-2 fs-2"
                                                        wire:click="generateCheckinToken({{ $venue->id }})">
                                                        Buat token
                                                    </button>
                                                    <div class="fs-2 text-muted mt-1">Pakai token event</div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button wire:click="toggleActive({{ $venue->id }})" class="btn btn-sm border-0 btn-link">
                                                    @if($venue->is_active)
                                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                                    @endif
                                                </button>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary p-1 me-1" wire:click="edit({{ $venue->id }})" title="Edit Tempat">
                                                    <i class="ti ti-edit fs-4"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="delete({{ $venue->id }})" title="Hapus Tempat"
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
        </div>

        {{-- Panel Form Tempat --}}
        <div class="col-lg-4">
            <div class="card w-100">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-4">{{ $isEditMode ? 'Edit Tempat' : 'Tambah Tempat' }}</h5>
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Nama Tempat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Misal: SMA 1" required>
                            <small class="form-text text-muted">Sebutan singkat yang dipakai panitia &amp; dicetak di tiket.</small>
                            @error('name') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat <span class="text-muted">(Opsional)</span></label>
                            <input type="text" class="form-control" wire:model="alamat" placeholder="Misal: Jl. Melati No. 3, Bandung">
                            @error('alamat') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Link Google Maps <span class="text-muted">(Opsional)</span></label>
                            <input type="url" class="form-control" wire:model="google_maps_url" placeholder="https://maps.app.goo.gl/...">
                            <small class="form-text text-muted">Dicetak sebagai tautan yang bisa dipindai di tiket.</small>
                            @error('google_maps_url') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Latitude <span class="text-muted">(Opsional)</span></label>
                                <input type="text" class="form-control" wire:model="latitude" placeholder="-6.9147">
                                @error('latitude') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Longitude <span class="text-muted">(Opsional)</span></label>
                                <input type="text" class="form-control" wire:model="longitude" placeholder="107.6098">
                                @error('longitude') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <hr class="my-3">
                        <p class="fs-2 text-muted mb-2">
                            Tiket untuk tempat ini &mdash; kosongkan bila tempat ini tidak dijual terpisah.
                            Kalau ada lebih dari satu tempat berjualan, pembeli memilih tempat saat membeli.
                        </p>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Harga Tiket <span class="text-muted">(Rp)</span></label>
                                <input type="number" class="form-control" wire:model="ticket_price" placeholder="35000" min="0" step="1000">
                                <small class="form-text text-muted">Kosong = ikut harga event.</small>
                                @error('ticket_price') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Kuota Tiket</label>
                                <input type="number" class="form-control" wire:model="ticket_kuota" placeholder="100" min="0">
                                <small class="form-text text-muted">Kosong = tanpa batas.</small>
                                @error('ticket_kuota') <span class="text-danger fs-2">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="venue-active" wire:model="is_active">
                            <label class="form-check-label" for="venue-active">Aktifkan tempat ini</label>
                        </div>

                        <div class="d-flex gap-2">
                            @if($isEditMode)
                                <button type="button" class="btn btn-secondary flex-fill" wire:click="resetForm">Batal</button>
                            @endif
                            <button type="submit" class="btn btn-primary flex-fill" wire:loading.attr="disabled">
                                <i class="ti ti-{{ $isEditMode ? 'device-floppy' : 'plus' }}"></i> {{ $isEditMode ? 'Simpan' : 'Tambahkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
