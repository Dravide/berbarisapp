<div>
    {{-- Hero Banner --}}
    <div class="section" style="background: linear-gradient(135deg, #111827 0%, #1e293b 100%); padding: 60px 0 40px;">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    @if($eventner->logo_event)
                        <div class="wow zoomIn" style="width: 70px; height: 70px; border-radius: 50%; overflow: hidden; margin: 0 auto 16px; border: 3px solid rgba(255,255,255,0.2);">
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    @endif
                    <span style="display:inline-block; background: rgba(0,114,255,0.3); color: #93c5fd; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">Pendaftaran Peserta</span>
                    <h1 class="wow fadeInUp" style="color: #fff; font-size: 36px;">Booking Slot Pasukan</h1>
                    <p class="wow fadeInUp" style="color: rgba(255,255,255,0.7); font-size: 17px; margin-top: 8px;">
                        {{ $eventner->nama_event }} — <em>{{ $eventner->diselenggarakan_oleh }}</em>
                    </p>
                    <div class="wow fadeInUp mt-3 d-flex justify-content-center gap-2">
                        <a href="{{ route('event.detail', $eventner->slug) }}" class="zubuz-default-btn" style="background: rgba(255,255,255,0.1);">
                            <span><i class="fa fa-arrow-left"></i> Kembali</span>
                        </a>
                        <a href="{{ route('event.participant', $eventner->slug) }}" class="zubuz-default-btn" style="background: rgba(255,255,255,0.06);">
                            <span><i class="fa fa-users"></i> Daftar Peserta</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('error'))
    <div class="container" style="margin-top: 20px;">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 14px 20px; border-radius: 12px;">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Progress Stepper --}}
    <div style="padding: 30px 0 10px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 24px;">
                        @for($i = 1; $i <= 3; $i++)
                            <div style="display: flex; align-items: center;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; {{ $step >= $i ? 'background: #0072FF; color: #fff;' : 'background: #f3f4f6; color: #9ca3af;' }}">
                                    @if($step > $i)
                                        <i class="fa fa-check"></i>
                                    @else
                                        {{ $i }}
                                    @endif
                                </div>
                                <span style="margin-left: 8px; font-weight: 600; font-size: 14px; {{ $step >= $i ? 'color: #111827;' : 'color: #9ca3af;' }}" class="d-none d-sm-inline">
                                    @if($i == 1) Pilih Kategori
                                    @elseif($i == 2) Data Sekolah
                                    @else Konfirmasi
                                    @endif
                                </span>
                            </div>
                            @if($i < 3)
                                <div style="width: 40px; height: 2px; background: {{ $step > $i ? '#0072FF' : '#e5e7eb' }}; margin: 0 4px;"></div>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section" style="padding: 10px 0 50px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    @if(($eventner->registration_status ?? 'open') == 'closed')
                        <div class="text-center wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 60px 40px; margin-top: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                            <div style="width: 100px; height: 100px; background: rgba(239,68,68,0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                                <i class="fa fa-lock" style="font-size: 40px;"></i>
                            </div>
                            <h2 style="font-weight: 700; color: #111827; margin-bottom: 12px;">Pendaftaran Ditutup</h2>
                            <p style="color: #6b7280; font-size: 16px; margin-bottom: 30px; line-height: 1.6;">
                                Mohon maaf, pendaftaran untuk event <strong>{{ $eventner->nama_event }}</strong> saat ini telah ditutup secara resmi oleh panitia.
                                <br>Pastikan Anda mengikuti informasi terbaru melalui kanal resmi kami.
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="{{ route('event.detail', $eventner->slug) }}" class="zubuz-default-btn">
                                    <span>Kembali ke Beranda Event</span>
                                </a>
                                <a href="{{ route('event.participant', $eventner->slug) }}" class="zubuz-default-btn" style="background: #f8fafc; color: #111827; border: 1px solid #e5e7eb;">
                                    <span>Daftar Peserta</span>
                                </a>
                            </div>
                            
                            @if($eventner->link_whatsapp)
                            <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
                                <p style="font-size: 14px; color: #9ca3af; margin-bottom: 12px;">Ada pertanyaan terkait pendaftaran?</p>
                                <a href="{{ $eventner->link_whatsapp }}" target="_blank" style="color: #10b981; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fa fa-whatsapp" style="font-size: 18px;"></i> Hubungi Panitia (WhatsApp)
                                </a>
                            </div>
                            @endif
                        </div>
                    @else

                    {{-- STEP 1: Pilih Kategori --}}
                    @if($step === 1)
                        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #0072FF, #0046b3); padding: 18px 24px;">
                                <h5 style="margin: 0; color: #fff; font-weight: 600;"><i class="fa fa-list" style="margin-right: 8px;"></i>Pilih Kategori Lomba</h5>
                            </div>
                            <div style="padding: 24px;">
                                @error('selectedCategories')
                                    <div style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 10px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px;">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <p style="color: #6b7280; margin-bottom: 20px;">Pilih kategori lomba dan jumlah pasukan yang ingin didaftarkan.</p>

                                @foreach($categories as $cat)
                                    @php
                                        $isFull = $cat->kuota && $cat->registrations_count >= $cat->kuota;
                                        $maxPerSchool = $cat->max_registrations_per_school ?? 1;
                                        $selected = in_array($cat->id, $selectedCategories);
                                    @endphp
                                    <div style="background: {{ $selected ? 'rgba(0,114,255,0.04)' : '#fff' }}; border: {{ $selected ? '2px solid #0072FF' : '1px solid #e5e7eb' }}; border-radius: 12px; padding: 16px; margin-bottom: 12px; {{ $isFull ? 'opacity: 0.5;' : '' }}">
                                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                                            <input type="checkbox" wire:model="selectedCategories" value="{{ $cat->id }}" id="cat_{{ $cat->id }}" {{ $isFull ? 'disabled' : '' }} style="margin-top: 4px; width: 18px; height: 18px; accent-color: #0072FF;">

                                            <div style="flex: 1;">
                                                <label for="cat_{{ $cat->id }}" style="font-weight: 600; cursor: pointer; font-size: 15px;">{{ $cat->name }}</label>
                                                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px;">
                                                    <span style="background: rgba(0,114,255,0.1); color: #0072FF; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                        <i class="fa fa-users"></i> {{ $cat->registrations_count }} / {{ $cat->kuota ?? '∞' }}
                                                    </span>
                                                    <span style="background: rgba(59,130,246,0.1); color: #3b82f6; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                        Max {{ $maxPerSchool }}/sekolah
                                                    </span>
                                                    @if($isFull)
                                                    <span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Penuh</span>
                                                    @endif
                                                </div>

                                                @if($selected && $maxPerSchool > 1)
                                                <div style="margin-top: 12px; display: flex; align-items: center; gap: 6px;">
                                                    <span style="color: #6b7280; font-size: 13px;">Jumlah pasukan:</span>
                                                    @for($i = 1; $i <= $maxPerSchool; $i++)
                                                        <button type="button" wire:click="$set('teamCounts.{{ $cat->id }}', {{ $i }})" style="padding: 4px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; {{ ($teamCounts[$cat->id] ?? 1) == $i ? 'background: #0072FF; color: #fff; border: 1px solid #0072FF;' : 'background: #fff; color: #0072FF; border: 1px solid rgba(0,114,255,0.3);' }}">
                                                            {{ $i }}
                                                        </button>
                                                    @endfor
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($categories->isEmpty())
                                    <div style="text-align: center; padding: 30px; color: #9ca3af;">
                                        <p>Belum ada kategori lomba yang tersedia.</p>
                                    </div>
                                @endif

                                <div style="text-align: right; margin-top: 20px;">
                                    <button wire:click="nextStep" class="zubuz-default-btn">
                                        <span>Lanjut <i class="fa fa-arrow-right"></i></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- STEP 2: Data Sekolah --}}
                    @if($step === 2)
                        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #0072FF, #0046b3); padding: 18px 24px;">
                                <h5 style="margin: 0; color: #fff; font-weight: 600;"><i class="fa fa-school" style="margin-right: 8px;"></i>Data Sekolah</h5>
                            </div>
                            <div style="padding: 24px;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">NPSN <span style="color: #ef4444;">*</span></label>
                                        <input type="text" wire:model="npsn" placeholder="Nomor Pokok Sekolah Nasional" maxlength="20" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                        @error('npsn') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Nama Sekolah <span style="color: #ef4444;">*</span></label>
                                        <input type="text" wire:model="nama_sekolah" placeholder="Nama sekolah sesuai data resmi" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                        @error('nama_sekolah') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">No HP / WhatsApp <span style="color: #ef4444;">*</span></label>
                                        <input type="text" wire:model="no_hp" placeholder="08xxxxxxxxxx" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                        @error('no_hp') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Email Sekolah</label>
                                        <input type="email" wire:model="school_email" placeholder="opsional" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    </div>
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Nama Pelatih</label>
                                        <input type="text" wire:model="nama_pelatih" placeholder="Nama pelatih / pembina" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    </div>
                                    <div class="col-12"><hr style="border-color: #f3f4f6; margin: 8px 0;"></div>
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Password <span style="color: #ef4444;">*</span></label>
                                        <input type="password" wire:model="password" placeholder="Minimal 6 karakter" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                        @error('password') <span style="color: #ef4444; font-size: 13px;">{{ $message }}</span> @enderror
                                        <span style="color: #9ca3af; font-size: 12px;">Digunakan untuk mengelola pendaftaran Anda nanti.</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Konfirmasi Password <span style="color: #ef4444;">*</span></label>
                                        <input type="password" wire:model="password_confirmation" placeholder="Ulangi password" style="width: 100%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; outline: none;">
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; margin-top: 24px;">
                                    <button wire:click="prevStep" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 24px; cursor: pointer; font-weight: 600; color: #6b7280;">
                                        <i class="fa fa-arrow-left"></i> Kembali
                                    </button>
                                    <button wire:click="nextStep" class="zubuz-default-btn">
                                        <span>Lanjut <i class="fa fa-arrow-right"></i></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- STEP 3: Review & Submit --}}
                    @if($step === 3)
                        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #0072FF, #0046b3); padding: 18px 24px;">
                                <h5 style="margin: 0; color: #fff; font-weight: 600;"><i class="fa fa-clipboard-check" style="margin-right: 8px;"></i>Konfirmasi Booking</h5>
                            </div>
                            <div style="padding: 24px;">
                                {{-- School Info --}}
                                <h6 style="font-weight: 600; margin-bottom: 12px;"><i class="fa fa-school" style="color: #0072FF; margin-right: 6px;"></i>Data Sekolah</h6>
                                <div style="background: #f8fafc; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <span style="color: #9ca3af; font-size: 12px; display: block;">NPSN</span>
                                            <p style="font-weight: 600; margin: 0;">{{ $npsn }}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <span style="color: #9ca3af; font-size: 12px; display: block;">Nama Sekolah</span>
                                            <p style="font-weight: 600; margin: 0;">{{ $nama_sekolah }}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <span style="color: #9ca3af; font-size: 12px; display: block;">No HP</span>
                                            <p style="font-weight: 600; margin: 0;">{{ $no_hp }}</p>
                                        </div>
                                        @if($school_email)
                                        <div class="col-sm-6">
                                            <span style="color: #9ca3af; font-size: 12px; display: block;">Email</span>
                                            <p style="font-weight: 600; margin: 0;">{{ $school_email }}</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Categories --}}
                                <h6 style="font-weight: 600; margin-bottom: 12px;"><i class="fa fa-trophy" style="color: #0072FF; margin-right: 6px;"></i>Kategori & Pasukan</h6>
                                @foreach($categories->whereIn('id', $selectedCategories) as $cat)
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border-radius: 10px; padding: 12px 16px; margin-bottom: 8px;">
                                    <span style="font-weight: 600;">{{ $cat->name }}</span>
                                    <span style="background: rgba(0,114,255,0.1); color: #0072FF; padding: 4px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                        {{ $teamCounts[$cat->id] ?? 1 }} pasukan
                                    </span>
                                </div>
                                @endforeach

                                {{-- Info --}}
                                <div style="background: rgba(245,158,11,0.1); border-radius: 12px; padding: 16px; margin-top: 20px; margin-bottom: 20px;">
                                    <div style="display: flex; gap: 10px;">
                                        <i class="fa fa-info-circle" style="color: #f59e0b; margin-top: 2px;"></i>
                                        <div>
                                            <p style="font-weight: 600; margin: 0 0 4px; font-size: 14px;">Status: Booking</p>
                                            <p style="color: #6b7280; margin: 0; font-size: 13px;">
                                                Pendaftaran Anda akan berstatus <strong>Booking</strong>. Setelah Technical Meeting, Anda bisa mengkonfirmasi dan melengkapi data pasukan melalui link yang akan dikirim.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between;">
                                    <button wire:click="prevStep" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 24px; cursor: pointer; font-weight: 600; color: #6b7280;">
                                        <i class="fa fa-arrow-left"></i> Kembali
                                    </button>
                                    <button wire:click="submit" class="zubuz-default-btn" style="background: #10b981;" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="submit">
                                            <i class="fa fa-check"></i> Booking Sekarang
                                        </span>
                                        <span wire:loading wire:target="submit">
                                            <span style="display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50; animation: spin 0.6s linear infinite;"></span>
                                            Memproses...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                    @endif

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
