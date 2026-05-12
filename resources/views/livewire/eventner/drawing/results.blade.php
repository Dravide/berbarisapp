<div wire:poll.3s>
    {{-- Hero Banner --}}
    <div style="background: linear-gradient(135deg, var(--event-primary, #0072FF) 0%, var(--event-accent, #00D4AA) 100%); padding: 140px 0 40px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; border-radius: 50%; background: rgba(255,255,255,0.08);"></div>
        <div style="position: absolute; bottom: -30%; left: -10%; width: 300px; height: 300px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div class="row align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-4 mb-lg-0">
                    <span style="display:inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); color: #fff; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 12px;">
                        <i class="fa fa-broadcast-tower"></i> Hasil Undian Live
                    </span>
                    <h1 class="wow fadeInUp" style="color: #fff; font-size: clamp(24px, 5vw, 36px); margin-bottom: 8px;">Hasil Pengundian Urutan Tampil</h1>
                    <p class="wow fadeInUp" style="color: rgba(255,255,255,0.9); font-size: 15px; margin-bottom: 0;">Event: <strong>{{ $eventner->nama_event }}</strong></p>
                    <div class="mt-4 wow fadeInUp">
                        <a href="{{ route('event.detail', $slug) }}" style="background: transparent; border: 1px solid rgba(255,255,255,0.5); color: #fff; padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; text-decoration: none; margin-right: 8px; display: inline-block;">
                            <i class="fa fa-arrow-left me-1"></i> Kembali ke Event
                        </a>
                        <a href="{{ route('event.drawing.spin', $slug) }}" style="background: #fff; border: none; color: var(--event-primary, #0072FF); padding: 10px 20px; border-radius: 30px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block;">
                            <i class="fa fa-random me-1"></i> Kembali ke Spin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="section zubuz-section-padding3" style="padding-top: 40px;">
        <div class="container">

            <!-- Tabs Kategori -->
            <div class="wow fadeInUp" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 24px;">
                @foreach ($categories as $category)
                    <button wire:click="switchTab('{{ $category['id'] }}')"
                        style="white-space: nowrap; padding: 12px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s; border: none; outline: none; {{ $activeTab == $category['id'] ? 'background: var(--event-primary, #0072FF); color: #fff;' : 'background: #f3f4f6; color: #4b5563;' }}">
                        <i class="fa fa-medal" style="margin-right: 6px;"></i> {{ $category['name'] }}
                    </button>
                @endforeach
            </div>

            <!-- Live Badge -->
            <div class="wow fadeInUp" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="background: #ef4444; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; box-shadow: 0 4px 10px rgba(239,68,68,0.3);">
                        <i class="fa fa-circle text-white me-2" style="font-size: 10px; animation: pulse 2s infinite;"></i> LIVE
                    </span>
                    <span style="color: #6b7280; font-size: 14px;">Update otomatis setiap 3 detik</span>
                </div>
                <span style="background: rgba(0,114,255,0.1); color: var(--event-primary, #0072FF); padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; border: 1px solid rgba(0,114,255,0.2);">
                    {{ $results->count() }} / {{ $totalSchools }} Ditentukan
                </span>
            </div>

            <!-- Tabel Hasil -->
            <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                @if($results->count() > 0)
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                            <thead style="background: var(--event-primary, #0072FF); color: #fff;">
                                <tr>
                                    <th style="padding: 16px 24px; text-align: left; font-size: 14px; font-weight: 600; width: 100px;">URUTAN</th>
                                    <th style="padding: 16px 24px; text-align: left; font-size: 14px; font-weight: 600; width: 100px;">LOGO</th>
                                    <th style="padding: 16px 24px; text-align: left; font-size: 14px; font-weight: 600;">NAMA SEKOLAH / KONTINGEN</th>
                                    <th style="padding: 16px 24px; text-align: left; font-size: 14px; font-weight: 600;">NPSN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $reg)
                                    <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.3s; background: {{ $loop->last ? 'rgba(16,185,129,0.05)' : 'transparent' }};">
                                        <td style="padding: 16px 24px;">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 50%; background: {{ $loop->last ? '#10b981' : 'var(--event-primary, #0072FF)' }}; color: #fff; font-weight: 700; font-size: 18px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                                {{ $reg->urutan_tampil }}
                                            </span>
                                        </td>
                                        <td style="padding: 16px 24px;">
                                            @if($reg->logo_sekolah)
                                                <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid #e5e7eb; padding: 4px; background: #fff;" alt="Logo">
                                            @else
                                                <div style="width: 50px; height: 50px; border-radius: 8px; background: #f3f4f6; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="fa fa-school" style="font-size: 20px; color: #9ca3af;"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td style="padding: 16px 24px;">
                                            <h5 style="margin: 0; font-size: 16px; font-weight: 700; color: #1f2937;">{{ $reg->nama_sekolah }}</h5>
                                        </td>
                                        <td style="padding: 16px 24px;">
                                            <span style="font-size: 14px; color: #6b7280; font-weight: 600;">{{ $reg->npsn }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding: 80px 20px; text-align: center;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: rgba(0,114,255,0.05); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
                            <i class="fa fa-hourglass-half" style="font-size: 40px; color: var(--event-primary, #0072FF);"></i>
                        </div>
                        <h4 style="font-weight: 700; color: #1f2937; margin-bottom: 12px;">Menunggu Pengundian...</h4>
                        <p style="color: #6b7280; font-size: 15px; margin: 0;">Hasil akan muncul otomatis saat pengundian dilakukan di laman Spin.</p>
                    </div>
                @endif
            </div>

            <style>
                @keyframes pulse {
                    0% { transform: scale(0.95); opacity: 1; }
                    50% { transform: scale(1.2); opacity: 0.5; }
                    100% { transform: scale(0.95); opacity: 1; }
                }
            </style>
        </div>
    </div>
</div>
