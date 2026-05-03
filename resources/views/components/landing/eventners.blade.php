@php
    $eventners = $eventners ?? [];
@endphp

@if($eventners->count() > 0)
<div class="section zubuz-section-padding3" id="eventners">
    <div class="container">
        <div class="zubuz-section-title center">
            <h2>Penyelenggara Event yang Telah Bergabung</h2>
            <p class="mt-2 text-muted" style="max-width: 600px; margin: 8px auto 0;">
                Mereka telah mempercayakan pengelolaan event dan kompetisi mereka melalui platform kami.
            </p>
        </div>
        <div class="row">
            @foreach($eventners as $eventner)
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="zubuz-iconbox-wrap center" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 32px 20px; height: 100%; transition: all 0.3s ease;">
                    {{-- Logo --}}
                    <div style="width: 72px; height: 72px; border-radius: 50%; overflow: hidden; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; background: #f3f4f6;">
                        @if($eventner->logo_event)
                            <img src="{{ Storage::url($eventner->logo_event) }}" alt="{{ $eventner->nama_event }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <span style="font-size: 28px; font-weight: 700; color: #0072FF;">
                                {{ strtoupper(substr($eventner->nama_event, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="zubuz-iconbox-data">
                        <h3 style="font-size: 18px; margin-bottom: 4px; line-height: 1.3;">{{ $eventner->nama_event }}</h3>
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 10px;">{{ $eventner->diselenggarakan_oleh }}</p>

                        @if($eventner->tingkat_perlombaan)
                        <span style="display: inline-block; background: #eff6ff; color: #0072FF; font-size: 11px; padding: 2px 10px; border-radius: 20px; font-weight: 600;">
                            {{ $eventner->tingkat_perlombaan }}
                        </span>
                        @endif

                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f3f4f6;">
                            <div class="d-flex justify-content-center gap-3" style="font-size: 12px; color: #9ca3af;">
                                <span>
                                    <i class="fas fa-map-marker-alt" style="color: #0072FF;"></i>
                                    {{ Str::limit($eventner->lokasi, 15) }}
                                </span>
                                <span>
                                    <i class="fas fa-users" style="color: #10b981;"></i>
                                    {{ $eventner->registrations->count() }} peserta
                                </span>
                            </div>
                        </div>

                        <div style="margin-top: 14px;">
                            <a href="{{ route('event.detail', $eventner->slug) }}"
                               style="display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: #0072FF; text-decoration: none;">
                                Lihat Event
                                <svg width="16" height="14" viewBox="0 0 26 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15.5 2.25L24.25 11M24.25 11L15.5 19.75M24.25 11L1.75 11" stroke="#0072FF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- CTA to explore more --}}
        @if($eventners->count() > 8)
        <div class="text-center mt-4">
            <a href="#" class="zubuz-default-btn" style="background: transparent; border: 2px solid #0072FF; color: #0072FF;">
                <span>Lihat Semua Event</span>
            </a>
        </div>
        @endif
    </div>
</div>
@endif
