<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Eventner</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">Manajemen Eventner</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3">
                    <div class="text-center mb-n5">
                        <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt=""
                            class="img-fluid mb-n4" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menampilkan pesan sukses -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title fw-semibold">Data Eventner</h5>
                <button type="button" class="btn btn-primary" wire:click="resetForm" data-bs-toggle="modal"
                    data-bs-target="#eventnerModal">
                    <i class="ti ti-plus me-1"></i> Tambah Eventner
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle text-nowrap mb-0">
                    <thead>
                        <tr class="text-muted fw-semibold">
                            <th scope="col" class="ps-0">Nama Event</th>
                            <th scope="col">Diselenggarakan Oleh</th>
                            <th scope="col">Lokasi / Venue</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Akun Pengguna</th>
                            <th scope="col" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top">
                        @forelse($eventners as $eventner)
                            <tr>
                                <td class="ps-0">
                                    <h6 class="fw-semibold mb-1">{{ $eventner->nama_event }}</h6>
                                    @if($eventner->tingkat_perlombaan)
                                        <span
                                            class="badge bg-secondary-subtle text-secondary fs-2 mb-1">{{ $eventner->tingkat_perlombaan }}</span>
                                    @endif
                                    @if($eventner->tanggal_pendaftaran)
                                        <div class="fs-2 text-muted"><i class="ti ti-calendar me-1"></i>Daftar:
                                            {{ $eventner->tanggal_pendaftaran }}</div>
                                    @endif
                                </td>
                                <td>
                                    <p class="mb-0 fs-3">{{ $eventner->diselenggarakan_oleh }}</p>
                                </td>
                                <td>
                                    <span class="d-block">{{ $eventner->lokasi }}</span>
                                    <span class="text-muted fs-2">{{ $eventner->venue ?? '-' }}</span>
                                </td>
                                <td>
                                    {{ \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y') }}
                                </td>
                                <td>
                                    <span
                                        class="badge bg-primary-subtle text-primary">{{ $eventner->user->username }}</span>
                                    <span class="d-block fs-2">{{ $eventner->user->email }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.eventner.show', $eventner->id) }}"
                                        class="btn btn-sm btn-primary me-1">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-info me-1"
                                        wire:click="edit({{ $eventner->id }})">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                        wire:click="delete({{ $eventner->id }})"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus Eventner dan Akun Penggunanya?') || event.stopImmediatePropagation()">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data eventner.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form Eventner -->
    <div wire:ignore.self class="modal fade" id="eventnerModal" tabindex="-1" aria-labelledby="eventnerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold" id="eventnerModalLabel">{{ $isEditMode ? 'Edit' : 'Tambah' }}
                        Eventner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit="save">
                        <div class="row">
                            <h6 class="fw-semibold mb-3">Informasi Event</h6>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="nama_event">Nama Event <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_event') is-invalid @enderror"
                                    id="nama_event" wire:model="nama_event" placeholder="Masukkan nama event">
                                @error('nama_event') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="diselenggarakan_oleh">Diselenggarakan Oleh <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('diselenggarakan_oleh') is-invalid @enderror"
                                    id="diselenggarakan_oleh" wire:model="diselenggarakan_oleh"
                                    placeholder="Nama organisasi/kepanitiaan">
                                @error('diselenggarakan_oleh') <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="lokasi">Lokasi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('lokasi') is-invalid @enderror"
                                    id="lokasi" wire:model="lokasi" placeholder="Lokasi acara">
                                @error('lokasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="venue">Venue Ruangan</label>
                                <input type="text" class="form-control @error('venue') is-invalid @enderror" id="venue"
                                    wire:model="venue" placeholder="Nama gedung/ruangan">
                                @error('venue') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tanggal">Tanggal Event <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                                    id="tanggal" wire:model="tanggal">
                                @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tanggal_pendaftaran">Batas Pendaftaran <span
                                        class="text-secondary">(Opsional)</span></label>
                                <input type="date"
                                    class="form-control @error('tanggal_pendaftaran') is-invalid @enderror"
                                    id="tanggal_pendaftaran" wire:model="tanggal_pendaftaran">
                                @error('tanggal_pendaftaran') <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="technical_meeting">Technical Meeting <span
                                        class="text-secondary">(Opsional)</span></label>
                                <input type="datetime-local"
                                    class="form-control @error('technical_meeting') is-invalid @enderror"
                                    id="technical_meeting" wire:model="technical_meeting">
                                @error('technical_meeting') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tingkat_perlombaan">Tingkat Perlombaan <span
                                        class="text-secondary">(Opsional)</span></label>
                                <input type="text"
                                    class="form-control @error('tingkat_perlombaan') is-invalid @enderror"
                                    id="tingkat_perlombaan" wire:model="tingkat_perlombaan"
                                    placeholder="Misal: Sederajat SD/SMP/SMA">
                                @error('tingkat_perlombaan') <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-3 border-top pt-3">
                            <h6 class="fw-semibold mb-3">Akun Login Eventner</h6>
                            @if(!$isEditMode)
                                <div class="alert alert-warning py-2 fs-2 mb-3">Password otomatis dibuat: <b>password</b>
                                </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="username">Username <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror"
                                    id="username" wire:model="username" placeholder="Username untuk login">
                                @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                    wire:model="email" placeholder="Alamat email">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                        wire:click="resetForm">Batal</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Simpan</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('close-modal', () => {
                var myModalEl = document.getElementById('eventnerModal');
                var modal = bootstrap.Modal.getInstance(myModalEl);
                if (modal) modal.hide();
            });

            Livewire.on('open-modal', () => {
                var myModal = new bootstrap.Modal(document.getElementById('eventnerModal'));
                myModal.show();
            });
        });
    </script>
@endsection