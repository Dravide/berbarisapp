<div>
    {{-- Hero Banner --}}
    <div class="section" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 50px 0 30px; position: relative; overflow: hidden;">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <span style="display:inline-block; background: rgba(0,0,0,0.2); color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">
                        <i class="fa fa-ticket"></i> Pembelian Tiket Online
                    </span>
                    <h1 class="wow fadeInUp" style="color: #fff; font-size: clamp(24px, 5vw, 36px);">{{ $eventner->nama_event }}</h1>
                    <p class="wow fadeInUp" style="color: rgba(255,255,255,0.9); font-size: 15px; margin-top: 8px;">
                        Beli tiket online, bayar via QRIS, dan dapatkan QR masuk langsung di HP Anda.
                    </p>
                    <div class="wow fadeInUp mt-3">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="zubuz-default-btn" style="background: rgba(255,255,255,0.25);">
                            <span><i class="fa fa-info-circle"></i> Info Event</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section zubuz-section-padding3">
        <div class="container">
            @if(session()->has('error'))
                <div class="wow fadeInUp" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px;">
                    {{ session('error') }}
                </div>
            @endif

            @if($view === 'payment')
                {{-- PAYMENT VIEW: QR Code --}}
                <div class="row justify-content-center" wire:poll.5s="checkPaymentStatus">
                    <div class="col-lg-5">
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            {{-- Header --}}
                            <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 20px; text-align: center;">
                                <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                                    <i class="fa fa-qrcode" style="font-size: 24px; color: #fff;"></i>
                                </div>
                                <h4 style="color: #fff; font-weight: 600; margin-bottom: 4px;">Scan & Bayar</h4>
                                <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 14px;">Scan QR code di bawah dengan e-wallet</p>
                            </div>

                            {{-- QR Code --}}
                            <div style="padding: 24px; text-align: center;">
                                <div style="background: #fff; border: 2px solid #e5e7eb; border-radius: 16px; padding: 16px; display: inline-block; margin-bottom: 16px;">
                                    <img src="{{ $qrImageUrl }}" alt="QRIS Payment" style="max-width: 220px; width: 100%;">
                                </div>

                                {{-- Amount --}}
                                <div style="background: rgba(16,185,129,0.06); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                                    <p style="color: #6b7280; font-size: 13px; margin-bottom: 4px;">Total Pembayaran</p>
                                    <h3 style="color: #059669; font-weight: 800; margin: 0;">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</h3>
                                    <p style="color: #6b7280; font-size: 12px; margin-top: 4px;">{{ $quantity }} tiket × Rp {{ number_format($eventner->ticket_price, 0, ',', '.') }}</p>
                                </div>

                                {{-- Timer --}}
                                <div style="margin-bottom: 16px;" x-data="{ 
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
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <i class="fa fa-clock" style="color: #f59e0b;"></i>
                                        <span style="font-weight: 600; font-size: 14px;" :class="expired ? 'text-danger' : ''">
                                            Kedaluwarsa dalam: <span x-text="remaining" style="font-family: monospace; font-size: 16px;"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: #fffbeb; border-radius: 10px; margin-bottom: 16px;">
                                    <span style="display: inline-block; width: 14px; height: 14px; border: 2px solid #f59e0b; border-top: 2px solid transparent; border-radius: 50%; animation: spin 0.8s linear infinite;"></span>
                                    <span style="color: #92400e; font-size: 14px; font-weight: 500;">Menunggu pembayaran...</span>
                                </div>

                                {{-- Instructions --}}
                                <div style="text-align: left; background: #f8fafc; border-radius: 10px; padding: 14px;">
                                    <p style="font-weight: 600; font-size: 13px; margin-bottom: 8px;">
                                        <i class="fa fa-info-circle" style="color: #059669;"></i> Cara Bayar:
                                    </p>
                                    <ol style="margin: 0; padding-left: 18px; font-size: 13px; color: #4b5563; line-height: 1.8;">
                                        <li>Buka aplikasi e-wallet (GoPay, OVO, DANA, dll)</li>
                                        <li>Pilih menu <strong>Scan QR / Bayar</strong></li>
                                        <li>Scan QR code di atas</li>
                                        <li>Konfirmasi pembayaran</li>
                                    </ol>
                                </div>

                                {{-- Cancel --}}
                                <button wire:click="resetPayment" style="background: none; border: none; color: #6b7280; font-size: 13px; margin-top: 14px; cursor: pointer;">
                                    <i class="fa fa-arrow-left"></i> Batal & Kembali
                                </button>

                                {{-- QRIS Logo --}}
                                <div style="margin-top: 14px;">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" height="18" alt="QRIS" style="opacity: 0.5;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($view === 'confirmation' && $paidTicket)
                {{-- CONFIRMATION VIEW --}}
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            {{-- Success Header --}}
                            <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 30px; text-align: center;">
                                <i class="fa fa-check-circle" style="font-size: 48px; color: #fff; display: block; margin-bottom: 10px;"></i>
                                <h4 style="color: #fff; font-weight: 600; margin-bottom: 4px;">Pembayaran Berhasil!</h4>
                                <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 14px;">Tiket Anda sudah aktif</p>
                            </div>

                            {{-- QR Code --}}
                            <div style="padding: 30px; text-align: center;">
                                <div style="margin-bottom: 24px;">
                                    <img src="{{ asset('storage/' . $paidTicket->qr_code_path) }}" alt="QR Ticket" style="max-width: 200px;">
                                </div>

                                {{-- Info Box --}}
                                <div style="background: rgba(0,114,255,0.06); border-radius: 12px; padding: 16px; text-align: left; margin-bottom: 24px;">
                                    <p style="font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                        <i class="fa fa-info-circle" style="color: #0072FF; margin-right: 4px;"></i> Cara Menggunakan
                                    </p>
                                    <ol style="margin: 0; padding-left: 20px; font-size: 13px; color: #4b5563;">
                                        <li>Simpan/screenshot QR code di atas</li>
                                        <li>Datang ke lokasi event</li>
                                        <li>Tunjukkan QR kepada panitia di gerbang</li>
                                        <li>Panitia akan scan dan memberikan gelang</li>
                                    </ol>
                                </div>

                                {{-- Details --}}
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Kode Order</td>
                                        <td style="padding: 10px 0; text-align: right; font-weight: 600; font-size: 14px;">{{ $paidTicket->order_code }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Nama</td>
                                        <td style="padding: 10px 0; text-align: right; font-weight: 600; font-size: 14px;">{{ $paidTicket->buyer_name }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Jumlah Tiket</td>
                                        <td style="padding: 10px 0; text-align: right; font-weight: 600; font-size: 14px;">{{ $paidTicket->quantity }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Total Bayar</td>
                                        <td style="padding: 10px 0; text-align: right; font-weight: 600; font-size: 14px;">Rp {{ number_format($paidTicket->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; color: #6b7280; font-size: 14px;">Status</td>
                                        <td style="padding: 10px 0; text-align: right;">
                                            <span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600;">{{ $paidTicket->status }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- PURCHASE FORM --}}
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        {{-- Ticket Info Card --}}
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-ticket" style="font-size: 20px; color: #0072FF;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h5 style="margin: 0; font-weight: 600;">Tiket Masuk Event</h5>
                                    <p style="margin: 2px 0 0; color: #6b7280; font-size: 14px;">{{ $eventner->nama_event }}</p>
                                </div>
                                <div style="text-align: right;">
                                    <h4 style="margin: 0; color: #0072FF; font-weight: 700;">Rp {{ number_format($eventner->ticket_price, 0, ',', '.') }}</h4>
                                    <span style="color: #9ca3af; font-size: 13px;">/ tiket</span>
                                </div>
                            </div>
                            @if($eventner->ticket_description)
                                <div style="background: #f8fafc; border-radius: 10px; padding: 14px; font-size: 14px; color: #6b7280;">
                                    {{ $eventner->ticket_description }}
                                </div>
                            @endif
                        </div>

                        {{-- Form Card --}}
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            <div style="padding: 18px 24px; border-bottom: 1px solid #e5e7eb;">
                                <h5 style="margin: 0; font-weight: 600;">
                                    <i class="fa fa-file-alt" style="margin-right: 8px; color: #0072FF;"></i>Formulir Pembelian
                                </h5>
                            </div>
                            <div style="padding: 24px;">
                                {{-- Name --}}
                                <div style="margin-bottom: 18px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                                    <input type="text" wire:model="buyerName" placeholder="Masukkan nama lengkap" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    @error('buyerName') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Email --}}
                                <div style="margin-bottom: 18px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Email <span style="color: #ef4444;">*</span></label>
                                    <input type="email" wire:model="buyerEmail" placeholder="contoh@email.com" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    @error('buyerEmail') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Phone --}}
                                <div style="margin-bottom: 18px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">No. WhatsApp <span style="color: #ef4444;">*</span></label>
                                    <input type="tel" wire:model="buyerPhone" placeholder="08xxxxxxxxxx" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    @error('buyerPhone') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Quantity --}}
                                <div style="margin-bottom: 24px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Jumlah Tiket <span style="color: #ef4444;">*</span></label>
                                    <div style="display: flex; max-width: 200px;">
                                        <button type="button" wire:click="$set('quantity', max(1, $quantity - 1))" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px 0 0 8px; padding: 10px 16px; cursor: pointer; font-size: 16px;">−</button>
                                        <input type="number" wire:model="quantity" style="border: 1px solid #e5e7eb; border-left: none; border-right: none; text-align: center; width: 100%; padding: 10px; font-size: 16px; font-weight: 600; outline: none;" min="1" max="{{ $eventner->ticket_max_per_order ?? 10 }}">
                                        <button type="button" wire:click="$set('quantity', min({{ $eventner->ticket_max_per_order ?? 10 }}, $quantity + 1))" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 0 8px 8px 0; padding: 10px 16px; cursor: pointer; font-size: 16px;">+</button>
                                    </div>
                                    <span style="color: #9ca3af; font-size: 13px;">Maksimal {{ $eventner->ticket_max_per_order ?? 10 }} tiket per transaksi</span>
                                    @error('quantity') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Summary --}}
                                <div style="background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="color: #6b7280; font-size: 14px;">{{ number_format($eventner->ticket_price, 0, ',', '.') }} x {{ $quantity }} tiket</span>
                                        <span style="font-weight: 600; font-size: 14px;">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                    </div>
                                    <div style="border-top: 1px solid #e5e7eb; padding-top: 8px; display: flex; justify-content: space-between;">
                                        <span style="font-weight: 700; font-size: 16px;">Total Bayar</span>
                                        <span style="font-weight: 700; color: #0072FF; font-size: 18px;">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <button wire:click="submitTicket" class="zubuz-default-btn" style="width: 100%;" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="submitTicket">
                                        <i class="fa fa-qrcode"></i> Bayar via QRIS
                                    </span>
                                    <span wire:loading wire:target="submitTicket">
                                        <span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                                        Memproses...
                                    </span>
                                </button>

                                <div style="text-align: center; margin-top: 14px;">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" height="18" alt="QRIS" style="opacity: 0.5;">
                                    <p style="color: #9ca3af; font-size: 11px; margin-top: 4px;">Pembayaran aman via QRIS GoPay</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
