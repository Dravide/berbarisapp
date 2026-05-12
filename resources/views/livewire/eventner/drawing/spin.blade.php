<div>
    @if($isAuthenticated)
    {{-- Hero Banner --}}
    <div style="background: linear-gradient(135deg, var(--event-primary, #0072FF) 0%, var(--event-accent, #00D4AA) 100%); padding: 140px 0 40px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
        <div style="position: absolute; bottom: -30%; left: -10%; width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div class="row align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-4 mb-lg-0">
                    <span style="display:inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">
                        <i class="fa fa-spinner"></i> Live Drawing
                    </span>
                    <h1 class="wow fadeInUp" style="color: #fff; font-size: clamp(24px, 5vw, 36px); margin-bottom: 8px;">Pengundian Urutan Tampil</h1>
                    <p class="wow fadeInUp" style="color: rgba(255,255,255,0.9); font-size: 15px; margin-bottom: 0;">Event: <strong>{{ $eventner->nama_event }}</strong></p>
                    <div class="mt-4 wow fadeInUp">
                        <a href="{{ route('event.detail', $slug) }}" style="background: transparent; border: 1px solid rgba(255,255,255,0.5); color: #fff; padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; text-decoration: none; margin-right: 8px; display: inline-block;">
                            <i class="fa fa-arrow-left me-1"></i> Kembali
                        </a>
                        <a href="{{ route('event.drawing.results', $slug) }}" style="background: #fff; border: none; color: var(--event-primary, #0072FF); padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block;" target="_blank">
                            <i class="fa fa-table me-1"></i> Lihat Hasil
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section zubuz-section-padding3" style="padding-top: 40px;">
        <div class="container">
            @if(session()->has('success'))
                <div class="wow fadeInUp" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <i class="fa fa-check-circle" style="margin-right: 8px;"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 12px;"></button>
                </div>
            @endif

            <style>
                @keyframes spin-ring {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                .spinning-ring {
                    animation: spin-ring 0.2s linear infinite;
                    border-style: dashed !important;
                    border-width: 8px !important;
                    border-color: #f59e0b !important;
                }
            </style>

            <!-- Tabs Kategori -->
            <div class="wow fadeInUp" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 24px;">
                @foreach ($categories as $category)
                    <button wire:click="switchTab('{{ $category['id'] }}')"
                        style="white-space: nowrap; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s; border: none; outline: none; {{ $activeTab == $category['id'] ? 'background: var(--event-primary, #0072FF); color: #fff;' : 'background: #f3f4f6; color: #4b5563;' }}">
                        <i class="fa fa-medal" style="margin-right: 6px;"></i> {{ $category['name'] }}
                    </button>
                @endforeach
            </div>

            <div class="row g-4">
                <!-- Kolom Kiri: Spinning Area -->
                <div class="col-lg-7">
                    <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; min-height: 480px;">
                        <div style="background: rgba(0,114,255,0.05); padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--event-primary, #0072FF);"><i class="fa fa-random" style="margin-right: 8px;"></i> Zona Pengundian</h5>
                            <span style="background: #fff; color: var(--event-primary, #0072FF); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid rgba(0,114,255,0.2);">
                                {{ $drawnSchools->count() }} / {{ $totalSchools }} Selesai
                            </span>
                        </div>

                        <div style="padding: 40px 24px; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            @if($allDrawn)
                                <div style="padding: 20px 0;">
                                    <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(16,185,129,0.1); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                                        <i class="fa fa-check-circle" style="font-size: 48px; color: #10b981;"></i>
                                    </div>
                                    <h3 style="font-weight: 700; color: #10b981; margin-bottom: 12px;">Pengundian Selesai!</h3>
                                    <p style="color: #6b7280; font-size: 15px; margin-bottom: 24px;">Semua sekolah di kategori ini telah mendapatkan nomor urut tampil.</p>
                                    <a href="{{ route('event.drawing.results', $slug) }}" class="zubuz-default-btn" style="text-decoration: none;">
                                        <span><i class="fa fa-table" style="margin-right: 8px;"></i> Lihat Hasil Lengkap</span>
                                    </a>
                                </div>
                            @elseif($currentSchool)
                                <div style="margin-bottom: 24px;">
                                    <span style="background: rgba(0,114,255,0.1); color: var(--event-primary, #0072FF); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">Giliran Mengundi</span>
                                </div>

                                @if($currentSchool->logo_sekolah)
                                    <img src="{{ asset('storage/' . $currentSchool->logo_sekolah) }}" style="width: 80px; height: 80px; object-fit: contain; border-radius: 12px; border: 1px solid #e5e7eb; padding: 4px; margin-bottom: 16px;" alt="Logo">
                                @else
                                    <div style="width: 80px; height: 80px; border-radius: 12px; background: #f3f4f6; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                                        <i class="fa fa-school" style="font-size: 32px; color: #9ca3af;"></i>
                                    </div>
                                @endif

                                <h2 style="font-weight: 700; margin-bottom: 4px; font-size: 24px; color: #1f2937;">{{ $currentSchool->nama_sekolah }}</h2>
                                <p style="color: #6b7280; font-size: 14px; margin-bottom: 32px;">NPSN: {{ $currentSchool->npsn }}</p>

                                <div x-data="window.spinnerWidget()" style="width: 100%; max-width: 320px; margin: 0 auto;">
                                    <div style="position: relative; margin: 0 auto 32px; width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">
                                        <!-- Spinning outer ring -->
                                        <div 
                                            class="rounded-circle"
                                            :class="isSpinning ? 'spinning-ring' : (result ? '' : '')"
                                            :style="'position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: ' + (isSpinning ? '8px solid transparent' : (result ? '8px solid #10b981' : '8px solid var(--event-primary, #0072FF)')) + ';'"
                                        ></div>

                                        <!-- Inner circle with text -->
                                        <div 
                                            class="rounded-circle"
                                            :style="'width: 170px; height: 170px; display: flex; align-items: center; justify-content: center; transition: all 0.3s; background: ' + (result && !isSpinning ? 'rgba(16,185,129,0.1)' : 'rgba(0,114,255,0.1)') + ';'"
                                        >
                                            <template x-if="isSpinning">
                                                <span style="font-weight: 800; font-size: 80px; color: #f59e0b;" x-text="displayNumber"></span>
                                            </template>
                                            <template x-if="!isSpinning && result">
                                                <span style="font-weight: 800; font-size: 80px; color: #10b981;" x-text="result"></span>
                                            </template>
                                            <template x-if="!isSpinning && !result">
                                                <span style="font-weight: 800; font-size: 60px; color: var(--event-primary, #0072FF);">?</span>
                                            </template>
                                        </div>
                                    </div>

                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                        <template x-if="!result">
                                            <button 
                                                class="zubuz-default-btn"
                                                :disabled="isSpinning"
                                                @click="startSpin()"
                                                style="width: 100%; border: none; background: #f59e0b; padding: 16px;"
                                            >
                                                <template x-if="isSpinning">
                                                    <span><i class="fa fa-spinner fa-spin" style="margin-right: 8px;"></i> Mengundi...</span>
                                                </template>
                                                <template x-if="!isSpinning">
                                                    <span style="font-size: 18px;"><i class="fa fa-random" style="margin-right: 8px;"></i> SPIN SEKARANG!</span>
                                                </template>
                                            </button>
                                        </template>

                                        <template x-if="result && !isSpinning">
                                            <div>
                                                <div style="background: rgba(16,185,129,0.1); color: #10b981; padding: 16px; border-radius: 12px; margin-bottom: 16px; font-size: 18px;">
                                                    <i class="fa fa-star" style="margin-right: 8px;"></i> Nomor Urut: <strong x-text="'#' + result"></strong>
                                                </div>
                                                <button 
                                                    class="zubuz-default-btn"
                                                    wire:click="saveResult"
                                                    style="width: 100%; border: none; background: #10b981;"
                                                >
                                                    <span><i class="fa fa-check" style="margin-right: 8px;"></i> Simpan & Lanjut</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($drawnSchools->count() > 0)
                        <div class="text-center mt-4">
                            <button wire:click="resetDrawing" wire:confirm="PERINGATAN: Semua hasil undian pada kategori ini akan DIHAPUS. Yakin ingin reset?" style="background: transparent; border: 1px solid #ef4444; color: #ef4444; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                <i class="fa fa-sync" style="margin-right: 4px;"></i> Reset Undian Kategori Ini
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Kolom Kanan: Progress -->
                <div class="col-lg-5">
                    <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
                        <div style="background: #f9fafb; padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                            <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;"><i class="fa fa-list-ol" style="margin-right: 8px; color: #6b7280;"></i> Urutan Sudah Ditentukan</h5>
                        </div>
                        <div style="padding: 0;">
                            @if($drawnSchools->count() > 0)
                                <div style="max-height: 420px; overflow-y: auto;">
                                    <table style="width: 100%; border-collapse: collapse;">
                                        <thead style="background: #f3f4f6; position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th style="padding: 12px 20px; text-align: left; font-size: 13px; color: #4b5563; font-weight: 600; width: 60px;">No</th>
                                                <th style="padding: 12px 20px; text-align: left; font-size: 13px; color: #4b5563; font-weight: 600;">Sekolah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($drawnSchools as $school)
                                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                                    <td style="padding: 16px 20px;">
                                                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: var(--event-primary, #0072FF); color: #fff; font-weight: 700; font-size: 14px;">
                                                            {{ $school->urutan_tampil }}
                                                        </span>
                                                    </td>
                                                    <td style="padding: 16px 20px;">
                                                        <h6 style="margin: 0; font-size: 14px; font-weight: 600; color: #1f2937;">{{ $school->nama_sekolah }}</h6>
                                                        <span style="font-size: 12px; color: #6b7280;">NPSN: {{ $school->npsn }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div style="padding: 60px 20px; text-align: center;">
                                    <i class="fa fa-dice" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                    <p style="margin: 0; color: #6b7280; font-size: 14px;">Belum ada hasil undian. Klik <strong style="color: #1f2937;">SPIN SEKARANG</strong> untuk memulai!</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @script
            <script>
                window.spinnerWidget = function() {
                    return {
                        isSpinning: false,
                        displayNumber: 0,
                        result: @json($spinResult),
                        totalNumbers: @json($totalSchools),
                        interval: null,

                        startSpin() {
                            if (this.isSpinning) return;
                            this.isSpinning = true;
                            this.result = null;

                            let counter = 0;
                            const maxIterations = 30 + Math.floor(Math.random() * 20);
                            let speed = 50;

                            const animate = () => {
                                this.displayNumber = Math.floor(Math.random() * this.totalNumbers) + 1;
                                counter++;

                                if (counter < maxIterations) {
                                    speed += counter * 2;
                                    setTimeout(animate, Math.min(speed, 300));
                                } else {
                                    this.isSpinning = false;
                                    Livewire.find('{{ $this->getId() }}').call('spin').then(() => {
                                        this.result = Livewire.find('{{ $this->getId() }}').get('spinResult');
                                        this.displayNumber = this.result;
                                    });
                                }
                            };

                            animate();
                        }
                    };
                }
            </script>
            @endscript
        </div>
    </div>
    @else
        <div style="background: linear-gradient(135deg, var(--event-primary, #0072FF) 0%, var(--event-accent, #00D4AA) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div class="wow fadeInUp" style="background: #fff; border-radius: 16px; padding: 40px; width: 100%; max-width: 450px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(0,114,255,0.1); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                    <i class="fa fa-lock" style="font-size: 32px; color: var(--event-primary, #0072FF);"></i>
                </div>
                <h3 style="font-weight: 700; color: #1f2937; margin-bottom: 12px;">Akses Terkunci</h3>
                <p style="color: #6b7280; font-size: 15px; margin-bottom: 32px;">Silakan masukkan kode akses untuk membuka halaman Pengundian Acara <strong>{{ $eventner->nama_event }}</strong>.</p>
                
                <form wire:submit.prevent="verifyCode">
                    <div style="text-align: left; margin-bottom: 24px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">Kode Akses</label>
                        <input type="password" wire:model="inputCode" style="width: 100%; border: 2px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; font-size: 18px; text-align: center; outline: none; transition: border-color 0.2s;" placeholder="Masukkan PIN" autofocus>
                        @error('inputCode')
                            <div style="color: #ef4444; font-size: 13px; margin-top: 8px; text-align: center;">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="zubuz-default-btn" style="width: 100%; border: none; padding: 16px;">
                        <span><i class="fa fa-unlock" style="margin-right: 8px;"></i> Buka Kunci</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
