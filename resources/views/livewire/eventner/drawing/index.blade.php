<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Hasil Pengundian Urutan Tampil</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Drawing</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success border-0 bg-success-subtle text-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Category Tabs --}}
    @if(count($categories) > 1)
    <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
        @foreach($categories as $cat)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab == $cat['id'] ? 'active bg-primary text-white' : '' }}"
                    wire:click="switchTab({{ $cat['id'] }})"
                    type="button" role="tab">
                    <i class="ti ti-layers-intersect me-1"></i> {{ $cat['name'] }}
                </button>
            </li>
        @endforeach
    </ul>
    @endif

    <div class="row">
        {{-- Results Table --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-list-numbers me-2"></i> Hasil Undian
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('event.drawing.spin', $eventner->slug) }}" target="_blank" class="btn btn-sm btn-primary">
                            <i class="ti ti-arrows-shuffle me-1"></i> Layar Spin
                        </a>
                        <button class="btn btn-sm btn-outline-danger" wire:click="resetDrawing" onclick="return confirm('Yakin reset semua hasil undian kategori ini?')">
                            <i class="ti ti-refresh me-1"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if($drawnResults->count() > 0)
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-bottom-0 ps-4" width="70px">
                                            <h6 class="fw-semibold mb-0">Urutan</h6>
                                        </th>
                                        <th class="border-bottom-0">
                                            <h6 class="fw-semibold mb-0">Sekolah</h6>
                                        </th>
                                        <th class="border-bottom-0" width="100px">
                                            <h6 class="fw-semibold mb-0">NPSN</h6>
                                        </th>
                                        <th class="border-bottom-0 text-center" width="80px">
                                            <h6 class="fw-semibold mb-0">Aksi</h6>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($drawnResults as $reg)
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge bg-primary px-3 py-2 fs-3">{{ $reg->urutan_tampil }}</span>
                                            </td>
                                            <td>
                                                <h6 class="fw-semibold mb-0">{{ $reg->nama_sekolah }}</h6>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $reg->npsn }}</span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-danger p-1" wire:click="removeDrawing({{ $reg->id }})" title="Hapus urutan">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-5">
                                <i class="ti ti-arrows-shuffle fs-10 text-muted d-block mb-3"></i>
                                <h5 class="fw-semibold text-muted">Belum Ada Hasil Undian</h5>
                                <p class="text-muted">Lakukan pengundian melalui Layar Spin atau input manual.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Manual Input --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-edit me-2"></i> Input Manual
                    </h5>
                </div>
                <div class="card-body">
                    @if($undrawnParticipants->count() > 0)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Peserta</label>
                            <div wire:key="select-parent-{{ $undrawnParticipants->count() }}" 
                                x-data="{
                                    model: @entangle('manualRegistrationId'),
                                    init() {
                                        const setup = () => {
                                            if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                                                setTimeout(setup, 50);
                                                return;
                                            }
                                            const el = $(this.$refs.select);
                                            el.select2({
                                                placeholder: '-- Cari Peserta --',
                                                allowClear: true,
                                                width: '100%'
                                            });
                                            el.on('change', (e) => {
                                                this.model = e.target.value || null;
                                            });
                                            this.$watch('model', (value) => {
                                                el.val(value).trigger('change.select2');
                                            });
                                        };
                                        setup();
                                    }
                                }" wire:ignore>
                                <select x-ref="select" class="form-select form-select-sm" id="select2-participant">
                                    <option value="">-- Cari Peserta --</option>
                                    @foreach($undrawnParticipants as $p)
                                        <option value="{{ $p->id }}" {{ (string) $manualRegistrationId === (string) $p->id ? 'selected' : '' }}>{{ $p->nama_sekolah }} ({{ $p->npsn }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('manualRegistrationId')
                                <span class="text-danger fs-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor Urut</label>
                            <input type="number" class="form-control form-control-sm" wire:model="manualUrutan" min="1" placeholder="Contoh: 1">
                            @error('manualUrutan')
                                <span class="text-danger fs-2">{{ $message }}</span>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100 btn-sm" wire:click="assignManual">
                            <i class="ti ti-check me-1"></i> Simpan
                        </button>
                    @else
                        <div class="text-center py-3">
                            <i class="ti ti-checks fs-8 text-success d-block mb-2"></i>
                            <p class="text-muted mb-0">Semua peserta sudah mendapat urutan.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title fw-semibold mb-0">
                        <i class="ti ti-external-link me-2"></i> Tautan Cepat
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('event.drawing.spin', $eventner->slug) }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center bg-primary-subtle">
                        <i class="ti ti-arrows-shuffle fs-5 me-2 text-primary"></i>
                        <span class="fw-bold">Layar Pengundian (Spin)</span>
                    </a>
                    <a href="{{ route('event.drawing.results', $eventner->slug) }}" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="ti ti-list-numbers fs-5 me-2"></i> Lihat Hasil Undian (Publik)
                    </a>
                    <a href="{{ route('eventner.participants.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="ti ti-users fs-5 me-2"></i> Kelola Peserta
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <link rel="stylesheet" href="{{ asset('templates/assets/libs/select2/dist/css/select2.min.css') }}">
    <script src="{{ asset('templates/assets/libs/select2/dist/js/select2.full.min.js') }}"></script>
    @endpush
</div>
