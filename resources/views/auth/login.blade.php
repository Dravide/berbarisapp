@extends('layouts.auth')

@section('title', 'Login - BARIS APP')

@section('content')
<div class="position-relative overflow-hidden radial-gradient min-vh-100 w-100">
  <div class="position-relative z-index-5">
    <div class="row">
      <div class="col-xl-7 col-xxl-8">
        <a href="{{ url('/') }}" class="text-nowrap logo-img d-block px-4 py-9 w-100">
          <img src="{{ get_setting('logo_dark') ? Storage::url(get_setting('logo_dark')) : asset('templates/assets/images/logos/dark-logo.svg') }}" class="dark-logo" width="180" alt="Logo-Dark" />
          <img src="{{ get_setting('logo_light') ? Storage::url(get_setting('logo_light')) : asset('templates/assets/images/logos/light-logo.svg') }}" class="light-logo" width="180" alt="Logo-light" />
        </a>
        <div class="d-none d-xl-flex align-items-center justify-content-center h-n80">
          <img src="{{ asset('templates/assets/images/backgrounds/login-security.svg') }}" alt="modernize-img" class="img-fluid" width="500">
        </div>
      </div>
      <div class="col-xl-5 col-xxl-4">
        <div class="authentication-login min-vh-100 bg-body row justify-content-center align-items-center p-4">
          <div class="auth-max-width col-sm-8 col-md-6 col-xl-7 px-4">
            <h2 class="mb-1 fs-7 fw-bolder">Welcome to BARIS</h2>
            <p class="mb-7">Your Admin Dashboard</p>

            @if($errors->any())
              <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
              @csrf
              <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" id="exampleInputEmail1" value="{{ old('email') }}" required autofocus>
              </div>
              <div class="mb-4">
                <label for="exampleInputPassword1" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" id="exampleInputPassword1" required>
              </div>
              <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                  <input class="form-check-input primary" type="checkbox" name="remember" id="flexCheckChecked">
                  <label class="form-check-label text-dark fs-3" for="flexCheckChecked">
                    Remember this Device
                  </label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 py-8 mb-4 rounded-2">Sign In</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
