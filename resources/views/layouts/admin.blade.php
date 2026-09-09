<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">

<head>
  <!-- Required meta tags -->
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Favicon icon-->
  <link rel="shortcut icon" type="image/png" href="{{ get_setting('favicon') ? Storage::url(get_setting('favicon')) : asset('templates/assets/images/logos/favicon.png') }}" />
  
  <meta name="description" content="{{ get_setting('meta_description') }}" />
  <meta name="keywords" content="{{ get_setting('meta_keywords') }}" />
  <meta name="google-adsense-account" content="ca-pub-5071798385516247" />
  <link rel="canonical" href="{{ url()->current() }}" />

  {{-- Open Graph --}}
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="{{ get_setting('site_title', 'Berbaris App') }}">
  <meta property="og:title" content="{{ $title ?? get_setting('site_title', 'Berbaris App') . ' - Dashboard' }}">
  <meta property="og:description" content="{{ get_setting('meta_description', 'Dashboard panel ' . app_name()) }}">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:locale" content="id_ID">

  {{-- Twitter Card --}}
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="{{ $title ?? get_setting('site_title', 'Berbaris App') . ' - Dashboard' }}">

  <!-- Core Css -->
  <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />

  <title>@yield('title', ($title ?? get_setting('site_title', 'Berbaris App')) . ' - Dashboard')</title>
  <!-- Owl Carousel  -->
  <link rel="stylesheet" href="{{ asset('templates/assets/libs/owl.carousel/dist/assets/owl.carousel.min.css') }}" />

  @livewireStyles
  
  <style>
    /* --- Logo Custom CSS --- */
    /* Standard Theme Switching for Full Logos */
    html[data-bs-theme="light"] .light-logo { display: none !important; }
    html[data-bs-theme="dark"] .dark-logo { display: none !important; }
    
    /* Hide full logos when sidebar is mini (optional - usually handled by template) */
    #main-wrapper[data-sidebartype="mini-sidebar"] .brand-logo .logo-img .dark-logo,
    #main-wrapper[data-sidebartype="mini-sidebar"] .brand-logo .logo-img .light-logo {
      display: none !important;
    }
    /* --- End Logo Custom CSS --- */
  </style>
</head>

<body>
  <!-- Preloader -->
  <div class="preloader">
    @if(get_setting('favicon'))
      <img src="{{ Storage::url(get_setting('favicon')) }}" alt="loader" class="lds-ripple img-fluid" style="width: 50px;" />
    @else
      <img src="{{ asset('templates/assets/images/logos/favicon.png') }}" alt="loader" class="lds-ripple img-fluid" />
    @endif
  </div>

  <div id="main-wrapper">
    <!-- Sidebar -->
    @include('layouts.partials.sidebar')

    <div class="page-wrapper">
      <!-- Header -->
      @include('layouts.partials.header')

      <!-- Main Content -->
      <div class="body-wrapper">
        <div class="container-fluid">
          {{ $slot }}
        </div>

        <!-- Footer -->
        @include('layouts.partials.footer')
      </div>
    </div>
  </div>

  <!-- Search Modal -->
  <livewire:search-links />

  <div class="dark-transparent sidebartoggler"></div>

  <!-- Import Js Files -->
  <script src="{{ asset('templates/assets/js/vendor.min.js') }}"></script>
  <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('templates/assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
  <script>
    // Auto-scroll sidebar ke item aktif
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
        const active = document.querySelector('.sidebar-link.active');
        if (active) {
          active.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
      }, 300);
    });
  </script>
  <script src="{{ asset('templates/assets/js/theme/app.init.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/theme.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/app.min.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/sidebarmenu.js') }}"></script>

  <!-- Owl Carousel -->
  <script src="{{ asset('templates/assets/libs/owl.carousel/dist/owl.carousel.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

  @livewireScripts
  @yield('scripts')
  @stack('scripts')
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Alert SweetAlert untuk semua halaman admin.
    // - beToast(msg, icon): notifikasi singkat pojok kanan atas
    // - beConfirm({title, text, ...}): dialog konfirmasi, return Promise<boolean>
    // - Override window.confirm: wire:confirm Livewire otomatis pakai
    //   SweetAlert (sinkron via handler __livewire_confirm).
    window.beToast = function(message, icon) {
      if (!window.Swal) { alert(message); return; }
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon || 'success',
        title: message,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
      });
    };

    window.beConfirm = function(options) {
      if (!window.Swal) { return Promise.resolve(window.confirm(options.title || 'Lanjutkan?')); }
      return Swal.fire({
        title: options.title || 'Yakin?',
        html: options.html || options.text || '',
        icon: options.icon || 'question',
        showCancelButton: true,
        confirmButtonText: options.confirmText || 'Ya',
        cancelButtonText: 'Batal',
        confirmButtonColor: options.confirmColor || '#198754',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
      }).then(function(result) { return result.isConfirmed; });
    };

    // wire:confirm memanggil global confirm() secara sinkron; override
    // menunggu SweetAlert dulu, lalu panggil handler asli saat dikonfirmasi.
    window.nativeConfirm = window.confirm;
    window.confirm = function(message) {
      // Fallback saat Swal tak ter-load (CDN gagal): konfirmasi native.
      if (!window.Swal) { return window.nativeConfirm(message); }

      const el = document.activeElement;
      const handler = el && el.__livewire_confirm;

      Swal.fire({
        title: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
      }).then(function(result) {
        if (result.isConfirmed && handler) {
          // Lepas __livewire_confirm dulu supaya klik ulang tidak
          // memicu konfirmasi dua kali, lalu pulihkan.
          const saved = el.__livewire_confirm;
          delete el.__livewire_confirm;
          el.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
          setTimeout(function() { if (!el.__livewire_confirm) el.__livewire_confirm = saved; }, 500);
        }
      });

      // Kembalikan false agar eksekusi native Livewire berhenti —
      // hanya handler SweetAlert yang melanjutkan saat dikonfirmasi.
      return false;
    };

    document.addEventListener('livewire:init', function () {
      Livewire.on('toast', function (event) {
        const d = (event && event.detail) || {};
        const message = typeof d === 'string' ? d : (d.message || 'Berhasil.');
        const type = (typeof d === 'object' && d.type) || 'success';
        window.beToast(message, type);
      });
    });

    document.addEventListener('DOMContentLoaded', function() {
      @if(session('success'))
        beToast({!! json_encode(session('success')) !!}, 'success');
      @endif
      @if(session('error'))
        beToast({!! json_encode(session('error')) !!}, 'error');
      @endif
      @if(session('scoring_error'))
        beToast({!! json_encode(session('scoring_error')) !!}, 'error');
      @endif
    });
  </script>
</body>
</html>
