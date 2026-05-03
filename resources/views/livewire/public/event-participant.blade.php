<div>
    {{-- Hero Banner --}}
    <div class="section" style="background: linear-gradient(135deg, #0072FF 0%, #0046b3 100%); padding: 60px 0 40px;">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    @if($eventner->logo_event)
                        <div class="wow zoomIn" style="width: 80px; height: 80px; border-radius: 50%; overflow: hidden; margin: 0 auto 16px; border: 3px solid rgba(255,255,255,0.3);">
                            <img src="{{ asset('storage/' . $eventner->logo_event) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    @endif
                    <span style="display:inline-block; background: rgba(255,255,255,0.2); color: #fff; padding: 4px 16px; border-radius: 20px; font-size: 13px; margin-bottom: 12px;">Kontingen Peserta</span>
                    <h1 class="wow fadeInUp" style="color: #fff; font-size: 36px;">Daftar Peserta Terdaftar</h1>
                    <p class="wow fadeInUp" style="color: rgba(255,255,255,0.8); font-size: 17px; margin-top: 8px;">
                        {{ $eventner->nama_event }} — <em>{{ $eventner->diselenggarakan_oleh }}</em>
                    </p>
                    <div class="wow fadeInUp mt-3">
                        <a href="{{ route('event.detail', $eventner->slug) }}" style="display: inline-flex; align-items: center; gap: 6px; color: #fff; text-decoration: none; font-weight: 600; font-size: 15px;">
                            <svg width="16" height="14" viewBox="0 0 26 22" fill="none"><path d="M10.5 2.25L1.75 11M1.75 11L10.5 19.75M1.75 11L24.25 11" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Kembali ke Info Event
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $totalKontingen = $eventner->competitionCategories->sum(fn($c) => $c->registrations->count());
        $totalAnggota = $eventner->competitionCategories->sum(fn($c) => $c->registrations->sum(fn($r) => $r->participants->count()));
        $totalBooking = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'booking')->count());
        $totalVerified = $eventner->competitionCategories->sum(fn($c) => $c->registrations->where('status_berkas', 'Terverifikasi')->count());
    @endphp
    <div class="section" style="padding: 30px 0 10px;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-2 col-md-4 col-6 mb-3 wow zoomIn">
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Kategori</p>
                        <h3 style="font-size: 28px; color: #111827; margin: 0;">{{ $eventner->competitionCategories->count() }}</h3>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3 wow zoomIn">
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Total Kontingen</p>
                        <h3 style="font-size: 28px; color: #10b981; margin: 0;">{{ $totalKontingen }}</h3>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3 wow zoomIn">
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Booking</p>
                        <h3 style="font-size: 28px; color: #6b7280; margin: 0;">{{ $totalBooking }}</h3>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3 wow zoomIn">
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Terverifikasi</p>
                        <h3 style="font-size: 28px; color: #0072FF; margin: 0;">{{ $totalVerified }}</h3>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3 wow zoomIn">
                    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 4px;">Total Peserta</p>
                        <h3 style="font-size: 28px; color: #f59e0b; margin: 0;">{{ $totalAnggota }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Participants List --}}
    <div class="section zubuz-section-padding3">
        <div class="container">
            @foreach($eventner->competitionCategories as $cat)
            <div class="wow fadeInUp mb-4" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;">
                {{-- Category Header --}}
                <div style="background: linear-gradient(135deg, #0072FF, #0046b3); padding: 14px 24px; display: flex; justify-content: space-between; align-items: center;">
                    <h6 style="margin: 0; color: #fff; font-weight: 600;">
                        <i class="fa fa-trophy" style="margin-right: 6px;"></i> {{ $cat->name }}
                    </h6>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        @if($cat->tanggal_pelaksanaan)
                        <span style="background: rgba(255,255,255,0.2); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                            <i class="fa fa-calendar"></i> {{ \Carbon\Carbon::parse($cat->tanggal_pelaksanaan)->translatedFormat('d M Y') }}
                        </span>
                        @endif
                        <span style="background: rgba(255,255,255,0.2); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px;">
                            {{ $cat->registrations->count() }} kontingen
                        </span>
                    </div>
                </div>

                @if($cat->registrations->count() > 0)
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #6b7280; width: 40px;">No</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #6b7280;">Sekolah / Kontingen</th>
                                    <th style="padding: 12px 16px; text-align: left; font-weight: 600; font-size: 13px; color: #6b7280;">Pelatih</th>
                                    <th style="padding: 12px 16px; text-align: center; font-weight: 600; font-size: 13px; color: #6b7280;">Pasukan</th>
                                    <th style="padding: 12px 16px; text-align: center; font-weight: 600; font-size: 13px; color: #6b7280;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cat->registrations as $idx => $reg)
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 12px 16px; font-weight: 600; color: #9ca3af;">{{ $idx + 1 }}</td>
                                    <td style="padding: 12px 16px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            @if($reg->logo_sekolah)
                                                <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #e5e7eb;" alt="">
                                            @else
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(0,114,255,0.1); display: flex; align-items: center; justify-content: center; color: #0072FF; font-size: 14px;">
                                                    <i class="fa fa-school"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <span style="font-weight: 600; color: #111827;">{{ $reg->nama_sekolah }}</span>
                                                <span style="color: #9ca3af; font-size: 12px; margin-left: 4px;">({{ $reg->npsn }})</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 12px 16px; color: #4b5563;">
                                        {{ $reg->nama_pelatih ?? '—' }}
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span style="background: rgba(0,114,255,0.1); color: #0072FF; padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                                            {{ $reg->participants->count() }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        @if($reg->status_berkas === 'Terverifikasi')
                                            <span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                <i class="fa fa-check"></i> Verifikasi
                                            </span>
                                        @elseif($reg->status_berkas === 'Ditolak')
                                            <span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Ditolak</span>
                                        @elseif($reg->status_berkas === 'booking')
                                            <span style="background: rgba(107,114,128,0.1); color: #6b7280; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Booking</span>
                                        @elseif($reg->status_berkas === 'dibatalkan')
                                            <span style="background: rgba(30,41,59,0.1); color: #1e293b; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Batal</span>
                                        @else
                                            <span style="background: rgba(245,158,11,0.1); color: #f59e0b; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Menunggu</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; padding: 30px; color: #9ca3af;">
                        <p style="margin: 0;">Belum ada kontingen di kategori ini.</p>
                    </div>
                @endif
            </div>
            @endforeach

            @if($eventner->competitionCategories->count() === 0)
            <div class="zubuz-section-title center">
                <p style="color: #9ca3af;">Belum ada kategori lomba yang tersedia.</p>
            </div>
            @endif
        </div>
    </div>
</div>
