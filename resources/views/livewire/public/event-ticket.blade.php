<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-primary text-white py-12 md:py-16">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-ticket"></i>
                Pembelian Tiket Online
            </span>
            <h1 class="font-display text-2xl font-extrabold tracking-tight sm:text-3xl md:text-4xl max-w-4xl leading-tight">
                {{ $eventner->nama_event }}
            </h1>
            <p class="mt-2.5 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Beli tiket online, bayar via QRIS otomatis, dan dapatkan QR code tiket masuk langsung di HP Anda.
            </p>
            <div class="mt-4">
                <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                    <i class="ti ti-arrow-left"></i> Kembali Ke Detail Event
                </a>
            </div>
        </div>
    </div>

    {{-- ========== MAIN CONTENT ========== --}}
    <div class="container-landing py-8">
        @if(session()->has('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-sm font-semibold flex items-center gap-2">
                <i class="ti ti-alert-circle text-lg"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ========== VIEW: SCHEDULED (belum mulai) ========== --}}
        @if($view === 'scheduled')
            <div class="flex justify-center">
                <div class="w-full max-w-lg surface-card overflow-hidden">
                    <div class="bg-amber-500 text-white p-8 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 shadow-sm mb-3 mx-auto">
                            <i class="ti ti-clock text-3xl"></i>
                        </div>
                        <h3 class="font-display text-lg font-bold text-white mb-1">Penjualan Tiket Belum Dibuka</h3>
                        <p class="text-sm text-white/80">Tiket akan tersedia mulai:</p>
                        <p class="text-lg font-bold mt-2">{{ \Carbon\Carbon::parse($eventner->ticket_start)->translatedFormat('l, d M Y - H:i') }} WIB</p>
                    </div>
                    <div class="p-6 text-center">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-primary py-3 px-6 font-bold text-sm inline-flex items-center gap-1.5 text-decoration-none">
                            <i class="ti ti-arrow-left"></i> Kembali ke Event
                        </a>
                    </div>
                </div>
            </div>

            {{-- ========== VIEW: CLOSED (sudah berakhir) ========== --}}
        @elseif($view === 'closed')
            <div class="flex justify-center">
                <div class="w-full max-w-lg surface-card overflow-hidden">
                    <div class="bg-red-500 text-white p-8 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 shadow-sm mb-3 mx-auto">
                            <i class="ti ti-lock text-3xl"></i>
                        </div>
                        <h3 class="font-display text-lg font-bold text-white mb-1">Penjualan Tiket Telah Berakhir</h3>
                        <p class="text-sm text-white/80">Periode penjualan tiket sudah ditutup.</p>
                    </div>
                    <div class="p-6 text-center">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-primary py-3 px-6 font-bold text-sm inline-flex items-center gap-1.5 text-decoration-none">
                            <i class="ti ti-arrow-left"></i> Kembali ke Event
                        </a>
                    </div>
                </div>
            </div>

            {{-- ========== VIEW: PAYMENT (QRIS) ========== --}}
        @elseif($view === 'payment')
            <div class="flex justify-center" wire:poll.5s="checkPaymentStatus">
                <div class="w-full max-w-md">
                    <div class="surface-card overflow-hidden">
                        {{-- Payment Header --}}
                        <div class="bg-primary text-white p-6 text-center relative overflow-hidden">
                            <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/5 blur-xl"></div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-white shadow-sm mb-3 mx-auto">
                                <i class="ti ti-qrcode text-2xl"></i>
                            </div>
                            <h3 class="font-display text-base font-bold text-white mb-0.5">Scan &amp; Bayar</h3>
                            <p class="text-xs text-white/80">Scan QR Code di bawah menggunakan aplikasi e-wallet Anda</p>
                        </div>

                        {{-- Payment Body --}}
                        <div class="p-6 text-center">
                            {{-- QR Code Image Box --}}
                            <div class="inline-block bg-white border-2 border-outline-variant/60 rounded-2xl p-4 shadow-sm mb-6">
                                <img src="{{ $qrImageUrl }}" alt="QRIS Payment" class="max-w-[220px] w-full mx-auto">
                            </div>

                            {{-- Payment Amount --}}
                            <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-xl p-4 mb-4">
                                <span class="text-xs text-on-surface-variant font-medium block mb-1">Total Pembayaran</span>
                                <h2 class="text-2xl font-extrabold text-emerald-600 leading-tight">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</h2>
                                <span class="text-[11px] text-on-surface-variant font-medium block mt-1">{{ $quantity }} tiket × Rp {{ number_format($eventner->ticket_price, 0, ',', '.') }}</span>
                            </div>

                            {{-- Timer --}}
                            <div class="mb-4" x-data="{
                                expiry: '{{ $expiryTime }}',
                                remaining: '',
                                expired: false,
                                init() {
                                    this.updateTimer();
                                    setInterval(() => this.updateTimer(), 1000);
                                },
                                updateTimer() {
                                    const exp = new Date(this.expiry).getTime();
                                    const now = Date.now();
                                    const diff = exp - now;
                                    if (diff <= 0) {
                                        this.remaining = '00:00';
                                        this.expired = true;
                                        return;
                                    }
                                    const m = Math.floor(diff / 60000);
                                    const s = Math.floor((diff % 60000) / 1000);
                                    this.remaining = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                                }
                            }">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 border border-amber-500/20 rounded-full text-xs font-bold text-amber-600">
                                    <i class="ti ti-clock"></i>
                                    Kedaluwarsa dalam: <span x-text="remaining" class="font-mono text-sm leading-none"></span>
                                </div>
                            </div>

                            {{-- Waiting Status --}}
                            <div class="flex items-center justify-center gap-2 py-3 px-4 bg-amber-500/5 border border-amber-500/15 rounded-xl mb-6">
                                <span class="h-4 w-4 border-2 border-amber-500 border-t-transparent rounded-full animate-spin shrink-0"></span>
                                <span class="text-amber-800 text-xs font-semibold">Menunggu Pembayaran...</span>
                            </div>

                            {{-- Instructions --}}
                            <div class="text-left bg-surface-container-low border border-outline-variant/40 rounded-xl p-4 mb-4">
                                <span class="text-xs font-bold text-deep-slate inline-flex items-center gap-1 mb-2">
                                    <i class="ti ti-info-circle text-emerald-600"></i> Cara Bayar:
                                </span>
                                <ol class="list-decimal pl-4 text-xs text-on-surface-variant space-y-1.5 leading-relaxed">
                                    <li>Buka aplikasi e-wallet (GoPay, OVO, DANA, dll) atau M-Banking</li>
                                    <li>Pilih menu <strong>Scan QRIS / Bayar</strong></li>
                                    <li>Scan QR Code di atas</li>
                                    <li>Periksa nominal pembayaran sudah sesuai dan konfirmasi</li>
                                </ol>
                            </div>

                            {{-- Cancel Button --}}
                            <button wire:click="resetPayment" class="text-xs font-bold text-on-surface-variant hover:text-red-500 transition inline-flex items-center gap-1 bg-transparent border-none cursor-pointer">
                                <i class="ti ti-arrow-left"></i> Batal &amp; Kembali
                            </button>

                            {{-- QRIS Logo --}}
                            <div class="mt-6 border-t border-outline-variant/30 pt-4 flex justify-center">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" class="h-5 opacity-60" alt="QRIS">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ========== VIEW: CONFIRMATION (SUCCESS) ========== --}}
        @elseif($view === 'confirmation' && $paidTicket)
            <div class="flex justify-center">
                <div class="w-full max-w-lg">
                    <div class="surface-card overflow-hidden">
                        {{-- Success Header --}}
                        <div class="bg-emerald-600 text-white p-8 text-center relative overflow-hidden">
                            <div class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/5 blur-xl"></div>
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-white shadow-sm mb-3 mx-auto">
                                <i class="ti ti-circle-check text-3xl"></i>
                            </div>
                            <h3 class="font-display text-lg font-bold text-white mb-0.5">Pembayaran Berhasil!</h3>
                            <p class="text-sm text-white/80">Tiket masuk online Anda telah aktif</p>
                        </div>

                        {{-- Success Body --}}
                        <div class="p-6 text-center">
                            {{-- QR Ticket Image --}}
                            <div class="inline-block bg-white border border-outline-variant/50 rounded-2xl p-4 shadow-sm mb-6">
                                <img src="{{ asset('storage/' . $paidTicket->qr_code_path) }}" alt="QR Ticket" class="max-w-[200px] w-full mx-auto">
                            </div>

                            {{-- Info Box --}}
                            <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 text-left mb-6">
                                <span class="text-sm font-bold text-deep-slate inline-flex items-center gap-1 mb-2">
                                    <i class="ti ti-info-circle text-primary"></i> Cara Penggunaan Tiket:
                                </span>
                                <ol class="list-decimal pl-4 text-xs text-on-surface-variant space-y-1.5 leading-relaxed">
                                    <li>Simpan atau <strong>screenshot QR Code</strong> di atas.</li>
                                    <li>Tunjukkan QR Code ini kepada panitia saat kedatangan di gerbang masuk event.</li>
                                    <li>Panitia akan men-scan tiket Anda untuk memverifikasi dan memberikan gelang masuk.</li>
                                </ol>
                            </div>

                            {{-- Transaction Details Table --}}
                            <div class="border border-outline-variant/40 rounded-xl overflow-hidden mb-6">
                                <table class="w-full text-sm border-collapse text-left">
                                    <tbody class="divide-y divide-outline-variant/30">
                                        <tr>
                                            <td class="px-4 py-3 bg-surface-container-low text-on-surface-variant font-medium">Kode Order</td>
                                            <td class="px-4 py-3 text-deep-slate font-bold text-right font-mono">{{ $paidTicket->order_code }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 bg-surface-container-low text-on-surface-variant font-medium">Nama Pembeli</td>
                                            <td class="px-4 py-3 text-deep-slate font-bold text-right">{{ $paidTicket->buyer_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 bg-surface-container-low text-on-surface-variant font-medium">Jumlah Tiket</td>
                                            <td class="px-4 py-3 text-deep-slate font-bold text-right">{{ $paidTicket->quantity }} tiket</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 bg-surface-container-low text-on-surface-variant font-medium">Total Bayar</td>
                                            <td class="px-4 py-3 text-emerald-600 font-bold text-right">Rp {{ number_format($paidTicket->total_amount, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 bg-surface-container-low text-on-surface-variant font-medium">Status</td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-600 border border-emerald-500/25">
                                                    {{ $paidTicket->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('event.detail', $eventner->slug) }}" class="btn-secondary py-3 px-4 font-bold text-sm w-full text-center text-decoration-none">
                                    <i class="ti ti-home"></i> Kembali Ke Detail Event
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        {{-- ========== VIEW: FORM (DEFAULT) ========== --}}
        @else
            <div class="grid gap-8 md:grid-cols-3">
                {{-- Left: Event Card --}}
                <div class="md:col-span-1">
                    @php
                        $tanggalLabel = $eventner->tanggal
                            ? \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('l, d F Y')
                            : null;
                    @endphp
                    <div class="surface-card overflow-hidden sticky top-24">
                        @if($eventner->poster)
                            <img src="{{ asset('storage/' . $eventner->poster) }}" alt="{{ $eventner->nama_event }}" class="w-full max-h-56 object-cover border-b border-outline-variant/30">
                        @endif

                        <div class="p-6">
                            <div class="flex items-center gap-3.5 mb-4">
                                @if($eventner->logo_event)
                                    <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="logo" class="h-12 w-12 rounded-xl object-cover border border-outline-variant/30">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary border border-outline-variant/30">
                                        <i class="ti ti-calendar text-xl"></i>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <h3 class="font-display text-sm font-bold text-deep-slate leading-tight mb-0.5 truncate">{{ $eventner->nama_event }}</h3>
                                    <span class="text-xs text-on-surface-variant font-medium block">Penyelenggara: {{ $eventner->diselenggarakan_oleh }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 p-4 bg-surface-container-low rounded-xl mb-4 text-xs font-semibold">
                                @if($tanggalLabel)
                                    <div class="flex items-center gap-2 text-deep-slate">
                                        <i class="ti ti-calendar text-base text-primary"></i>
                                        <span>{{ $tanggalLabel }}</span>
                                    </div>
                                @endif
                                @if($eventner->venue || $eventner->lokasi)
                                    <div class="flex items-start gap-2 text-deep-slate">
                                        <i class="ti ti-map-pin text-base text-primary mt-0.5 shrink-0"></i>
                                        <span>{{ $eventner->venue }} @if($eventner->venue && $eventner->lokasi) &mdash; @endif {{ $eventner->lokasi }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex justify-between items-center border-t border-outline-variant/30 pt-4">
                                <div>
                                    <span class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider block">Harga Tiket</span>
                                    <h4 class="text-lg font-bold text-primary mb-0 mt-0.5">Rp {{ number_format($eventner->ticket_price, 0, ',', '.') }} <span class="text-xs text-on-surface-variant font-normal">/ tiket</span></h4>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-600 border border-emerald-500/25">
                                    <i class="ti ti-circle-check-filled"></i> Tersedia
                                </span>
                            </div>

                            @if($eventner->ticket_description)
                                <div class="mt-4 p-3 bg-primary/5 border-l-2 border-primary rounded-r-lg text-xs text-on-surface-variant leading-relaxed">
                                    {{ $eventner->ticket_description }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right: Form Card --}}
                <div class="md:col-span-2">
                    <div class="surface-card">
                        <div class="px-6 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-base font-bold text-deep-slate inline-flex items-center gap-2">
                                <i class="ti ti-file-text text-primary"></i>
                                Formulir Pembelian Tiket
                            </h3>
                        </div>

                        <div class="p-6">
                            {{-- Name Input --}}
                            <div class="mb-4">
                                <label class="text-sm font-bold text-deep-slate block mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="buyerName" placeholder="Masukkan nama lengkap pembeli" class="field-input w-full">
                                @error('buyerName') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Email Input --}}
                            <div class="mb-4">
                                <label class="text-sm font-bold text-deep-slate block mb-1.5">Email <span class="text-red-500">*</span></label>
                                <input type="email" wire:model="buyerEmail" placeholder="contoh@email.com" class="field-input w-full">
                                <span class="text-[10px] text-on-surface-variant font-medium mt-1 block leading-normal">Bukti tiket (QR Code + kode order) akan dikirim ke email ini setelah pembayaran.</span>
                                @error('buyerEmail') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Quantity Counter --}}
                            <div class="mb-6">
                                <label class="text-sm font-bold text-deep-slate block mb-1.5">Jumlah Tiket <span class="text-red-500">*</span></label>
                                <div class="flex max-w-[160px] border border-outline-variant/60 rounded-lg overflow-hidden h-11 bg-surface">
                                    <button type="button" wire:click="$set('quantity', Math.max(1, $quantity - 1))" class="w-12 flex items-center justify-center font-bold text-lg text-primary hover:bg-primary/5 border-r border-outline-variant/60 transition cursor-pointer select-none">−</button>
                                    <input type="number" wire:model="quantity" class="flex-1 text-center font-bold text-sm text-deep-slate border-none outline-none h-full w-full bg-transparent px-1 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" min="1" max="{{ $eventner->ticket_max_per_order ?? 10 }}">
                                    <button type="button" wire:click="$set('quantity', Math.min({{ $eventner->ticket_max_per_order ?? 10 }}, $quantity + 1))" class="w-12 flex items-center justify-center font-bold text-lg text-primary hover:bg-primary/5 border-l border-outline-variant/60 transition cursor-pointer select-none">+</button>
                                </div>
                                <span class="text-xs text-on-surface-variant font-medium mt-1.5 block">Maksimal {{ $eventner->ticket_max_per_order ?? 10 }} tiket per order</span>
                                @error('quantity') <span class="text-red-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Price Summary Box --}}
                            <div class="bg-surface-container-low border border-outline-variant/40 rounded-xl p-4 mb-6">
                                <div class="flex justify-between items-center text-xs font-semibold text-on-surface-variant mb-2">
                                    <span>Rp {{ number_format($eventner->ticket_price, 0, ',', '.') }} x {{ $quantity }} tiket</span>
                                    <span>Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                </div>
                                <div class="border-t border-outline-variant/30 pt-2 flex justify-between items-center">
                                    <span class="text-sm font-bold text-deep-slate">Total Pembayaran</span>
                                    <span class="text-lg font-extrabold text-primary">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <button wire:click="submitTicket" class="btn-primary py-3.5 px-6 font-bold text-sm w-full text-center inline-flex justify-center cursor-pointer shadow-md hover:shadow-lg disabled:opacity-60" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitTicket">
                                    <i class="ti ti-qrcode text-base"></i> Lanjutkan &amp; Bayar via QRIS
                                </span>
                                <span wire:loading wire:target="submitTicket" class="inline-flex items-center gap-2">
                                    <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    Memproses...
                                </span>
                            </button>

                            <div class="text-center mt-4">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" class="h-5 opacity-60 mx-auto" alt="QRIS">
                                <p class="text-[10px] text-on-surface-variant font-medium mt-1">Pembayaran aman dengan QRIS GoPay</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
