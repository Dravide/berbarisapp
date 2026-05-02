<div>
    @if($isAuthenticated)
        <!-- Banner Start -->
        <section class="bg-primary-subtle pt-7 py-lg-9 py-7">
        <div class="custom-container">
            <div class="row align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-4 mb-lg-0">
                    <span class="badge bg-primary text-white mb-3 fs-3 px-3 py-2 rounded-pill">Live Drawing</span>
                    <h1 class="text-dark fw-bolder fs-11 mb-3">Pengundian Urutan Tampil</h1>
                    <p class="fs-5 text-muted mb-0">Event: <strong>{{ $eventner->nama_event }}</strong></p>
                    <div class="mt-3">
                        <a href="{{ route('event.detail', $slug) }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm pb-2 pt-2">
                            <i class="ti ti-arrow-left me-1"></i> Kembali ke Event
                        </a>
                        <a href="{{ route('event.drawing.results', $slug) }}" class="btn btn-primary rounded-pill px-4 shadow-sm pb-2 pt-2 ms-2" target="_blank">
                            <i class="ti ti-table me-1"></i> Lihat Hasil Live
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid" style="max-height: 150px;" />
                </div>
            </div>
        </div>
    </section>
    <!-- Banner End -->

    <section class="py-5 py-md-8">
        <div class="custom-container">

            @if(session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="ti ti-check me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
        }
    </style>

    <!-- Tabs Kategori -->
    <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
        @foreach ($categories as $category)
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab == $category['id'] ? 'active bg-primary text-white' : '' }}" 
                    wire:click="switchTab('{{ $category['id'] }}')"
                    type="button" role="tab"
                >
                    <i class="ti ti-medal me-2"></i> {{ $category['name'] }}
                </button>
            </li>
        @endforeach
    </ul>

    <div class="row g-4">
        <!-- Kolom Kiri: Spinning Area -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="min-height: 480px;">
                {{-- Gradient Header --}}
                <div class="card-header bg-primary text-white p-4 border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="ti ti-arrows-shuffle me-2"></i> Zona Pengundian</h5>
                        <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill">
                            {{ $drawnSchools->count() }} / {{ $totalSchools }} Selesai
                        </span>
                    </div>
                </div>

                <div class="card-body p-5 text-center d-flex flex-column justify-content-center align-items-center">
                    @if($allDrawn)
                        {{-- Semua sudah di-spin --}}
                        <div class="py-4">
                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px;">
                                <i class="ti ti-checks fs-10"></i>
                            </div>
                            <h3 class="fw-bold text-success mb-2">Pengundian Selesai!</h3>
                            <p class="text-muted fs-4">Semua sekolah di kategori ini telah mendapatkan nomor urut tampil.</p>
                            <a href="{{ route('event.drawing.results', $slug) }}" class="btn btn-primary rounded-pill px-4 mt-2 shadow-sm">
                                <i class="ti ti-table me-2"></i> Lihat Hasil Lengkap
                            </a>
                        </div>
                    @elseif($currentSchool)
                        {{-- Sekolah yang akan spin --}}
                        <div class="mb-4">
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-3 mb-3">Giliran Mengundi</span>
                        </div>

                        {{-- Logo --}}
                        @if($currentSchool->logo_sekolah)
                            <img src="{{ asset('storage/' . $currentSchool->logo_sekolah) }}" class="rounded-3 border shadow-sm mb-3" style="width: 80px; height: 80px; object-fit: contain;" alt="Logo">
                        @else
                            <div class="bg-primary-subtle rounded-3 d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="ti ti-building-school fs-8 text-primary"></i>
                            </div>
                        @endif

                        <h2 class="fw-bolder mb-1">{{ $currentSchool->nama_sekolah }}</h2>
                        <p class="text-muted fs-4 mb-4">NPSN: {{ $currentSchool->npsn }}</p>

                        {{-- Spinning Area --}}
                        <div x-data="window.spinnerWidget()" class="w-100" style="max-width: 320px;">
                            {{-- Nomor Display --}}
                            <div class="position-relative mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 200px; height: 200px;">
                                <!-- Spinning outer ring -->
                                <div 
                                    class="position-absolute w-100 h-100 rounded-circle border border-5"
                                    :class="isSpinning ? 'border-warning spinning-ring' : (result ? 'border-success' : 'border-primary')"
                                    style="top: 0; left: 0;"
                                ></div>

                                <!-- Inner circle with text -->
                                <div 
                                    class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                    :class="result ? 'bg-success-subtle' : 'bg-primary-subtle'"
                                    style="width: 170px; height: 170px; transition: all 0.3s;"
                                >
                                    <template x-if="isSpinning">
                                        <span class="fw-bolder text-warning" style="font-size: 80px;" x-text="displayNumber"></span>
                                    </template>
                                    <template x-if="!isSpinning && result">
                                        <span class="fw-bolder text-success" style="font-size: 80px;" x-text="result"></span>
                                    </template>
                                    <template x-if="!isSpinning && !result">
                                        <span class="fw-bolder text-primary" style="font-size: 60px;">?</span>
                                    </template>
                                </div>
                            </div>

                            {{-- Buttons --}}
                            <div class="d-flex flex-column gap-2">
                                <template x-if="!result">
                                    <button 
                                        class="btn btn-warning btn-lg rounded-pill fw-bold shadow-md px-5 py-3 w-100"
                                        :disabled="isSpinning"
                                        @click="startSpin()"
                                    >
                                        <template x-if="isSpinning">
                                            <span><i class="ti ti-loader ti-spin me-2"></i> Mengundi...</span>
                                        </template>
                                        <template x-if="!isSpinning">
                                            <span><i class="ti ti-arrows-shuffle me-2"></i> SPIN!</span>
                                        </template>
                                    </button>
                                </template>

                                <template x-if="result && !isSpinning">
                                    <div class="d-flex flex-column gap-2">
                                        <div class="alert alert-success text-center py-3 rounded-4 border-0 mb-0 fs-4">
                                            <i class="ti ti-confetti me-2"></i> Nomor Urut: <strong x-text="'#' + result"></strong>
                                        </div>
                                        <button 
                                            class="btn btn-success btn-lg rounded-pill fw-bold shadow-md px-5 py-3 w-100"
                                            wire:click="saveResult"
                                        >
                                            <i class="ti ti-check me-2"></i> Simpan & Lanjut
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reset Button --}}
            @if($drawnSchools->count() > 0)
                <div class="text-center mt-3">
                    <button wire:click="resetDrawing" wire:confirm="PERINGATAN: Semua hasil undian pada kategori ini akan DIHAPUS. Yakin ingin reset?" class="btn btn-sm btn-outline-danger rounded-pill px-4">
                        <i class="ti ti-refresh me-1"></i> Reset Undian Kategori Ini
                    </button>
                </div>
            @endif
        </div>

        <!-- Kolom Kanan: Progress -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="ti ti-list-numbers me-2"></i> Urutan Sudah Ditentukan</h5>
                </div>
                <div class="card-body p-0">
                    @if($drawnSchools->count() > 0)
                        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light sticky-top">
                                    <tr>
                                        <th class="fw-semibold ps-4" style="width: 60px;">No</th>
                                        <th class="fw-semibold">Sekolah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($drawnSchools as $school)
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge bg-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">
                                                    {{ $school->urutan_tampil }}
                                                </span>
                                            </td>
                                            <td>
                                                <h6 class="fw-semibold mb-0">{{ $school->nama_sekolah }}</h6>
                                <small class="text-muted">NPSN: {{ $school->npsn }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 px-3">
                            <i class="ti ti-dice fs-10 text-muted"></i>
                            <p class="text-muted mt-2 mb-0 fs-3">Belum ada hasil undian. Klik <strong>SPIN</strong> untuk memulai!</p>
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
    
    </section>
    @else
        <div class="container d-flex align-items-center justify-content-center" style="min-height: 80vh;">
            <div class="card shadow-lg border-0 rounded-4" style="max-width: 450px; width: 100%;">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="ti ti-lock fs-9"></i>
                        </div>
                        <h3 class="fw-bold">Akses Terkunci</h3>
                        <p class="text-muted">Silakan masukkan kode akses untuk membuka halaman Pengundian Acara {{ $eventner->nama_event }}.</p>
                    </div>

                    <form wire:submit.prevent="verifyCode">
                        <div class="mb-4 text-start">
                            <label class="form-label fw-semibold">Kode Akses</label>
                            <input type="password" class="form-control form-control-lg text-center fs-5 rounded-3 border-2 @error('inputCode') is-invalid @enderror" wire:model="inputCode" placeholder="Masukkan PIN" autofocus>
                            @error('inputCode')
                                <div class="invalid-feedback fw-medium mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm">
                            <i class="ti ti-unlock me-2"></i> Buka Kunci
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
