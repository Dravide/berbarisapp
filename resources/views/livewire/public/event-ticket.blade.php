<div>
    {{-- Banner --}}
    <section class="bg-primary-subtle pt-7 pb-5">
        <div class="custom-container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-semibold">Pembelian Tiket Online</span>
                    <h1 class="text-dark fw-semibold fs-10 mb-2">{{ $eventner->nama_event }}</h1>
                    <p class="fs-5 text-muted mb-4">
                        Beli tiket online, bayar via QRIS, dan dapatkan QR masuk langsung di HP Anda.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="btn btn-outline-primary px-4">
                            <i class="ti ti-info-circle me-1"></i> Info Event
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="custom-container">
            @if(session()->has('error'))
                <div class="alert alert-danger border-0 bg-danger-subtle text-danger alert-dismissible fade show mb-4">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($view === 'confirmation' && $paidTicket)
                {{-- CONFIRMATION VIEW --}}
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card w-100 border-0 shadow-lg">
                            <div class="card-header bg-success text-white text-center py-4">
                                <i class="ti ti-circle-check fs-10 mb-2 d-block"></i>
                                <h4 class="text-white fw-semibold mb-1">Pembayaran Berhasil!</h4>
                                <p class="text-white text-opacity-75 mb-0 small">Tiket Anda sudah aktif</p>
                            </div>
                            <div class="card-body p-4 text-center">
                                <div class="mb-4">
                                    <img src="{{ asset('storage/' . $paidTicket->qr_code_path) }}" alt="QR Ticket" class="img-fluid" style="max-width:220px;">
                                </div>
                                <div class="alert alert-info border-0 text-start mb-4">
                                    <p class="fw-semibold mb-1"><i class="ti ti-info-circle me-1"></i> Cara Menggunakan</p>
                                    <ol class="mb-0 ps-3 small">
                                        <li>Simpan/screenshot QR code di atas</li>
                                        <li>Datang ke lokasi event</li>
                                        <li>Tunjukkan QR kepada panitia di gerbang</li>
                                        <li>Panitia akan scan dan memberikan gelang</li>
                                    </ol>
                                </div>
                                <table class="table table-sm mb-0">
                                    <tr><td class="text-muted">Kode Order</td><td class="fw-semibold text-end">{{ $paidTicket->order_code }}</td></tr>
                                    <tr><td class="text-muted">Nama</td><td class="fw-semibold text-end">{{ $paidTicket->buyer_name }}</td></tr>
                                    <tr><td class="text-muted">Jumlah Tiket</td><td class="fw-semibold text-end">{{ $paidTicket->quantity }}</td></tr>
                                    <tr><td class="text-muted">Total Bayar</td><td class="fw-semibold text-end">Rp {{ number_format($paidTicket->total_amount, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Status</td><td class="text-end"><span class="badge bg-success">{{ $paidTicket->status }}</span></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- PURCHASE FORM --}}
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        {{-- Ticket Info --}}
                        <div class="card w-100 mb-4 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                        <i class="ti ti-ticket fs-5"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-semibold mb-0">Tiket Masuk Event</h5>
                                        <p class="text-muted small mb-0">{{ $eventner->nama_event }}</p>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <h4 class="text-primary fw-bold mb-0">Rp {{ number_format($eventner->ticket_price, 0, ',', '.') }}</h4>
                                        <span class="text-muted small">/ tiket</span>
                                    </div>
                                </div>
                                @if($eventner->ticket_description)
                                    <div class="bg-light rounded p-3 small text-muted">
                                        {{ $eventner->ticket_description }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Form --}}
                        <div class="card w-100 border-0 shadow-sm">
                            <div class="card-header bg-transparent border-bottom">
                                <h5 class="fw-semibold mb-0"><i class="ti ti-forms me-2"></i>Formulir Pembelian</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="buyerName" class="form-control" placeholder="Masukkan nama lengkap">
                                    @error('buyerName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                    <input type="email" wire:model="buyerEmail" class="form-control" placeholder="contoh@email.com">
                                    @error('buyerEmail') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">No. WhatsApp <span class="text-danger">*</span></label>
                                    <input type="tel" wire:model="buyerPhone" class="form-control" placeholder="08xxxxxxxxxx">
                                    @error('buyerPhone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Jumlah Tiket <span class="text-danger">*</span></label>
                                    <div class="input-group" style="max-width: 200px;">
                                        <button class="btn btn-outline-secondary" wire:click="$set('quantity', max(1, $quantity - 1))">−</button>
                                        <input type="number" wire:model="quantity" class="form-control text-center" min="1" max="{{ $eventner->ticket_max_per_order ?? 10 }}">
                                        <button class="btn btn-outline-secondary" wire:click="$set('quantity', min({{ $eventner->ticket_max_per_order ?? 10 }}, $quantity + 1))">+</button>
                                    </div>
                                    <small class="text-muted">Maksimal {{ $eventner->ticket_max_per_order ?? 10 }} tiket per transaksi</small>
                                    @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                {{-- Summary --}}
                                <div class="bg-light rounded p-3 mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ $eventner->ticket_price ? number_format($eventner->ticket_price, 0, ',', '.') : 0 }} x {{ $quantity }} tiket</span>
                                        <span class="fw-semibold">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Total Bayar</span>
                                        <span class="fw-bold text-primary fs-5">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                <button wire:click="submitTicket" class="btn btn-primary w-100 py-8 fw-semibold" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submitTicket">
                                        <i class="ti ti-credit-card me-2"></i> Bayar via QRIS
                                    </span>
                                    <span wire:loading wire:target="submitTicket">
                                        <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
