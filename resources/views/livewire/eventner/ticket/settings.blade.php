<div>
    {{-- Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Pengaturan Tiket</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item">{{ $eventner->nama_event }}</li>
                            <li class="breadcrumb-item"><a class="text-decoration-none" href="{{ route('eventner.tickets.index') }}">Tiket</a></li>
                            <li class="breadcrumb-item active">Pengaturan</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card w-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white fw-semibold"><i class="ti ti-settings me-2"></i>Konfigurasi Tiket Online</h5>
                </div>
                <div class="card-body p-4">
                    {{-- Toggle --}}
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" wire:model="ticket_active" id="ticketActive" style="width:48px;height:24px;">
                        <label class="form-check-label fw-semibold ms-2" for="ticketActive">
                            Aktifkan Penjualan Tiket Online
                        </label>
                        <p class="text-muted small mb-0 mt-1">Jika aktif, pengunjung bisa membeli tiket melalui halaman event.</p>
                    </div>

                    @if($ticket_active)
                        <div class="border rounded p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Harga Per Tiket (Rp) <span class="text-danger">*</span></label>
                                <input type="number" wire:model="ticket_price" class="form-control" placeholder="Contoh: 50000" min="0" step="1000">
                                @error('ticket_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Maksimal Tiket Per Transaksi</label>
                                <input type="number" wire:model="ticket_max_per_order" class="form-control" min="1" max="100">
                                @error('ticket_max_per_order') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Keterangan Tiket (Opsional)</label>
                                <textarea wire:model="ticket_description" class="form-control" rows="3" placeholder="Informasi tambahan tentang tiket, syarat & ketentuan, dll."></textarea>
                                @error('ticket_description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 d-flex gap-2">
                        <button wire:click="save" class="btn btn-primary px-4 fw-semibold" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save"><i class="ti ti-device-floppy me-1"></i> Simpan Pengaturan</span>
                            <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...</span>
                        </button>
                        <a href="{{ route('eventner.tickets.index') }}" class="btn btn-outline-secondary px-4">Kembali</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
