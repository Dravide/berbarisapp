<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9"><h4 class="fw-semibold mb-8">Kirim Notifikasi</h4></div>
                <div class="col-3 text-end mb-n5"><img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" class="img-fluid mb-n4" style="max-height: 80px;"></div>
            </div>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h5 class="card-title fw-semibold mb-3"><i class="ti ti-bell me-1"></i> Pesan Baru</h5>
                <form wire:submit="send">
                    <div class="mb-3">
                        <label class="form-label">Judul</label>
                        <input class="form-control" wire:model="title" maxlength="100" placeholder="Contoh: Info Technical Meeting">
                        @error('title')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi Pesan</label>
                        <textarea class="form-control" wire:model="body" rows="4" maxlength="1000" placeholder="Tulis pesan untuk peserta..."></textarea>
                        @error('body')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select class="form-select" wire:model="target">
                            <option value="broadcast">Semua Peserta</option>
                            <option value="registration">Satu Sekolah/Pasukan</option>
                        </select>
                    </div>
                    <div class="mb-3" wire:ignore>
                        <label class="form-label">Pilih Peserta</label>
                        <select class="form-select" wire:model="registrationId" @if($target !== 'registration') disabled @endif>
                            <option value="">— Pilih sekolah/pasukan —</option>
                            @foreach($registrations as $r)
                                <option value="{{ $r->id }}">{{ $r->nama_sekolah }}{{ $r->label_pasukan ? ' — ' . $r->label_pasukan : '' }}</option>
                            @endforeach
                        </select>
                        @error('registrationId')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100" @disabled($sending)>
                        @if($sending)
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm"></span> Mengirim...
                            </span>
                        @else
                            <span><i class="ti ti-send me-1"></i> Kirim</span>
                        @endif
                    </button>
                </form>
            </div></div>
        </div>

        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <h5 class="card-title fw-semibold mb-3"><i class="ti ti-info-circle me-1"></i> Cara Kerja</h5>
                <ul class="small text-muted mb-0">
                    <li class="mb-2"><strong>Semua Peserta</strong> — notifikasi dikirim ke semua device peserta yang sudah scan QR di event ini.</li>
                    <li class="mb-2"><strong>Satu Sekolah/Pasukan</strong> — kirim ke semua device milik peserta tertentu.</li>
                    <li>Notifikasi muncul di aplikasi peserta sebagai push notification.</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
