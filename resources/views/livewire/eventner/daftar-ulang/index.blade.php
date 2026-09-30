<div>
    {{-- Page Header --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <h4 class="fw-semibold mb-8">Daftar Ulang</h4>
            <p class="mb-0 text-muted fs-2">
                Meja pendaftaran ulang: tandai kehadiran, tetapkan grup, seri penilaian, dan nomor undian
                dalam satu layar.
            </p>
        </div>
    </div>

    {{-- Saringan meja: tingkat, cari sekolah, sisa antrean --}}
    <div class="card w-100 mb-3">
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tingkat Lomba</label>
                    <select class="form-select" wire:model.live="activeTab">
                        @foreach($categories as $cat)
                            @php $tabLabel = !empty($cat['parent']) ? $cat['parent']['name'] . ' — ' . $cat['name'] : $cat['name']; @endphp
                            <option value="{{ $cat['id'] }}">{{ $tabLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Cari Sekolah</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" placeholder="Ketik nama sekolah…"
                            wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="text-muted fs-2">Belum hadir</div>
                        <div class="fs-5 fw-semibold {{ $this->belumHadir > 0 ? 'text-danger' : 'text-success' }}">
                            {{ $this->belumHadir }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Seri belum dibuat sama sekali: kolom Seri akan kosong semua, dan itu
         terbaca sebagai kerusakan. Katakan apa yang kurang. --}}
    @if($this->series->isEmpty())
        <div class="alert alert-warning py-2 fs-2">
            <i class="ti ti-alert-triangle me-1"></i>
            Tingkat ini belum punya seri. Buat dulu di halaman
            <a href="{{ route('eventner.competition-categories.index') }}" class="fw-semibold">Kategori Lomba</a>
            supaya lembar nilainya bisa ditentukan.
        </div>
    @endif

    <div class="card w-100">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-semibold mb-0 text-white">
                <i class="ti ti-clipboard-check me-2"></i> Peserta
            </h5>
            <span class="fs-2">{{ $this->participants->count() }} sekolah tampil</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Sekolah</th>
                            <th style="width: 90px;" class="text-center">Hadir</th>
                            <th style="width: 190px;">Grup</th>
                            <th style="width: 190px;">Seri</th>
                            <th style="width: 120px;">Undian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->participants as $peserta)
                            <tr class="{{ $peserta->daftar_ulang_at ? '' : 'table-warning' }}">
                                <td>
                                    <div class="fw-semibold">{{ $peserta->nama_sekolah }}</div>
                                    <div class="text-muted fs-2">
                                        @if($peserta->label_pasukan)
                                            Pasukan {{ $peserta->label_pasukan }}
                                        @endif
                                        @if($peserta->daftar_ulang_at)
                                            · daftar ulang {{ $peserta->daftar_ulang_at->format('H:i') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-sm {{ $peserta->daftar_ulang_at ? 'btn-success' : 'btn-outline-secondary' }}"
                                        wire:click="toggleHadir({{ $peserta->id }})"
                                        title="{{ $peserta->daftar_ulang_at ? 'Batalkan tanda hadir' : 'Tandai sudah daftar ulang' }}">
                                        <i class="ti ti-{{ $peserta->daftar_ulang_at ? 'check' : 'clock' }}"></i>
                                    </button>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm"
                                        wire:change="setGroup({{ $peserta->id }}, $event.target.value)">
                                        <option value="">― tanpa grup ―</option>
                                        @foreach($this->groups as $group)
                                            <option value="{{ $group->id }}"
                                                @selected((string) $peserta->competition_group_id === (string) $group->id)>
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm"
                                        wire:change="setSeries({{ $peserta->id }}, $event.target.value)">
                                        <option value="">― tanpa seri ―</option>
                                        @foreach($this->series as $seri)
                                            <option value="{{ $seri->id }}"
                                                @selected((string) $peserta->competition_series_id === (string) $seri->id)>
                                                {{ $seri->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm"
                                        value="{{ $peserta->urutan_tampil }}"
                                        wire:change="setUndian({{ $peserta->id }}, $event.target.value)"
                                        placeholder="—">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    @if(trim($search) !== '')
                                        Tidak ada sekolah yang cocok dengan "{{ $search }}".
                                    @else
                                        Belum ada peserta pada tingkat ini.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-muted fs-2 mb-0">
                Nomor undian hanya unik di dalam satu grup. Memindah grup mengosongkan nomor undiannya.
                Seri tidak bisa dipindah setelah ada nilai juri — hapus nilainya dulu di halaman Input Nilai.
            </p>
        </div>
    </div>
</div>
