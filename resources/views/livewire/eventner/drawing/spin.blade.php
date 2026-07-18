<div>
    @if($isAuthenticated)
    {{-- ========== HERO ========== --}}
    <div class="min-h-screen bg-surface">
        <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#0053da] to-tertiary text-white py-10 md:py-14">
            <div class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>

            <div class="container-landing relative z-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white backdrop-blur-md border border-white/10 mb-3">
                            <i class="ti ti-arrows-shuffle"></i>
                            Live Drawing
                        </span>
                        <h1 class="font-display text-xl font-extrabold tracking-tight sm:text-2xl leading-tight">
                            Pengundian Urutan Tampil
                        </h1>
                        <p class="mt-1.5 text-xs font-medium text-white/80 md:text-sm">
                            Event: <strong class="text-secondary">{{ $eventner->nama_event }}</strong>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('event.detail', $slug) }}" class="btn-ghost !border-white/20 !text-white hover:!bg-white/10 text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                            <i class="ti ti-arrow-left"></i> Kembali
                        </a>
                        <a href="{{ route('event.drawing.results', $slug) }}" target="_blank" class="btn-primary text-xs py-2 px-4 leading-normal inline-flex items-center gap-1.5 text-decoration-none">
                            <i class="ti ti-table"></i> Hasil
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-landing py-6">
            @if(session()->has('success'))
                <div class="mb-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-sm font-semibold flex items-center gap-2">
                    <i class="ti ti-circle-check text-lg"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- Category Select --}}
            @if(count($categories) > 0)
            <div class="mb-6">
                <div class="mx-auto" style="max-width: 420px;">
                    <select wire:model.live="activeTab" wire:change="switchTab($event.target.value)"
                        class="w-full appearance-none bg-white border border-outline-variant/40 rounded-xl px-5 py-3.5 text-sm font-bold text-deep-slate shadow-sm cursor-pointer
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition">
                        @foreach($categories as $cat)
                            @php $label = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                            <option value="{{ $cat['id'] }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-12">
                {{-- Left: Spinning Area --}}
                <div class="lg:col-span-7">
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40 flex items-center justify-between">
                            <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 mb-0">
                                <i class="ti ti-arrows-shuffle text-primary"></i> Zona Pengundian
                            </h3>
                            <span class="chip py-0.5 px-3 text-xs font-bold">{{ $drawnSchools->count() }} / {{ $totalSchools }} Selesai</span>
                        </div>

                        <div class="p-8 text-center flex flex-col items-center justify-center min-h-[400px]">
                            @if($allDrawn)
                                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-500 mb-6">
                                    <i class="ti ti-circle-check text-4xl"></i>
                                </div>
                                <h3 class="font-display text-lg font-bold text-emerald-600 mb-2">Pengundian Selesai!</h3>
                                <p class="text-sm text-on-surface-variant mb-6">Semua sekolah telah mendapat nomor urut tampil.</p>
                                <a href="{{ route('event.drawing.results', $slug) }}" class="btn-primary py-3 px-6 font-bold text-sm text-decoration-none">
                                    <i class="ti ti-table me-1"></i> Lihat Hasil Lengkap
                                </a>
                            @elseif($currentSchool)
                                <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-4 py-1.5 text-xs font-bold text-primary border border-primary/20 mb-6">Giliran Mengundi</span>

                                @if($currentSchool->logo_sekolah)
                                    <img src="{{ asset('storage/' . $currentSchool->logo_sekolah) }}" class="h-20 w-20 rounded-2xl border border-outline-variant/40 p-1 object-cover mb-4">
                                @else
                                    <div class="h-20 w-20 rounded-2xl bg-surface-container flex items-center justify-center mb-4">
                                        <i class="ti ti-school text-3xl text-on-surface-variant"></i>
                                    </div>
                                @endif

                                <h2 class="font-display text-xl font-bold text-deep-slate mb-1">{{ $currentSchool->nama_sekolah }}</h2>
                                <p class="text-xs text-on-surface-variant mb-8">NPSN: {{ $currentSchool->npsn }}</p>

                                <div wire:key="spinner-{{ $currentSchool->id }}" x-data="window.spinnerWidget()" class="w-full max-w-xs mx-auto">
                                    <div class="relative mx-auto mb-8" style="width:200px;height:200px;">
                                        <div class="rounded-full absolute inset-0"
                                            :class="isSpinning ? 'animate-spin border-amber-400 border-4 border-dashed' : (result ? 'border-emerald-400 border-4 border-solid' : 'border-primary border-4 border-solid')"
                                            style="transition:all 0.3s;">
                                        </div>
                                        <div class="rounded-full absolute inset-2 flex items-center justify-center"
                                            :style="'background:' + (result && !isSpinning ? 'rgba(16,185,129,0.08)' : 'rgba(0,98,255,0.06)') + ';'">
                                            <template x-if="isSpinning">
                                                <span class="font-display font-extrabold text-6xl text-amber-500" x-text="displayNumber"></span>
                                            </template>
                                            <template x-if="!isSpinning && result">
                                                <span class="font-display font-extrabold text-6xl text-emerald-500" x-text="result"></span>
                                            </template>
                                            <template x-if="!isSpinning && !result">
                                                <span class="font-display font-extrabold text-6xl text-primary">?</span>
                                            </template>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-3">
                                        <template x-if="!result">
                                            <button type="button"
                                                class="btn-primary w-full py-4 text-base font-bold border-none"
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
                                                <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 mb-3 text-center text-sm font-bold text-emerald-600">
                                                    <i class="ti ti-star me-1"></i> Nomor Urut: <strong class="text-lg" x-text="'#' + result"></strong>
                                                </div>
                                                <button type="button"
                                                    class="btn-secondary w-full py-3.5 font-bold text-sm border-none"
                                                    style="background:#10b981; color:#fff;"
                                                    wire:click="saveResult">
                                                    <i class="ti ti-check me-1"></i> Simpan &amp; Lanjut
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
                            <button wire:click="resetDrawing" wire:confirm="Yakin ingin reset semua hasil undian di kategori ini?"
                                class="bg-transparent border border-error/30 text-error py-2 px-4 rounded-full text-xs font-bold hover:bg-error/5 transition cursor-pointer">
                                <i class="ti ti-refresh me-1"></i> Reset Undian Kategori Ini
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Right: Progress --}}
                <div class="lg:col-span-5">
                    <div class="surface-card overflow-hidden">
                        <div class="bg-surface-container px-5 py-4 border-b border-outline-variant/40">
                            <h3 class="font-display text-sm font-bold text-deep-slate inline-flex items-center gap-2 mb-0">
                                <i class="ti ti-list-ol text-primary"></i> Urutan Sudah Ditentukan
                            </h3>
                        </div>
                        <div class="max-h-[420px] overflow-y-auto divide-y divide-outline-variant/30">
                            @forelse($drawnSchools as $school)
                                <div class="flex items-center gap-4 px-5 py-4 hover:bg-surface-container-lowest transition">
                                    <span class="flex items-center justify-center h-8 w-8 rounded-full bg-primary text-white text-sm font-bold shrink-0">
                                        {{ $school->urutan_tampil }}
                                    </span>
                                    <div class="min-w-0">
                                        <h6 class="text-sm font-bold text-deep-slate leading-tight mb-0.5 truncate">{{ $school->nama_sekolah }}</h6>
                                        <span class="text-xs text-on-surface-variant">NPSN: {{ $school->npsn }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="py-16 text-center">
                                    <i class="ti ti-dice text-4xl text-outline-variant block mb-3"></i>
                                    <p class="text-sm text-on-surface-variant">Belum ada hasil undian.</p>
                                    <p class="text-xs text-on-surface-variant/70">Klik <strong>SPIN SEKARANG</strong> untuk memulai!</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
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

    @else
        {{-- Access Gate --}}
        <div class="min-h-screen bg-gradient-to-br from-primary via-[#0053da] to-tertiary flex items-center justify-center p-6">
            <div class="surface-card w-full max-w-md p-8 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary mx-auto mb-6">
                    <i class="ti ti-lock text-3xl"></i>
                </div>
                <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Akses Terkunci</h3>
                <p class="text-sm text-on-surface-variant mb-8">Masukkan kode akses untuk membuka Pengundian <strong>{{ $eventner->nama_event }}</strong>.</p>

                <form wire:submit.prevent="verifyCode">
                    <div class="mb-6 text-start">
                        <label class="text-sm font-bold text-deep-slate block mb-2">Kode Akses</label>
                        <input type="password" wire:model="inputCode"
                            class="field-input w-full text-center text-lg font-mono tracking-widest"
                            placeholder="PIN" autofocus>
                        @error('inputCode')
                            <span class="text-error text-xs font-semibold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="btn-primary w-full py-4 font-bold text-sm border-none">
                        <span wire:loading.remove><i class="ti ti-unlock me-1"></i> Buka Kunci</span>
                        <span wire:loading><span class="spinner-border spinner-border-sm me-2"></span> Memverifikasi...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
