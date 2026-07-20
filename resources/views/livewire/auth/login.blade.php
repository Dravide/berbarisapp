<div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
  <div class="position-relative z-index-5">
    <div class="row">
      <div class="col-xl-7 col-xxl-8">
        <a href="{{ url('/') }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
          @php
            $logoDark = get_setting('logo_dark') ? Storage::url(get_setting('logo_dark')) : asset('templates/assets/images/logos/dark-logo.svg');
            $logoLight = get_setting('logo_light') ? Storage::url(get_setting('logo_light')) : asset('templates/assets/images/logos/light-logo.svg');
          @endphp
          <img src="{{ $logoDark }}" class="dark-logo" width="180" alt="Logo" />
          <img src="{{ $logoLight }}" class="light-logo" width="180" alt="Logo" />
        </a>
        <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
          <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="modernize-img" class="img-fluid" width="500">
        </div>
      </div>
      <div class="col-xl-5 col-xxl-4">
        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
          <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
            <h2 class="mb-1 fs-7 fw-bold">{{ get_setting('app_name', 'BARIS') }}</h2>
            <p class="mb-7">Silakan masuk untuk melanjutkan</p>

            <form wire:submit="authenticate">
              <div class="mb-3">
                <label for="inputLogin" class="form-label">Username atau Email</label>
                <input type="text" wire:model="login" class="form-control @error('login') is-invalid @enderror" id="inputLogin" placeholder="Masukkan username atau email" required autofocus>
                @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="mb-4">
                <label for="inputPassword" class="form-label">Password</label>
                <input type="password" wire:model="password" class="form-control" id="inputPassword" placeholder="Masukkan password" required>
              </div>
              <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                  <input class="form-check-input primary" type="checkbox" wire:model="remember" id="rememberMe">
                  <label class="form-check-label text-dark fs-3" for="rememberMe">
                    Ingat Saya
                  </label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2" wire:loading.attr="disabled">
                <span wire:loading.remove>Masuk</span>
                <span wire:loading>Memproses...</span>
              </button>
              <div class="text-center mt-3">
                <small class="text-muted">Eventner baru?
                  <a href="{{ route('register.eventner') }}" class="fw-semibold text-primary text-decoration-none">Daftar di sini</a>
                </small>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
