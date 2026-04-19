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

  <!-- Core Css -->
  <link rel="stylesheet" href="{{ asset('templates/assets/css/styles.css') }}" />

  <title>{{ $title ?? get_setting('site_title', 'BARIS APP') . ' - Dashboard' }}</title>
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
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <input type="search" class="form-control fs-3" placeholder="Search here" id="search" />
          <a href="javascript:void(0)" data-bs-dismiss="modal" class="lh-1">
            <i class="ti ti-x fs-5 ms-3"></i>
          </a>
        </div>
        <div class="modal-body message-body" data-simplebar>
          <h5 class="mb-0 fs-5 p-1">Quick Page Links</h5>
          <ul class="list list-unstyled p-2" id="search-results">
            <li class="p-1 mb-1 bg-hover-light-black">
              <a href="{{ route('dashboard') }}">
                <span class="d-block">Dashboard</span>
                <span class="text-muted d-block">/dashboard</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="dark-transparent sidebartoggler"></div>

  <!-- Import Js Files -->
  <script src="{{ asset('templates/assets/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('templates/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('templates/assets/libs/simplebar/dist/simplebar.min.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/app.init.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/theme.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/app.min.js') }}"></script>
  <script src="{{ asset('templates/assets/js/theme/sidebarmenu.js') }}"></script>

  <!-- Owl Carousel -->
  <script src="{{ asset('templates/assets/libs/owl.carousel/dist/owl.carousel.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

  @livewireScripts
  @yield('scripts')
</body>
</html>
