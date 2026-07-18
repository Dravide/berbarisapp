<div class="min-h-screen bg-surface">

    {{-- ========== HERO ========== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary text-white py-8 md:py-10">
        <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

        <div class="container-landing relative z-10 flex flex-col items-center text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                <i class="ti ti-qrcode"></i>
                Check-in Tiket
            </span>
            <h1 class="font-display text-xl font-extrabold tracking-tight sm:text-2xl max-w-2xl leading-tight">
                {{ $eventner->nama_event }}
            </h1>
            <p class="mt-2 text-xs font-medium text-white/80 md:text-sm max-w-xl">
                Scan QR Code tiket peserta untuk check-in masuk area event
            </p>
        </div>
    </div>

    {{-- ========== MAIN ========== --}}
    <div class="container-landing py-6">
        <div class="max-w-lg mx-auto flex flex-col gap-5">

            @if (session()->has('error'))
                <div class="p-4 rounded-xl bg-error/10 border border-error/20 text-error text-sm font-semibold flex items-center gap-2">
                    <i class="ti ti-alert-circle text-lg"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- ========== SCANNER ========== --}}
            <div class="surface-card overflow-hidden">
                <div class="bg-primary text-on-primary px-5 py-3.5 flex items-center justify-between">
                    <div>
                        <h3 class="font-display text-sm font-bold text-on-primary mb-0 inline-flex items-center gap-2">
                            <i class="ti ti-qrcode"></i> Scanner QR
                        </h3>
                        <span class="text-[10px] text-on-primary/80 block mt-0.5">Arahkan kamera ke QR Code tiket</span>
                    </div>
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-400"></span>
                    </span>
                </div>
                <div id="qr-reader" class="w-full bg-black" style="min-height: 280px;"></div>
            </div>

            {{-- ========== MANUAL INPUT ========== --}}
            <div class="surface-card p-5">
                <label class="text-sm font-bold text-deep-slate block mb-3 inline-flex items-center gap-2">
                    <i class="ti ti-keyboard text-primary"></i> Input Manual Kode Order
                </label>
                <form wire:submit="lookupTicket" class="flex gap-2">
                    <input type="text"
                           wire:model="manualCode"
                           placeholder="TKT-XXXXXXXX"
                           class="field-input flex-1 uppercase font-mono text-sm tracking-wider"
                           autocomplete="off">
                    <button type="submit" class="btn-primary py-2.5 px-4 text-sm font-bold whitespace-nowrap">
                        <i class="ti ti-search"></i> Cari
                    </button>
                </form>
            </div>

            {{-- ========== RESULT ========== --}}
            @if($result)
                @php
                    $kind = $result['kind'] ?? null;
                    $config = match($kind) {
                        'success' => ['bg' => 'bg-emerald-500/5', 'border' => 'border-emerald-500/30', 'icon' => 'ti-circle-check', 'iconBg' => 'bg-emerald-500', 'title' => 'Check-in Berhasil!', 'subtitle' => 'Peserta berhasil masuk'],
                        'ready' => ['bg' => 'bg-primary/5', 'border' => 'border-primary/30', 'icon' => 'ti-qrcode', 'iconBg' => 'bg-primary', 'title' => 'Tiket Valid — Siap Check-in', 'subtitle' => 'Konfirmasi untuk tandai masuk'],
                        'already' => ['bg' => 'bg-amber-500/5', 'border' => 'border-amber-500/30', 'icon' => 'ti-alert-triangle', 'iconBg' => 'bg-amber-500', 'title' => 'Sudah Check-in', 'subtitle' => 'Tiket ini sudah digunakan masuk'],
                        'pending' => ['bg' => 'bg-amber-500/5', 'border' => 'border-amber-500/30', 'icon' => 'ti-clock', 'iconBg' => 'bg-amber-500', 'title' => 'Belum Dibayar', 'subtitle' => 'Peserta belum menyelesaikan pembayaran'],
                        'expired' => ['bg' => 'bg-red-500/5', 'border' => 'border-red-500/30', 'icon' => 'ti-clock-off', 'iconBg' => 'bg-red-500', 'title' => 'Tiket Expired', 'subtitle' => 'Masa pembayaran sudah habis'],
                        'not_found' => ['bg' => 'bg-red-500/5', 'border' => 'border-red-500/30', 'icon' => 'ti-search-off', 'iconBg' => 'bg-red-500', 'title' => 'Tiket Tidak Ditemukan', 'subtitle' => 'Kode order tidak terdaftar di event ini'],
                        'not_ready' => ['bg' => 'bg-amber-500/5', 'border' => 'border-amber-500/30', 'icon' => 'ti-lock', 'iconBg' => 'bg-amber-500', 'title' => 'Tidak Bisa Check-in', 'subtitle' => 'Status tiket tidak memungkinkan check-in'],
                        default => ['bg' => 'bg-surface-container-low', 'border' => 'border-outline-variant/50', 'icon' => 'ti-info-circle', 'iconBg' => 'bg-cool-gray', 'title' => 'Hasil', 'subtitle' => ''],
                    };
                @endphp

                <div class="surface-card overflow-hidden border-2 {{ $config['border'] }}">
                    {{-- Result Header --}}
                    <div class="{{ $config['bg'] }} px-5 py-4 flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full {{ $config['iconBg'] }} text-white shrink-0">
                            <i class="ti {{ $config['icon'] }} text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-display text-base font-bold text-deep-slate mb-0.5">{{ $config['title'] }}</h4>
                            <p class="text-xs text-on-surface-variant m-0">{{ $config['subtitle'] }}</p>
                        </div>
                    </div>

                    @if(isset($result['ticket']))
                        @php $t = $result['ticket']; @endphp
                        <div class="p-5 divide-y divide-outline-variant/30">
                            <div class="flex justify-between items-center py-3 first:pt-0">
                                <span class="text-sm text-on-surface-variant font-medium">Kode Order</span>
                                <span class="text-sm font-bold text-deep-slate font-mono tracking-wider">{{ $t->order_code }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-sm text-on-surface-variant font-medium">Nama Pembeli</span>
                                <span class="text-sm font-bold text-deep-slate">{{ $t->buyer_name }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-sm text-on-surface-variant font-medium">Jumlah</span>
                                <span class="text-sm font-bold text-deep-slate">{{ $t->quantity }} tiket</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-sm text-on-surface-variant font-medium">Status</span>
                                @php
                                    $statusColors = [
                                        'CHECKED_IN' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                                        'PAID' => 'bg-primary/10 text-primary border-primary/20',
                                        'PENDING' => 'bg-amber-500/10 text-amber-600 border-amber-500/20',
                                        'EXPIRED' => 'bg-red-500/10 text-red-600 border-red-500/20',
                                    ];
                                    $statusLabels = ['CHECKED_IN' => 'Sudah Masuk', 'PAID' => 'Lunas', 'PENDING' => 'Pending', 'EXPIRED' => 'Expired'];
                                @endphp
                                <span class="inline-flex items-center rounded-full {{ $statusColors[$t->status] ?? 'bg-surface-container text-on-surface-variant' }} px-2.5 py-0.5 text-xs font-bold border">
                                    {{ $statusLabels[$t->status] ?? $t->status }}
                                </span>
                            </div>
                            @if($t->checked_in_at)
                                <div class="flex justify-between items-center py-3 last:pb-0">
                                    <span class="text-sm text-on-surface-variant font-medium">Waktu Check-in</span>
                                    <span class="text-sm font-bold text-deep-slate">{{ \Carbon\Carbon::parse($t->checked_in_at)->translatedFormat('d M Y, H:i') }} WIB</span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Button --}}
                        @if($kind === 'ready')
                            <div class="px-5 pb-5 pt-2">
                                <button type="button"
                                        wire:click="askConfirm({{ $t->id }}, '{{ $t->order_code }}')"
                                        class="btn-secondary w-full py-3.5 font-bold text-sm shadow-md"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="askConfirm">
                                        <i class="ti ti-check"></i> Konfirmasi Check-in
                                    </span>
                                    <span wire:loading wire:target="askConfirm" class="inline-flex items-center gap-2">
                                        <span class="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        @endif

                        @if($kind === 'success')
                            <div class="px-5 pb-5 pt-2">
                                <div class="flex items-center justify-center gap-2 text-xs font-bold text-emerald-600">
                                    <i class="ti ti-circle-check-filled text-base"></i>
                                    Check-in {{ \Carbon\Carbon::parse($t->checked_in_at)->diffForHumans() }}
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="px-5 py-6 text-center">
                            <p class="text-sm font-semibold text-on-surface-variant mb-0">
                                Kode: <code class="bg-surface-container px-2 py-0.5 rounded font-mono text-error">{{ $result['code'] ?? '-' }}</code>
                            </p>
                            <p class="text-xs text-on-surface-variant mt-2 mb-0">Pastikan kode order benar dan tiket terdaftar di event ini.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>

{{-- SweetAlert2 + html5-qrcode --}}
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
            const component = window.Livewire?.find(
                document.querySelector('[wire\\:id]')?.getAttribute('wire:id')
            );
            if (component) {
                component.set('scannedCode', text);
                component.call('lookupTicket', text).finally(() => { busy = false; });
            }
        }

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                targetEl.innerHTML = '<div class="flex items-center justify-center text-on-surface-variant text-sm py-16 bg-surface-container-low"><div class="text-center"><i class="ti ti-camera-off text-3xl block mb-2"></i>Kamera tidak tersedia.<br><small>Gunakan input manual.</small></div></div>';
                return;
            }
            const cameraId = cameras.find(c => /back|rear|environment/i.test(c.label))?.id || cameras[0].id;

            scanner.start(
                cameraId,
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decodedText) => dispatchScan(decodedText.trim()),
                () => { /* ignore frame errors */ }
            ).catch(err => {
                targetEl.innerHTML = '<div class="flex items-center justify-center text-error text-sm py-16 bg-surface-container-low"><div class="text-center"><i class="ti ti-camera-off text-3xl block mb-2"></i>Tidak bisa mengakses kamera.<br><small>Gunakan input manual.</small></div></div>';
            });
        }).catch(err => {
            targetEl.innerHTML = '<div class="flex items-center justify-center text-error text-sm py-16 bg-surface-container-low"><div class="text-center"><i class="ti ti-camera-off text-3xl block mb-2"></i>Tidak bisa membaca kamera.<br><small>Gunakan input manual.</small></div></div>';
        });

        window.addEventListener('beforeunload', () => {
            if (scanner && scanner.isScanning) scanner.stop().catch(() => {});
        });
    }
});

// Livewire event listener untuk SweetAlert2 konfirmasi
document.addEventListener('livewire:init', () => {
    window.addEventListener('checkin:ask-confirm', (event) => {
        const detail = event.detail || {};
        const id = Array.isArray(detail) ? detail[0]?.id : detail.id;
        const code = Array.isArray(detail) ? detail[0]?.code : detail.code;
        if (!id || !window.Swal) return;

        Swal.fire({
            title: 'Konfirmasi Check-in?',
            html: 'Tiket <strong style="font-family:monospace;">' + code + '</strong> akan ditandai sudah masuk event.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check"></i> Ya, Check-in',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0062ff',
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
