<div>
    {{-- Hero Banner --}}
    <div style="background: linear-gradient(135deg, var(--event-primary, #0072FF) 0%, var(--event-accent, #00D4AA) 100%); padding: 140px 0 40px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
        <div style="position: absolute; bottom: -30%; left: -10%; width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div class="text-center">
                <span style="display:inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">
                    <i class="fa fa-heart"></i> Voting Digital
                </span>
                <h1 class="wow fadeInUp" style="color: #fff; font-size: clamp(24px, 5vw, 36px); margin-bottom: 8px;">Dukung Tim Jagoan Anda!</h1>
                <p class="wow fadeInUp" style="color: rgba(255,255,255,0.9); font-size: 15px; max-width: 500px; margin: 0 auto;">
                    Setiap vote sangat berarti untuk menentukan juara favorit di <strong>{{ $eventner->nama_event }}</strong>.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section zubuz-section-padding3" style="padding-top: 30px;">
        <div class="container">
            @if (session()->has('error'))
                <div class="wow fadeInUp" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($view === 'payment')
                {{-- PAYMENT VIEW: QR Code --}}
                <div class="row justify-content-center" wire:poll.5s="checkPaymentStatus">
                    <div class="col-lg-5">
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            {{-- Header --}}
                            <div style="background: linear-gradient(135deg, var(--event-primary, #0072FF), var(--event-accent, #00D4AA)); padding: 20px; text-align: center;">
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
                                <div style="background: rgba(0,114,255,0.06); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                                    <p style="color: #6b7280; font-size: 13px; margin-bottom: 4px;">Total Pembayaran</p>
                                    <h3 style="color: var(--event-primary, #0072FF); font-weight: 800; margin: 0;">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</h3>
                                    <p style="color: #6b7280; font-size: 12px; margin-top: 4px;">{{ $voteCount }} vote × Rp 1.000</p>
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
                                        <i class="fa fa-info-circle" style="color: var(--event-primary, #0072FF);"></i> Cara Bayar:
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

            @elseif($view === 'success')
                {{-- SUCCESS VIEW --}}
                <div class="row justify-content-center">
                    <div class="col-lg-5">
                        <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; text-align: center;">
                            <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 30px;">
                                <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                    <i class="fa fa-check" style="font-size: 28px; color: #fff;"></i>
                                </div>
                                <h3 style="color: #fff; font-weight: 700; margin-bottom: 4px;">Pembayaran Berhasil!</h3>
                                <p style="color: rgba(255,255,255,0.85); margin: 0; font-size: 15px;">Vote Anda telah dihitung</p>
                            </div>
                            <div style="padding: 24px;">
                                <div style="background: rgba(16,185,129,0.08); border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                                    <p style="color: #065f46; font-size: 14px; margin: 0;">
                                        <strong>{{ $voteCount }} vote</strong> untuk tim pilihan Anda telah berhasil ditambahkan.
                                    </p>
                                </div>
                                <a href="{{ route('event.detail', $eventner->slug) }}" class="zubuz-default-btn" style="width: 100%;">
                                    <span><i class="fa fa-arrow-left"></i> Kembali ke Event</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- NORMAL VOTE VIEW (categories/participants) --}}
                {{-- Quick Nav on mobile --}}
                <div class="d-lg-none mb-3 wow fadeInUp">
                    <div class="d-flex gap-2 overflow-auto pb-2" style="scrollbar-width: none; -webkit-overflow-scrolling: touch;">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="zubuz-default-btn" style="min-width: fit-content; padding: 8px 16px; font-size: 13px; background: #f1f5f9; color: #475569; white-space: nowrap;">
                            <span><i class="fa fa-info-circle"></i> Info</span>
                        </a>
                        <a href="{{ route('event.participant', $eventner->slug) }}" class="zubuz-default-btn" style="min-width: fit-content; padding: 8px 16px; font-size: 13px; background: #f1f5f9; color: #475569; white-space: nowrap;">
                            <span><i class="fa fa-users"></i> Peserta</span>
                        </a>
                        <a href="{{ route('event.vote', $eventner->slug) }}" class="zubuz-default-btn" style="min-width: fit-content; padding: 8px 16px; font-size: 13px; background: var(--event-primary, #0072FF); white-space: nowrap;">
                            <span><i class="fa fa-heart"></i> Vote</span>
                        </a>
                    </div>
                </div>

                <div class="row">
                    {{-- Left: Team Selection --}}
                    <div class="col-lg-8">
                        @if($view == 'categories')
                            {{-- View A: Categories --}}
                            <div class="zubuz-section-title wow fadeInUp">
                                <h2 style="font-size: 24px;">Pilih Kategori Lomba</h2>
                            </div>
                            <div class="row g-3">
                                @foreach($categories as $cat)
                                <div class="col-6 col-md-6 wow fadeInUp">
                                    <div wire:click="selectCategory({{ $cat->id }})"
                                         style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px 16px; text-align: center; cursor: pointer; transition: all 0.3s;"
                                         onmouseover="this.style.borderColor='var(--event-primary, #0072FF)'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.06)';"
                                         onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                                        <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                            <i class="fa fa-trophy" style="font-size: 20px; color: var(--event-primary, #0072FF);"></i>
                                        </div>
                                        <h6 style="font-weight: 600; margin-bottom: 4px; font-size: 15px; line-height: 1.3;">{{ $cat->name }}</h6>
                                        <p style="color: #6b7280; margin-bottom: 12px; font-size: 13px;">{{ $cat->registrations_count }} Kontingen</p>
                                        <span style="display: inline-block; background: var(--event-primary, #0072FF); color: #fff; padding: 6px 16px; border-radius: 8px; font-size: 12px; font-weight: 600;">
                                            Pilih <i class="fa fa-arrow-right" style="font-size: 10px; margin-left: 4px;"></i>
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            {{-- View B: Participants --}}
                            <div class="wow fadeInUp mb-3">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <button wire:click="backToCategories" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; width: 40px; height: 40px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fa fa-arrow-left" style="color: #6b7280;"></i>
                                    </button>
                                    <nav style="font-size: 14px;">
                                        <span style="color: #6b7280;">Kategori</span>
                                        <span style="color: #9ca3af; margin: 0 6px;">/</span>
                                        <strong style="color: var(--event-primary, #0072FF);">{{ $selectedCategory->name }}</strong>
                                    </nav>
                                </div>
                            </div>

                            {{-- Search --}}
                            <div class="wow fadeInUp mb-3">
                                <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 2px 4px; display: flex; align-items: center;">
                                    <span style="padding: 10px 12px; color: #9ca3af;"><i class="fa fa-search"></i></span>
                                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama sekolah..." style="border: none; outline: none; flex: 1; padding: 10px 0; font-size: 15px; min-width: 0;">
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 style="font-weight: 600; margin: 0; font-size: 18px;">Pilih Peserta</h5>
                                <span style="background: rgba(0,114,255,0.1); color: var(--event-primary, #0072FF); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">{{ $participants->count() }}</span>
                            </div>

                            <div class="row g-2">
                                @forelse($participants as $reg)
                                <div class="col-12 col-md-6 wow fadeInUp">
                                    <div wire:click="selectTeam({{ $reg->id }})"
                                         style="background: #fff; border: {{ $selectedRegistrationId == $reg->id ? '2px solid var(--event-primary, #0072FF)' : '1px solid #e5e7eb' }}; border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.3s; {{ $selectedRegistrationId == $reg->id ? 'box-shadow: 0 4px 16px rgba(0,114,255,0.12);' : '' }}">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="position: relative; flex-shrink: 0;">
                                                @if($reg->logo_sekolah)
                                                    <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid #e5e7eb;" alt="">
                                                @else
                                                    <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center; color: var(--event-primary, #0072FF);">
                                                        <i class="fa fa-school" style="font-size: 16px;"></i>
                                                    </div>
                                                @endif
                                                @if($selectedRegistrationId == $reg->id)
                                                <span style="position: absolute; top: -3px; right: -3px; width: 18px; height: 18px; background: var(--event-primary, #0072FF); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff;">
                                                    <i class="fa fa-check" style="color: #fff; font-size: 8px;"></i>
                                                </span>
                                                @endif
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <h6 style="margin: 0; font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $reg->nama_sekolah }}</h6>
                                                <p style="margin: 2px 0 0; color: #6b7280; font-size: 12px;">Pelatih: {{ $reg->nama_pelatih }}</p>
                                            </div>
                                            <div style="text-align: right; flex-shrink: 0;">
                                                <span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 4px 8px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;" title="Total Vote Terkumpul">
                                                    <i class="fa fa-heart"></i> {{ number_format($reg->total_votes ?? 0, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="col-12 text-center" style="padding: 40px 0;">
                                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                                        <i class="fa fa-search" style="color: #9ca3af; font-size: 20px;"></i>
                                    </div>
                                    <p style="color: #9ca3af; margin-bottom: 8px;">Tidak ada kontingen ditemukan.</p>
                                    <button wire:click="$set('search', '')" style="background: none; border: none; color: var(--event-primary, #0072FF); font-weight: 600; cursor: pointer; font-size: 14px;">Hapus Pencarian</button>
                                </div>
                                @endforelse
                            </div>
                        @endif
                    </div>

                    {{-- Right: Vote Form --}}
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        {{-- Desktop: sticky sidebar --}}
                        <div class="d-none d-lg-block">
                            <div class="wow fadeInRight" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; position: sticky; top: 100px;">
                                @include('livewire.public.partials._vote-form')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($view !== 'payment' && $view !== 'success')
    {{-- Mobile: Sticky bottom vote form --}}
    <div class="d-lg-none" id="mobile-vote-bar">
        @if($selectedRegistrationId)
        <div style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000; background: #fff; border-top: 1px solid #e5e7eb; padding: 12px 16px; padding-bottom: calc(12px + env(safe-area-inset-bottom)); box-shadow: 0 -4px 20px rgba(0,0,0,0.08);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="flex: 1; min-width: 0;">
                    <p style="margin: 0; font-size: 12px; color: #6b7280;">Vote untuk</p>
                    <p style="margin: 0; font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ App\Models\Registration::find($selectedRegistrationId)?->nama_sekolah }}</p>
                </div>
                <button type="button" data-bs-toggle="modal" data-bs-target="#mobileVoteModal" style="background: var(--event-primary, #0072FF); color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; font-size: 14px; white-space: nowrap; cursor: pointer;">
                    Vote {{ $voteCount }}x
                </button>
            </div>
        </div>
        @else
        <div style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000; background: #fff; border-top: 1px solid #e5e7eb; padding: 12px 16px; padding-bottom: calc(12px + env(safe-area-inset-bottom)); box-shadow: 0 -4px 20px rgba(0,0,0,0.08);">
            <div style="text-align: center;">
                <p style="margin: 0; color: #9ca3af; font-size: 13px;"><i class="fa fa-hand-pointer"></i> Pilih kontingen terlebih dahulu untuk mulai voting</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Mobile Vote Modal --}}
    <div class="modal fade" id="mobileVoteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content" style="border: none; border-radius: 20px 20px 0 0;">
                <div class="modal-header" style="border-bottom: 1px solid #e5e7eb; padding: 16px 20px;">
                    <h5 class="modal-title" style="font-weight: 600; font-size: 18px;">
                        <i class="fa fa-heart" style="color: var(--event-primary, #0072FF);"></i> Form Voting
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    @include('livewire.public.partials._vote-form')
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    /* Add padding at bottom for mobile sticky bar */
    @media (max-width: 991px) {
        body { padding-bottom: 80px !important; }
    }
</style>
