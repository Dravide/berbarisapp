@php
    // Aturan password disusun sekali di sini dan dicerminkan ke atribut `minlength`
    // di kedua kolom password baru, supaya batas di klien tidak pernah berbeda
    // dari batas di server.
    $minBaru = 8;
@endphp

<div>
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Ganti Password</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Pengaturan Akun</li>
                            <li class="breadcrumb-item" aria-current="page">Ganti Password</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-end mb-n5">
                    <img src="{{ asset('templates/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" style="max-height: 80px;" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form wire:submit="save">
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password_lama') is-invalid @enderror"
                                wire:model="password_lama" autocomplete="current-password"
                                placeholder="Masukkan password yang dipakai sekarang">
                            @error('password_lama') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password_baru') is-invalid @enderror"
                                wire:model="password_baru" autocomplete="new-password"
                                minlength="{{ $minBaru }}" placeholder="Minimal {{ $minBaru }} karakter">
                            @error('password_baru') <span class="text-danger fs-2 d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ulangi Password Baru <span class="text-danger">*</span></label>
                            {{-- Pesan "konfirmasi tidak cocok" muncul di kolom Password Baru:
                                 aturan `confirmed` menempelkan errornya ke atribut aslinya,
                                 bukan ke kolom `_confirmation`. --}}
                            <input type="password" class="form-control"
                                wire:model="password_baru_confirmation" autocomplete="new-password"
                                minlength="{{ $minBaru }}" placeholder="Ketik ulang password baru">
                        </div>

                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <i class="ti ti-lock me-1"></i> Simpan Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="alert alert-light border border-info-subtle fs-2 mb-0">
                <i class="ti ti-info-circle me-1 text-info"></i>
                Password dipakai untuk masuk ke <strong>/login</strong>. Setelah diganti, sesi yang sedang
                terbuka di perangkat lain tetap berjalan sampai kedaluwarsa sendiri.
                <br><br>
                Lupa password saat ini? Keluar lalu pakai tautan
                <strong>Lupa Password</strong> di halaman login untuk mengatur ulang lewat email
                terdaftar.
            </div>
        </div>
    </div>
</div>
