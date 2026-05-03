<!-- Sidebar Start -->
<aside class="left-sidebar with-vertical">
  <div>
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="{{ route('dashboard') }}" class="text-nowrap logo-img">
        @php
          $logoDark = get_setting('logo_dark') ? Storage::url(get_setting('logo_dark')) : asset('templates/assets/images/logos/dark-logo.svg');
          $logoLight = get_setting('logo_light') ? Storage::url(get_setting('logo_light')) : asset('templates/assets/images/logos/light-logo.svg');
        @endphp

        <img src="{{ $logoDark }}" class="dark-logo" width="180" alt="Logo-Dark" />
        <img src="{{ $logoLight }}" class="light-logo" width="180" alt="Logo-light" />
      </a>
      <a href="javascript:void(0)" class="sidebartoggler ms-auto text-decoration-none fs-5 d-block d-xl-none">
        <i class="ti ti-x"></i>
      </a>
    </div>

    <nav class="sidebar-nav scroll-sidebar" data-simplebar>
      <ul id="sidebarnav">
        <!-- ---------------------------------- -->
        <!-- Home -->
        <!-- ---------------------------------- -->
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
          <span class="hide-menu">Home</span>
        </li>
        <!-- ---------------------------------- -->
        <!-- Dashboard -->
        <!-- ---------------------------------- -->
        <li class="sidebar-item">
          <a class="sidebar-link {{ request()->routeIs('dashboard') || request()->routeIs('eventner.dashboard') || request()->routeIs('admin.dashboard') ? 'active' : '' }}"
            href="{{ route('dashboard') }}" aria-expanded="false">
            <span>
              <i class="ti ti-aperture"></i>
            </span>
            <span class="hide-menu">Dashboard</span>
          </a>
        </li>

        @if(auth()->user()->role === 'Admin')
          <!-- ---------------------------------- -->
          <!-- Admin Menu -->
          <!-- ---------------------------------- -->
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Manajemen</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.eventner.*') ? 'active' : '' }}"
              href="{{ route('admin.eventner.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-users"></i>
              </span>
              <span class="hide-menu">Eventner</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="javascript:void(0)" aria-expanded="false">
              <span>
                <i class="ti ti-user-circle"></i>
              </span>
              <span class="hide-menu">Peserta</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
              href="{{ route('admin.settings.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-settings"></i>
              </span>
              <span class="hide-menu">Pengaturan</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.settings.landing-page') ? 'active' : '' }}"
              href="{{ route('admin.settings.landing-page') }}" aria-expanded="false">
              <span>
                <i class="ti ti-layout"></i>
              </span>
              <span class="hide-menu">Landing Page</span>
            </a>
          </li>
        @endif

        @if(auth()->user()->role === 'Eventner')
          {{-- ============================================ --}}
          {{-- ACARA --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Acara</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.profile.*') ? 'active' : '' }}"
              href="{{ route('eventner.profile.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-home-cog"></i>
              </span>
              <span class="hide-menu">Profil Event</span>
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- PESERTA --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Peserta</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.competition-categories.*') ? 'active' : '' }}"
              href="{{ route('eventner.competition-categories.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-layers-intersect"></i>
              </span>
              <span class="hide-menu">Kategori Lomba</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.participants.*') ? 'active' : '' }}"
              href="{{ route('eventner.participants.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-users"></i>
              </span>
              <span class="hide-menu">Daftar Peserta</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.judges.*') ? 'active' : '' }}"
              href="{{ route('eventner.judges.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-user"></i>
              </span>
              <span class="hide-menu">Daftar Juri</span>
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- PENILAIAN --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Penilaian</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.format-nilai.*') ? 'active' : '' }}"
              href="{{ route('eventner.format-nilai.builder') }}" aria-expanded="false">
              <span>
                <i class="ti ti-checklist"></i>
              </span>
              <span class="hide-menu">Format Penilaian</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.scoring.*') ? 'active' : '' }}"
              href="{{ route('eventner.scoring.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-clipboard-check"></i>
              </span>
              <span class="hide-menu">Input Nilai</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.score-recap.*') ? 'active' : '' }}"
              href="{{ route('eventner.score-recap.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-report-analytics"></i>
              </span>
              <span class="hide-menu">Rekap Nilai</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.champion-categories.*') ? 'active' : '' }}"
              href="{{ route('eventner.champion-categories.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-trophy"></i>
              </span>
              <span class="hide-menu">Kategori Juara</span>
            </a>
          </li>

          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.drawing.*') ? 'active' : '' }}"
              href="{{ route('eventner.drawing.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-arrows-shuffle"></i>
              </span>
              <span class="hide-menu">Drawing / Undian</span>
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- INTERAKSI --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Interaksi</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.vote-results.*') ? 'active' : '' }}"
              href="{{ route('eventner.vote-results.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-chart-bar"></i>
              </span>
              <span class="hide-menu">Hasil Voting</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.tickets.*') ? 'active' : '' }}"
              href="{{ route('eventner.tickets.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-ticket"></i>
              </span>
              <span class="hide-menu">Tiket Online</span>
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- LAINNYA --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Lainnya</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.activity-log.*') ? 'active' : '' }}"
              href="{{ route('eventner.activity-log.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-history"></i>
              </span>
              <span class="hide-menu">Activity Log</span>
            </a>
          </li>
          @if(auth()->user()->eventner && auth()->user()->eventner->scoring_code)
          <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('public.scoreboard', auth()->user()->eventner->scoring_code) }}" target="_blank" aria-expanded="false">
              <span>
                <i class="ti ti-presentation"></i>
              </span>
              <span class="hide-menu">Live Scoreboard</span>
            </a>
          </li>
          @endif
          @if(auth()->user()->eventner && auth()->user()->eventner->scoring_code)
          <li class="sidebar-item">
            <a class="sidebar-link" href="{{ route('public.champions', auth()->user()->eventner->scoring_code) }}" target="_blank" aria-expanded="false">
              <span>
                <i class="ti ti-trophy"></i>
              </span>
              <span class="hide-menu">Pengumuman Juara</span>
            </a>
          </li>
          @endif
        @endif
      </ul>
    </nav>

    <div class="fixed-profile p-3 mx-4 mb-2 bg-secondary-subtle rounded mt-3">
      <div class="hstack gap-3">
        <div class="john-img">
          <img src="{{ asset('templates/assets/images/profile/user-1.jpg') }}" class="rounded-circle" width="40"
            height="40" alt="user" />
        </div>
        <div class="john-title">
          <h6 class="mb-0 fs-4 fw-semibold">{{ auth()->user()->name }}</h6>
          <span class="fs-2">{{ auth()->user()->role }}</span>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="ms-auto">
          @csrf
          <button type="submit" class="border-0 bg-transparent text-primary" tabindex="0" aria-label="logout"
            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="logout">
            <i class="ti ti-power fs-6"></i>
          </button>
        </form>
      </div>
    </div>

    <!-- ---------------------------------- -->
    <!-- End Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->
  </div>
</aside>
<!--  Sidebar End -->