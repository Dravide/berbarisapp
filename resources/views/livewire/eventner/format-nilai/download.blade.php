<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Unduh Format Penilaian</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Format Penilaian</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card w-100">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0 text-white fw-semibold">Atur Lembar Format Penilaian</h5>
        </div>
        <div class="card-body p-4">

            <div class="row g-3 mb-4">
                {{-- Juri --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Juri</label>
                    <select class="form-select" wire:model.live="selectedJudgeId">
                        <option value="">— Semua Juri —</option>
                        @foreach($this->judges as $judge)
                            <option value="{{ $judge->id }}">{{ $judge->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tingkat Lomba --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tingkat Lomba</label>
                    <select class="form-select" wire:model.live="selectedLevelId">
                        <option value="">— Semua Tingkat —</option>
                        @foreach($this->levels as $level)
                            <option value="{{ $level->id }}">{{ $level->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Mode --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jenis Lembar</label>
                    <select class="form-select" wire:model.live="mode">
                        <option value="kosong">Format Kosong (tanpa nama peserta)</option>
                        <option value="peserta">Per Peserta (No. Urut + Nama + Rubrik)</option>
                        <option value="daftar">Daftar Peserta (No. Urut + Nama saja)</option>
                    </select>
                </div>
            </div>

            @if($mode === 'peserta')
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pilih Peserta</label>
                        <select class="form-select" wire:model.live="selectedRegistrationId">
                            <option value="">— Pilih Peserta —</option>
                            @foreach($this->registrations as $reg)
                                <option value="{{ $reg->id }}">
                                    {{ $reg->urutan_tampil ? '#'.$reg->urutan_tampil.' — ' : '' }}{{ $reg->display_name }} ({{ $reg->competitionCategory->full_name ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <hr>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($mode === 'peserta')
                    <a href="{{ $this->pdfUrl() }}" target="_blank"
                       class="btn btn-danger {{ $selectedRegistrationId ? '' : 'disabled' }}">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh Lembar {{ $this->registrations->firstWhere('id', $selectedRegistrationId)?->display_name ?? 'Peserta' }}
                    </a>
                @else
                    <a href="{{ $this->pdfUrl() }}" target="_blank" class="btn btn-danger">
                        <i class="ti ti-file-type-pdf me-1"></i> Unduh PDF
                    </a>
                @endif
                <button class="btn btn-outline-secondary" wire:click="$set('mode', 'kosong')" {{ $mode === 'kosong' ? 'disabled' : '' }}>
                    Reset
                </button>
            </div>

            @if($this->categories->isEmpty())
                <p class="text-muted fs-3 mb-0 mt-3"><i>Tidak ada format penilaian yang cocok dengan filter.</i></p>
            @endif

        </div>
    </div>
</div>
