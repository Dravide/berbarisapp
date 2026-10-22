<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Pengajuan EO</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Pengajuan EO</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan per status. Yang perlu dikerjakan adalah `baru`; angka lain
         hanya memberi tahu seberapa jauh tumpukannya. --}}
    <div class="row g-3 mb-4">
        @foreach(\App\Models\EoInquiry::STATUS as $kunci => $label)
            <div class="col-6 col-lg-3">
                <button type="button"
                        class="card w-100 text-start mb-0 border {{ $saring === $kunci ? 'border-primary' : '' }}"
                        wire:click="$set('saring', '{{ $kunci }}')"
                        style="cursor: pointer;">
                    <div class="card-body py-3">
                        <p class="text-muted fs-3 mb-1">{{ $label }}</p>
                        <p class="fs-6 fw-semibold mb-0">{{ $hitung[$kunci] ?? 0 }}</p>
                    </div>
                </button>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="card-title fw-semibold mb-1">
                                Pengajuan {{ $saring === 'semua' ? 'semua status' : strtolower(\App\Models\EoInquiry::STATUS[$saring] ?? $saring) }}
                            </h5>
                            <p class="text-muted fs-3 mb-0">Dikirim dari formulir <code>eo.{{ parse_url(config('app.url'), PHP_URL_HOST) }}/ajukan</code>.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="search" class="form-control form-control-sm" style="min-width: 200px;"
                                   placeholder="Cari instansi, nama, nomor…" wire:model.live.debounce.400ms="cari">
                            <select class="form-select form-select-sm" style="width: auto;" wire:model.live="saring">
                                <option value="semua">Semua</option>
                                @foreach(\App\Models\EoInquiry::STATUS as $kunci => $label)
                                    <option value="{{ $kunci }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal masuk</th>
                                    <th>Instansi</th>
                                    <th>Jenis lomba</th>
                                    <th>WhatsApp</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pengajuan as $item)
                                    <tr class="{{ $item->status === 'baru' ? 'fw-semibold' : '' }}">
                                        <td class="text-nowrap">
                                            <span class="d-block">{{ $item->created_at?->translatedFormat('d M Y') }}</span>
                                            <span class="text-muted fs-3">{{ $item->created_at?->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="d-block">{{ $item->instansi }}</span>
                                            <span class="text-muted fs-3">{{ $item->penanggung_jawab }}</span>
                                        </td>
                                        <td>{{ $item->jenis_lomba }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ wa_me_url($item->whatsapp) }}" target="_blank" rel="noopener">{{ $item->whatsapp }}</a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $item->status === 'baru' ? 'warning' : ($item->status === 'batal' ? 'secondary' : 'success') }}-subtle text-{{ $item->status === 'baru' ? 'warning' : ($item->status === 'batal' ? 'secondary' : 'success') }}">
                                                {{ \App\Models\EoInquiry::STATUS[$item->status] ?? $item->status }}
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button class="btn btn-sm btn-outline-primary me-1" wire:click="lihat({{ $item->id }})" title="Lihat detail">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                            @if($item->status === 'baru')
                                                <button class="btn btn-sm btn-outline-success" wire:click="ubahStatus({{ $item->id }}, 'dibalas')" title="Tandai sudah dibalas">
                                                    <i class="ti ti-check"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            @if(trim($cari) !== '')
                                                Tidak ada pengajuan yang cocok dengan "{{ $cari }}".
                                            @else
                                                Belum ada pengajuan pada status ini.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $pengajuan->links() }}</div>
                </div>
            </div>

            {{-- Detail pengajuan. Ditampilkan sebagai panel di kolom yang sama,
                 bukan modal: isinya panjang dan admin perlu bisa menggulir. --}}
            @if($detail)
                <div class="card mb-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title fw-semibold mb-1">{{ $detail->instansi }}</h5>
                                <p class="text-muted fs-3 mb-0">
                                    Diajukan {{ $detail->created_at?->translatedFormat('d M Y H:i') }}
                                    @if($detail->dibalas_at)
                                        · dibalas {{ $detail->dibalas_at->translatedFormat('d M Y H:i') }}
                                    @endif
                                </p>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" wire:click="tutup"><i class="ti ti-x"></i></button>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <p class="text-muted fs-3 mb-1">Penanggung jawab</p>
                                <p class="mb-0">{{ $detail->penanggung_jawab }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted fs-3 mb-1">WhatsApp</p>
                                <p class="mb-0"><a href="{{ wa_me_url($detail->whatsapp) }}" target="_blank" rel="noopener">{{ $detail->whatsapp }}</a></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted fs-3 mb-1">Surel</p>
                                <p class="mb-0">{{ $detail->email ?: '—' }}</p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-muted fs-3 mb-1">Jenis lomba</p>
                                <p class="mb-0">{{ $detail->jenis_lomba }}</p>
                            </div>
                            <div class="col-sm-4">
                                <p class="text-muted fs-3 mb-1">Perkiraan tanggal</p>
                                <p class="mb-0">{{ $detail->tanggal?->translatedFormat('d M Y') ?? 'belum ditentukan' }}</p>
                            </div>
                            <div class="col-sm-4">
                                <p class="text-muted fs-3 mb-1">Lokasi</p>
                                <p class="mb-0">{{ $detail->lokasi ?: '—' }}</p>
                            </div>
                            <div class="col-sm-4">
                                <p class="text-muted fs-3 mb-1">Perkiraan peserta</p>
                                <p class="mb-0">{{ $detail->jumlah_peserta ? number_format($detail->jumlah_peserta, 0, ',', '.') : '—' }}</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <p class="text-muted fs-3 mb-1">Pekerjaan yang diminta</p>
                            @if(!empty($detail->pekerjaan))
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($detail->pekerjaan as $p)
                                        <span class="badge bg-primary-subtle text-primary">{{ $p }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="mb-0 text-muted">Tidak dicentang — perlu ditanyakan saat membalas.</p>
                            @endif
                        </div>

                        @if($detail->catatan)
                            <div class="mb-3">
                                <p class="text-muted fs-3 mb-1">Catatan pengaju</p>
                                <p class="mb-0" style="white-space: pre-line;">{{ $detail->catatan }}</p>
                            </div>
                        @endif

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Catatan admin</label>
                            <textarea class="form-control @error('catatan_admin') is-invalid @enderror" rows="3"
                                      wire:model="catatan_admin"
                                      placeholder="Mis. sudah dihubungi 23 Okt, menunggu keputusan kepala sekolah."></textarea>
                            @error('catatan_admin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <button class="btn btn-sm btn-primary mt-2" wire:click="simpanCatatan">
                                <i class="ti ti-device-floppy me-1"></i> Simpan catatan
                            </button>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="text-muted fs-3">Ubah status:</span>
                            @foreach(\App\Models\EoInquiry::STATUS as $kunci => $label)
                                <button class="btn btn-sm {{ $detail->status === $kunci ? 'btn-primary' : 'btn-outline-primary' }}"
                                        wire:click="ubahStatus({{ $detail->id }}, '{{ $kunci }}')">
                                    {{ $label }}
                                </button>
                            @endforeach
                            <button class="btn btn-sm btn-outline-danger ms-auto"
                                    wire:click="hapus({{ $detail->id }})"
                                    wire:confirm="Hapus pengajuan dari {{ $detail->instansi }}? Tindakan ini tidak bisa dibatalkan.">
                                <i class="ti ti-trash me-1"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- Setelan halaman EO. Ditaruh di sini karena yang mengubahnya
                 adalah orang yang menjawab pengajuan — ia yang tahu nomor mana
                 yang benar. Kunci setelannya `eo_*`, BUKAN `landing_*`. --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-1">Setelan halaman EO</h5>
                    <p class="text-muted fs-3 mb-3">
                        Dipakai di semua halaman <code>eo</code> — nama tim di navbar, dan kontak di kaki halaman serta formulir pengajuan.
                    </p>

                    <form wire:submit="simpanKontak">
                        <div class="mb-3">
                            <label class="form-label">Nama tim EO</label>
                            <input type="text" class="form-control @error('eo_nama') is-invalid @enderror" wire:model="eo_nama" placeholder="Kosongkan untuk memakai nama platform">
                            @error('eo_nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Nama contoh tidak pernah dipakai sebagai cadangan — kosong berarti nama platform.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Surel</label>
                            <input type="email" class="form-control @error('eo_email') is-invalid @enderror" wire:model="eo_email" placeholder="eo@contoh.id">
                            @error('eo_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" class="form-control @error('eo_phone') is-invalid @enderror" wire:model="eo_phone" placeholder="08xxxxxxxxxx">
                            @error('eo_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Instagram</label>
                            <input type="url" class="form-control @error('eo_instagram') is-invalid @enderror" wire:model="eo_instagram" placeholder="https://instagram.com/akun">
                            @error('eo_instagram') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled" wire:target="simpanKontak">
                            <span wire:loading.remove wire:target="simpanKontak"><i class="ti ti-device-floppy me-1"></i> Simpan setelan</span>
                            <span wire:loading wire:target="simpanKontak"><span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Angka waktu alur. Disunting di sini, bukan di kode: janji "H-7
                 kunci produksi" harus disepakati tim operasional lebih dulu. --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-1">Perkiraan waktu alur</h5>
                    <p class="text-muted fs-3 mb-3">
                        Tampil di halaman <code>/alur</code>. Selama kosong, halaman menulis "menunggu kesepakatan" — bukan angka karangan.
                    </p>
                    @php
                        $waktuEo = json_decode(get_setting('eo_alur_waktu') ?: '{}', true) ?: [];
                    @endphp
                    <ul class="list-unstyled mb-0">
                        @foreach(['pengajuan' => 'Balasan pengajuan', 'kunci_produksi' => 'Kunci produksi', 'pelaksanaan' => 'Pelaksanaan'] as $kunci => $label)
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted fs-3">{{ $label }}</span>
                                <span>{{ $waktuEo[$kunci] ?? 'menunggu kesepakatan' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="form-text mt-2 mb-0">Diisi lewat <code>php artisan tinker</code> atau halaman setelan setelah angkanya disepakati.</p>
                </div>
            </div>
        </div>
    </div>
</div>
