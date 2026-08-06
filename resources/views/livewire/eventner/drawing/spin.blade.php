<div>
    @if($isAuthenticated)
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- Header --}}
            <div class="card bg-warning-subtle shadow-none position-relative overflow-hidden mb-4">
                <div class="card-body px-4 py-4 text-center">
                    <h2 class="fw-bold mb-2">
                        <i class="ti ti-arrows-shuffle text-warning me-2"></i>
                        Pengundian Urutan Tampil
                    </h2>
                    <p class="text-muted fs-3 mb-0">{{ $eventner->nama_event }}</p>
                </div>
            </div>

            {{-- Flash --}}
            @if(session()->has('success'))
                <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
                    <i class="ti ti-circle-check me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Info Bar --}}
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 py-2 fs-3">
                    <i class="ti ti-player-play me-1"></i> LIVE
                    <small class="ms-1 opacity-75">Pengundian langsung</small>
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3 py-2 fs-3">
                    {{ $drawnSchools->count() }} / {{ $totalSchools }} Selesai
                </span>
            </div>

            {{-- Category Select --}}
            @if(count($categories) > 1)
                <div class="mb-4">
                    <div class="input-group mx-auto" style="max-width: 400px;">
                        <span class="input-group-text bg-primary text-white"><i class="ti ti-category"></i></span>
                        <select class="form-select" wire:model.live="activeTab" wire:change="switchTab($event.target.value)">
                            @foreach($categories as $cat)
                                @php $label = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                                <option value="{{ $cat['id'] }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="row g-4">
                {{-- Left: Spinning Area --}}
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title fw-semibold mb-0 text-white">
                                <i class="ti ti-arrows-shuffle text-warning me-2"></i> Zona Pengundian
                            </h5>
                            <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-3 py-1 fs-3">
                                {{ $drawnSchools->count() }} / {{ $totalSchools }} Selesai
                            </span>
                        </div>
                        <div class="card-body text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 420px;">
                            @if($allDrawn)
                                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-500 mb-4" style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(16,185,129,0.1);color:#10b981;font-size:2.2rem;">
                                    <i class="ti ti-circle-check"></i>
                                </div>
                                <h4 class="fw-bold text-success mb-2">Pengundian Selesai!</h4>
                                <p class="text-muted mb-4">Semua sekolah telah mendapat nomor urut tampil.</p>
                                <a href="{{ event_url($eventner, 'drawing.results') }}" class="btn btn-primary px-4">
                                    <i class="ti ti-table me-1"></i> Lihat Hasil Lengkap
                                </a>
                            @elseif($currentSchool)
                                <span class="badge bg-primary-subtle text-primary border border-primary rounded-pill px-3 py-1 fs-3 mb-4">Giliran Mengundi</span>

                                @if($currentSchool->logo_sekolah)
                                    <img src="{{ asset('storage/' . $currentSchool->logo_sekolah) }}" class="rounded-2 mb-3" style="width:80px;height:80px;object-fit:cover;border:1px solid rgba(0,0,0,.1);padding:4px;background:#fff;">
                                @else
                                    <div class="rounded-2 bg-primary bg-opacity-25 d-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;">
                                        <i class="ti ti-school text-primary fs-1"></i>
                                    </div>
                                @endif

                                <h4 class="fw-bold mb-1">{{ $currentSchool->nama_sekolah }}</h4>
                                <p class="text-muted fs-3 mb-4">NPSN: {{ $currentSchool->npsn }}</p>

                                <div wire:key="spinner-{{ $currentSchool->id }}" x-data="window.spinnerWidget()" class="w-100" style="max-width: 320px;">
                                    <div class="mx-auto mb-4 position-relative" style="width:200px;height:200px;">
                                        <div class="rounded-circle position-absolute inset-0"
                                            :class="isSpinning ? 'animate-spin border-amber-400 border-4 border-dashed' : (result ? 'border-emerald-400 border-4 border-solid' : 'border-primary border-4 border-solid')"
                                            style="transition:all 0.3s; border-style: solid;"
                                            :style="'top:0;left:0;right:0;bottom:0;'">
                                        </div>
                                        <div class="rounded-circle position-absolute d-flex align-items-center justify-content-center"
                                            :style="'background:' + (result && !isSpinning ? 'rgba(16,185,129,0.08)' : 'rgba(0,98,255,0.06)') + ';top:8px;left:8px;right:8px;bottom:8px;'">
                                            <template x-if="isSpinning">
                                                <span class="font-display font-extrabold text-6xl text-amber-500" style="font-size:3.5rem;font-weight:800;color:#f59e0b;" x-text="displayNumber"></span>
                                            </template>
                                            <template x-if="!isSpinning && result">
                                                <span class="font-display font-extrabold text-6xl text-emerald-500" style="font-size:3.5rem;font-weight:800;color:#10b981;" x-text="result"></span>
                                            </template>
                                            <template x-if="!isSpinning && !result">
                                                <span class="font-display font-extrabold text-6xl text-primary" style="font-size:3.5rem;font-weight:800;color:#0d6efd;">?</span>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-2">
                                        <template x-if="!result">
                                            <button type="button"
                                                class="btn btn-primary w-100 py-3 fw-bold"
                                                :disabled="isSpinning"
                                                @click="startSpin()">
                                                <template x-if="isSpinning">
                                                    <span><span class="spinner-border spinner-border-sm me-2"></span>Mengundi...</span>
                                                </template>
                                                <template x-if="!isSpinning">
                                                    <span><i class="ti ti-arrows-shuffle me-2"></i> SPIN SEKARANG!</span>
                                                </template>
                                            </button>
                                        </template>
                                        <template x-if="result && !isSpinning">
                                            <div>
                                                <div class="border border-success-subtle bg-success-subtle text-success rounded-3 p-3 mb-3 text-center fw-bold">
                                                    <i class="ti ti-star me-1"></i> Nomor Urut: <strong class="fs-4" x-text="'#' + result"></strong>
                                                </div>
                                                <button type="button"
                                                    class="btn w-100 py-3 fw-bold text-white"
                                                    style="background:#10b981;"
                                                    wire:click="saveResult">
                                                    <i class="ti ti-check me-1"></i> Simpan &amp; Lanjut
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($drawnSchools->count() > 0)
                            <div class="card-footer text-center bg-white">
                                <button wire:click="resetDrawing" wire:confirm="Yakin ingin reset semua hasil undian di kategori ini?"
                                    class="btn btn-sm btn-outline-danger">
                                    <i class="ti ti-refresh me-1"></i> Reset Undian Kategori Ini
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: Progress --}}
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="card-title fw-semibold mb-0 text-white">
                                <i class="ti ti-list-ol text-warning me-2"></i> Urutan Sudah Ditentukan
                            </h5>
                        </div>
                        <div class="card-body p-0" style="max-height: 420px; overflow-y: auto;">
                            @forelse($drawnSchools as $school)
                                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary text-white fw-bold shrink-0" style="width:32px;height:32px;flex-shrink:0;">
                                        {{ $school->urutan_tampil }}
                                    </span>
                                    <div class="min-w-0">
                                        <h6 class="fw-bold mb-0 text-truncate">{{ $school->nama_sekolah }}</h6>
                                        <small class="text-muted">NPSN: {{ $school->npsn }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="ti ti-dice fs-8 text-muted d-block mb-3"></i>
                                    <p class="text-muted mb-1">Belum ada hasil undian.</p>
                                    <small class="text-muted">Klik <strong>SPIN SEKARANG</strong> untuk memulai!</small>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-3 mb-4">
                <a href="{{ event_url($eventner, 'detail') }}" class="btn btn-sm btn-outline-primary px-3 me-1">
                    <i class="ti ti-arrow-left me-1"></i> Detail Event
                </a>
                <a href="{{ event_url($eventner, 'drawing.results') }}" target="_blank" class="btn btn-sm btn-primary px-3">
                    <i class="ti ti-table me-1"></i> Hasil
                </a>
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

    @else
        {{-- Access Gate --}}
        <div class="container-fluid">
            <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body p-5 text-center">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-4" style="width:64px;height:64px;font-size:1.8rem;">
                                <i class="ti ti-lock"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Akses Terkunci</h4>
                            <p class="text-muted fs-3 mb-4">Masukkan kode akses untuk membuka Pengundian <strong>{{ $eventner->nama_event }}</strong>.</p>

                            <form wire:submit.prevent="verifyCode">
                                <div class="mb-4 text-start">
                                    <label class="form-label fw-semibold">Kode Akses</label>
                                    <input type="password" wire:model="inputCode"
                                        class="form-control text-center fs-3 font-monospace"
                                        style="letter-spacing: 0.4em;" placeholder="PIN" autofocus>
                                    @error('inputCode')
                                        <span class="text-danger fs-2 fw-semibold mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">
                                    <span wire:loading.remove><i class="ti ti-unlock me-1"></i> Buka Kunci</span>
                                    <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span> Memverifikasi...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
