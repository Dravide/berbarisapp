<div class="premium-event-page">
    {{-- Hero Banner --}}
    <div class="pm-hero" style="background: var(--event-primary, #2665fd); text-align: center;">
        <div class="pm-hero-content">
            <div class="pm-event-badge"><i class="fa fa-ticket-alt"></i> Check-in Tiket</div>
            <h1 class="pm-event-title" style="font-size: clamp(22px, 5vw, 32px);">{{ $eventner->nama_event }}</h1>
            <p class="pm-event-org">Scan QR Code tiket untuk check-in peserta</p>
        </div>
    </div>

    <div class="section zubuz-section-padding3">
        <div class="container" style="max-width: 600px;">

            @if (session()->has('error'))
                <div style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 14px 20px; border-radius: 8px; margin-bottom: 16px;">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ========== SCANNER + RESULT ========== --}}
            <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-bottom: 16px;">
                <div style="padding: 14px 18px; background: var(--event-primary, #2665fd); color: #fff;">
                    <strong style="font-size: 15px;"><i class="fa fa-qrcode"></i> Scanner Aktif</strong>
                    <div style="font-size: 12px; opacity: 0.8;">Arahkan kamera ke QR Code tiket</div>
                </div>

                <div id="qr-reader" style="width: 100%; background: #000; min-height: 240px;"></div>
            </div>

            {{-- Manual input fallback --}}
            <div class="wow fadeInUp" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                <form wire:submit="lookupTicket">
                    <label style="font-weight: 600; display: block; margin-bottom: 6px; font-size: 14px;">Atau input kode order manual</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text"
                               wire:model="manualCode"
                               placeholder="TKT-XXXXXXXX"
                               style="flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; font-size: 14px; outline: none; text-transform: uppercase;">
                        <button type="submit" class="zubuz-default-btn" style="padding: 10px 18px;">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Result panel --}}
            @if($result)
                @php
                    $kind = $result['kind'] ?? null;
                    $bg = match($kind) {
                        'success' => 'rgba(16,185,129,0.08)',
                        'ready' => 'rgba(0,114,255,0.06)',
                        'already', 'pending', 'expired' => 'rgba(245,158,11,0.08)',
                        default => 'rgba(239,68,68,0.08)',
                    };
                    $border = match($kind) {
                        'success' => '#10b981',
                        'ready' => '#0072FF',
                        'already', 'pending', 'expired' => '#f59e0b',
                        default => '#ef4444',
                    };
                    $title = match($kind) {
                        'success' => '✓ Check-in Berhasil',
                        'ready' => 'Siap Check-in',
                        'already' => 'Sudah Check-in',
                        'pending' => 'Belum Dibayar',
                        'expired' => 'Tiket Expired',
                        'not_found' => 'Tiket Tidak Ditemukan',
                        'not_ready' => 'Tidak Bisa Check-in',
                        default => 'Hasil',
                    };
                @endphp

                <div class="wow fadeInUp" style="background: {{ $bg }}; border: 2px solid {{ $border }}; border-radius: 8px; padding: 20px;">
                    <h5 style="margin: 0 0 12px; font-weight: 700; color: {{ $border }};">{{ $title }}</h5>

                    @if(isset($result['ticket']))
                        @php $t = $result['ticket']; @endphp
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr><td style="padding: 6px 0; color: #64748b; font-size: 13px;">Order</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ $t->order_code }}</td></tr>
                            <tr><td style="padding: 6px 0; color: #64748b; font-size: 13px;">Nama</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ $t->buyer_name }}</td></tr>
                            <tr><td style="padding: 6px 0; color: #64748b; font-size: 13px;">Jumlah</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ $t->quantity }} tiket</td></tr>
                            <tr><td style="padding: 6px 0; color: #64748b; font-size: 13px;">Status</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ $t->status }}</td></tr>
                            @if($t->checked_in_at)
                                <tr><td style="padding: 6px 0; color: #64748b; font-size: 13px;">Check-in</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ $t->checked_in_at->translatedFormat('d M Y H:i') }}</td></tr>
                            @endif
                        </table>

                        @if($kind === 'ready')
                            <button type="button"
                                    wire:click="askConfirm({{ $t->id }}, '{{ $t->order_code }}')"
                                    class="zubuz-default-btn"
                                    style="width: 100%; margin-top: 14px;"
                                    wire:loading.attr="disabled">
                                <i class="fa fa-check"></i> Konfirmasi Check-in
                            </button>
                        @endif
                    @else
                        <p style="margin: 0; color: #64748b;">Kode: <strong>{{ $result['code'] ?? '-' }}</strong></p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const target = 'qr-reader';
    const targetEl = document.getElementById(target);
    if (targetEl && typeof Html5Qrcode !== 'undefined') {
        const scanner = new Html5Qrcode(target);
        let busy = false;

        function dispatchScan(text) {
            if (busy) return;
            busy = true;
            const component = window.Livewire?.find(document.querySelector('[wire\\:id]')?.getAttribute('wire:id'));
            if (component) {
                component.set('scannedCode', text);
                component.call('lookupTicket', text).finally(() => { busy = false; });
            }
        }

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                targetEl.innerHTML = '<div style="color:#fff; padding:20px; text-align:center;">Kamera tidak tersedia. Gunakan input manual di bawah.</div>';
                return;
            }
            const cameraId = cameras.find(c => /back|rear|environment/i.test(c.label))?.id || cameras[0].id;

            scanner.start(
                cameraId,
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decodedText) => {
                    dispatchScan(decodedText.trim());
                },
                () => { /* ignore per-frame errors */ }
            ).catch(err => {
                targetEl.innerHTML = '<div style="color:#fff; padding:20px; text-align:center;">Tidak bisa mengakses kamera: ' + err + '<br><small>Gunakan input manual di bawah.</small></div>';
            });
        }).catch(err => {
            targetEl.innerHTML = '<div style="color:#fff; padding:20px; text-align:center;">Tidak bisa membaca kamera: ' + err + '<br><small>Gunakan input manual di bawah.</small></div>';
        });

        window.addEventListener('beforeunload', () => {
            if (scanner && scanner.isScanning) {
                scanner.stop().catch(() => {});
            }
        });
    }
});

// Tangkap event dari Livewire dispatch
document.addEventListener('livewire:init', () => {
    window.addEventListener('checkin:ask-confirm', (event) => {
        const detail = event.detail || {};
        const id = Array.isArray(detail) ? detail[0]?.id : detail.id;
        const code = Array.isArray(detail) ? detail[0]?.code : detail.code;
        if (!id || !window.Swal) return;

        Swal.fire({
            title: 'Konfirmasi Check-in?',
            html: 'Tiket <strong>' + code + '</strong> akan ditandai sudah masuk.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check"></i> Ya, Check-in',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0072FF',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                const wireId = document.querySelector('[wire\\:id]')?.getAttribute('wire:id');
                if (wireId && window.Livewire) {
                    window.Livewire.find(wireId).call('confirmCheckIn', id);
                }
            }
        });
    });
});
</script>
