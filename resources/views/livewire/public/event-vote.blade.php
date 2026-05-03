<div>
    {{-- Hero Banner --}}
    <div class="section" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 60px 0 40px;">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <span style="display:inline-block; background: rgba(0,0,0,0.2); color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">
                        <i class="fa fa-heart"></i> Laman Voting Digital
                    </span>
                    <h1 class="wow fadeInUp" style="color: #fff; font-size: 36px;">Dukung Tim Jagoan Anda!</h1>
                    <p class="wow fadeInUp" style="color: rgba(255,255,255,0.9); font-size: 17px; margin-top: 8px;">
                        Setiap vote sangat berarti untuk menentukan juara favorit di <strong>{{ $eventner->nama_event }}</strong>.
                    </p>
                    <div class="wow fadeInUp mt-3 d-flex justify-content-center gap-2">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="zubuz-default-btn" style="background: rgba(255,255,255,0.25);">
                            <span><i class="fa fa-info-circle"></i> Info Event</span>
                        </a>
                        <a href="{{ route('event.participant', $eventner->slug) }}" class="zubuz-default-btn" style="background: rgba(255,255,255,0.15);">
                            <span><i class="fa fa-users"></i> Daftar Peserta</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section zubuz-section-padding3">
        <div class="container">
            @if (session()->has('error'))
                <div class="wow fadeInUp" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="row">
                {{-- Left: Team Selection --}}
                <div class="col-lg-8">
                    @if($view == 'categories')
                        {{-- View A: Categories --}}
                        <div class="zubuz-section-title wow fadeInUp">
                            <h2>Pilih Kategori Lomba</h2>
                        </div>
                        <div class="row">
                            @foreach($categories as $cat)
                            <div class="col-md-6 mb-4 wow fadeInUp">
                                <div wire:click="selectCategory({{ $cat->id }})"
                                     style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s;"
                                     onmouseover="this.style.borderColor='#0072FF'; this.style.boxShadow='0 4px 20px rgba(0,114,255,0.15)';"
                                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                                    <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                        <i class="fa fa-trophy" style="font-size: 24px; color: #0072FF;"></i>
                                    </div>
                                    <h5 style="font-weight: 600; margin-bottom: 6px;">{{ $cat->name }}</h5>
                                    <p style="color: #6b7280; margin-bottom: 16px;">{{ $cat->registrations_count }} Kontingen Terdaftar</p>
                                    <span class="zubuz-default-btn" style="padding: 8px 20px; font-size: 13px;">
                                        <span>Pilih Kategori</span>
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        {{-- View B: Participants --}}
                        <div class="wow fadeInUp mb-3">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button wire:click="backToCategories" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; cursor: pointer;">
                                    <i class="fa fa-arrow-left" style="color: #6b7280;"></i>
                                </button>
                                <nav>
                                    <span style="color: #6b7280; font-size: 14px;">Kategori</span>
                                    <span style="color: #9ca3af; margin: 0 6px;">/</span>
                                    <strong style="color: #0072FF;">{{ $selectedCategory->name }}</strong>
                                </nav>
                            </div>
                        </div>

                        {{-- Search --}}
                        <div class="wow fadeInUp mb-4" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 4px; overflow: hidden;">
                            <div style="display: flex; align-items: center;">
                                <span style="padding: 10px 14px; color: #9ca3af;"><i class="fa fa-search"></i></span>
                                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama sekolah atau kontingen..." style="border: none; outline: none; flex: 1; padding: 10px 0; font-size: 15px;">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 style="font-weight: 600; margin: 0;">Pilih Peserta</h4>
                            <span style="background: rgba(0,114,255,0.1); color: #0072FF; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">{{ $participants->count() }} Ditemukan</span>
                        </div>

                        <div class="row">
                            @forelse($participants as $reg)
                            <div class="col-md-6 mb-3 wow fadeInUp">
                                <div wire:click="selectTeam({{ $reg->id }})"
                                     style="background: #fff; border: {{ $selectedRegistrationId == $reg->id ? '2px solid #0072FF' : '1px solid #e5e7eb' }}; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.3s; {{ $selectedRegistrationId == $reg->id ? 'box-shadow: 0 4px 20px rgba(0,114,255,0.15);' : '' }}">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="position: relative;">
                                            @if($reg->logo_sekolah)
                                                <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 1px solid #e5e7eb;" alt="">
                                            @else
                                                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center; color: #0072FF;">
                                                    <i class="fa fa-school" style="font-size: 18px;"></i>
                                                </div>
                                            @endif
                                            @if($selectedRegistrationId == $reg->id)
                                            <span style="position: absolute; top: -4px; right: -4px; width: 18px; height: 18px; background: #0072FF; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa fa-check" style="color: #fff; font-size: 10px;"></i>
                                            </span>
                                            @endif
                                        </div>
                                        <div style="flex: 1; overflow: hidden;">
                                            <h6 style="margin: 0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $reg->nama_sekolah }}</h6>
                                            <p style="margin: 2px 0 0; color: #6b7280; font-size: 13px;">Pelatih: {{ $reg->nama_pelatih }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 text-center" style="padding: 60px 0;">
                                <p style="color: #9ca3af;">Tidak ada kontingen yang sesuai.</p>
                                <button wire:click="$set('search', '')" style="background: none; border: none; color: #0072FF; font-weight: 600; cursor: pointer;">Hapus Pencarian</button>
                            </div>
                            @endforelse
                        </div>
                    @endif
                </div>

                {{-- Right: Vote Form --}}
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="wow fadeInRight" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; position: sticky; top: 100px;">
                        {{-- Form Header --}}
                        <div style="background: linear-gradient(135deg, #0072FF, #0046b3); padding: 18px 24px;">
                            <h5 style="margin: 0; color: #fff; font-weight: 600;">
                                <i class="fa fa-heart" style="margin-right: 8px;"></i>Form Voting
                            </h5>
                        </div>

                        {{-- Form Body --}}
                        <div style="padding: 24px;">
                            <form wire:submit.prevent="submitVote">
                                {{-- Vote Count --}}
                                <div style="margin-bottom: 20px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 8px; font-size: 14px;">Jumlah Vote</label>
                                    <div style="display: flex; gap: 0;">
                                        <button type="button" wire:click="$set('voteCount', {{ max(1, $voteCount - 1) }})" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px 0 0 8px; padding: 10px 16px; cursor: pointer; font-size: 18px; color: #0072FF;">−</button>
                                        <input type="number" wire:model.live="voteCount" style="border: 1px solid #e5e7eb; border-left: none; border-right: none; text-align: center; font-weight: 600; font-size: 16px; width: 100%; padding: 10px; outline: none;">
                                        <button type="button" wire:click="$set('voteCount', {{ $voteCount + 1 }})" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 0 8px 8px 0; padding: 10px 16px; cursor: pointer; font-size: 18px; color: #0072FF;">+</button>
                                    </div>
                                    <div style="display: flex; gap: 6px; margin-top: 8px;">
                                        <button type="button" wire:click="$set('voteCount', 10)" style="flex: 1; background: rgba(0,114,255,0.08); border: 1px solid rgba(0,114,255,0.2); border-radius: 6px; padding: 4px; cursor: pointer; color: #0072FF; font-weight: 600; font-size: 13px;">10</button>
                                        <button type="button" wire:click="$set('voteCount', 50)" style="flex: 1; background: rgba(0,114,255,0.08); border: 1px solid rgba(0,114,255,0.2); border-radius: 6px; padding: 4px; cursor: pointer; color: #0072FF; font-weight: 600; font-size: 13px;">50</button>
                                        <button type="button" wire:click="$set('voteCount', 100)" style="flex: 1; background: rgba(0,114,255,0.08); border: 1px solid rgba(0,114,255,0.2); border-radius: 6px; padding: 4px; cursor: pointer; color: #0072FF; font-weight: 600; font-size: 13px;">100</button>
                                        <button type="button" wire:click="$set('voteCount', 500)" style="flex: 1; background: rgba(0,114,255,0.08); border: 1px solid rgba(0,114,255,0.2); border-radius: 6px; padding: 4px; cursor: pointer; color: #0072FF; font-weight: 600; font-size: 13px;">500</button>
                                    </div>
                                    @error('voteCount') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Name --}}
                                <div style="margin-bottom: 16px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Nama Lengkap</label>
                                    <input type="text" wire:model="voterName" placeholder="Contoh: Budi Santoso" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    @error('voterName') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Email --}}
                                <div style="margin-bottom: 20px;">
                                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Email (Untuk Bukti)</label>
                                    <input type="email" wire:model="voterEmail" placeholder="email@contoh.com" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    @error('voterEmail') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                </div>

                                {{-- Summary --}}
                                <div style="background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="color: #6b7280; font-size: 14px;">Harga per Vote</span>
                                        <span style="font-weight: 600; font-size: 14px;">Rp 1.000</span>
                                    </div>
                                    <div style="border-top: 1px solid #e5e7eb; padding-top: 8px; display: flex; justify-content: space-between;">
                                        <span style="font-weight: 700; font-size: 16px;">Total Bayar</span>
                                        <span style="font-weight: 700; color: #0072FF; font-size: 18px;">Rp {{ number_format($voteCount * 1000, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                {{-- Submit --}}
                                <button type="submit"
                                    class="zubuz-default-btn"
                                    style="width: 100%; {{ !$selectedRegistrationId ? 'opacity: 0.5; cursor: not-allowed;' : '' }}"
                                    {{ !$selectedRegistrationId ? 'disabled' : '' }}
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove>Lanjutkan Ke Pembayaran</span>
                                    <span wire:loading>
                                        <span style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                                        Memproses...
                                    </span>
                                </button>

                                @if(!$selectedRegistrationId)
                                <p style="text-align: center; color: #ef4444; font-size: 13px; margin-top: 8px;">Silakan pilih kontingen terlebih dahulu</p>
                                @endif

                                <div style="text-align: center; margin-top: 16px;">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg" height="22" alt="QRIS" style="opacity: 0.7;">
                                    <p style="color: #9ca3af; font-size: 12px; margin-top: 6px;">Pembayaran aman via Xendit QRIS</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
