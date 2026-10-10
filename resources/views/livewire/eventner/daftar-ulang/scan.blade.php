<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-semibold mb-8">Scan Daftar Ulang</h4>
                <p class="mb-0 text-muted fs-2">
                    Arahkan kamera ke QR kartu peserta — tanpa mencari satu per satu di tabel.
                </p>
            </div>
            <a href="{{ route('eventner.daftar-ulang.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Tabel Daftar Ulang
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Kamera + input manual --}}
        <div class="col-lg-6">
            <div class="card w-100 mb-3">
                <div class="card-body">
                    <label class="form-label fw-semibold">Kamera</label>
                    <div id="qr-reader" class="border rounded p-2 bg-light"></div>
                    <div class="input-group mt-3">
                        <span class="input-group-text"><i class="ti ti-keyboard"></i></span>
                        <input type="text" class="form-control" placeholder="Atau ketik kode di kartu (contoh: K7B2M9XQ)…"
                            wire:model="manualCode"
                            wire:keydown.enter="lookup()">
                        <button class="btn btn-primary" type="button" wire:click="lookup()">Cari</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kartu hasil --}}
        <div class="col-lg-6">
            <div class="card w-100">
                <div class="card-body">
                    <label class="form-label fw-semibold">Hasil</label>

                    @if(!$result)
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-qrcode fs-1 d-block mb-2 opacity-50"></i>
                            <span class="fs-2">Hasil scan akan tampil di sini.</span>
                        </div>
                    @else
                        @if($result['kind'] === 'ready' || $result['kind'] === 'success' || $result['kind'] === 'already')
                            @php $reg = $result['registration']; @endphp
                            <div class="border rounded p-3 {{ $result['kind'] === 'already' ? 'border-warning-subtle' : 'border-success-subtle' }}">
                                <div class="d-flex align-items-start gap-3">
                                    @if($reg->logo_sekolah)
                                        <img src="{{ asset('storage/' . $reg->logo_sekolah) }}" class="rounded border" width="56" height="56" style="object-fit: cover;" alt="">
                                    @else
                                        <div class="rounded border bg-light d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                            <i class="ti ti-school fs-3 text-muted"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold">{{ $reg->nama_sekolah }}{{ $reg->label_pasukan ? ' — ' . $reg->label_pasukan : '' }}</div>
                                        <div class="text-muted fs-2">
                                            {{ $reg->competitionCategory?->parent?->name }}{{ $reg->competitionCategory?->parent ? ' — ' : '' }}{{ $reg->competitionCategory?->name }}
                                        </div>
                                        <div class="fs-2 text-muted">Pelatih: {{ $reg->nama_pelatih ?: '—' }}</div>
                                    </div>
                                </div>

                                @if($result['kind'] === 'ready')
                                    <div class="alert alert-success py-2 fs-2 mt-3 mb-3">
                                        <i class="ti ti-user-check me-1"></i> Belum terdaftar ulang — siap ditandai hadir.
                                    </div>
                                    <button class="btn btn-success w-100" wire:click="tandaiHadir({{ $reg->id }})">
                                        <i class="ti ti-check me-1"></i> Tandai Hadir
                                    </button>
                                @elseif($result['kind'] === 'success')
                                    <div class="alert alert-success py-2 fs-2 mt-3 mb-3">
                                        <i class="ti ti-circle-check me-1"></i> Hadir sejak {{ $reg->daftar_ulang_at?->translatedFormat('H:i') }}.
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm w-100" wire:click="batalkan({{ $reg->id }})"
                                        wire:confirm="Batalkan kehadiran {{ $reg->nama_sekolah }}?">
                                        <i class="ti ti-rotate me-1"></i> Batalkan Kehadiran
                                    </button>
                                @else
                                    <div class="alert alert-warning py-2 fs-2 mt-3 mb-3">
                                        <i class="ti ti-info-circle me-1"></i> Sudah hadir sejak {{ $reg->daftar_ulang_at?->translatedFormat('H:i') }}.
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm w-100" wire:click="batalkan({{ $reg->id }})"
                                        wire:confirm="Batalkan kehadiran {{ $reg->nama_sekolah }}?">
                                        <i class="ti ti-rotate me-1"></i> Batalkan Kehadiran
                                    </button>
                                @endif
                            </div>
                        @elseif($result['kind'] === 'wrong_event')
                            <div class="alert alert-danger py-3 mb-0">
                                <i class="ti ti-ban me-1"></i> <strong>Kartu milik event lain.</strong><br>
                                <span class="fs-2">Kode <code>{{ $result['code'] }}</code> sah, tetapi bukan peserta event ini. Peserta diarahkan ke meja yang benar.</span>
                            </div>
                        @else
                            <div class="alert alert-danger py-3 mb-0">
                                <i class="ti ti-qrcode-off me-1"></i> <strong>Kode tak dikenal.</strong><br>
                                <span class="fs-2">Kode <code>{{ $result['code'] }}</code> tidak terdaftar. Coba input manual dari kartu.</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- html5-qrcode — pola scan checkin tiket --}}
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
                component.call('lookup', text).finally(() => { busy = false; });
            } else {
                busy = false;
            }
        }

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                targetEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-camera-off fs-3 d-block"></i>Kamera tidak tersedia. Gunakan input manual.</div>';
                return;
            }
            const cameraId = cameras.find(c => /back|rear|environment/i.test(c.label))?.id || cameras[0].id;

            scanner.start(
                cameraId,
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decodedText) => dispatchScan(decodedText.trim()),
                () => { /* abaikan error frame */ }
            ).catch(() => {
                targetEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-camera-off fs-3 d-block"></i>Tidak bisa mengakses kamera. Gunakan input manual.</div>';
            });
        }).catch(() => {
            targetEl.innerHTML = '<div class="text-center text-muted py-4"><i class="ti ti-camera-off fs-3 d-block"></i>Tidak bisa membaca kamera. Gunakan input manual.</div>';
        });

        window.addEventListener('beforeunload', () => {
            if (scanner && scanner.isScanning) scanner.stop().catch(() => {});
        });
    }
});
</script>
