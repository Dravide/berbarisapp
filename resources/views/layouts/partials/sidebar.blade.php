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
          {{-- ============================================ --}}
          {{-- MANAJEMEN UTAMA --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Manajemen</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.eventner.pending') ? 'active' : '' }}"
              href="{{ route('admin.eventner.pending') }}" aria-expanded="false">
              <span>
                <i class="ti ti-user-plus"></i>
              </span>
              <span class="hide-menu">Pendaftaran Eventner</span>
              @php $pendingCount = \App\Models\Eventner::where('status', 'pending')->count(); @endphp
              @if($pendingCount > 0)
                <span class="badge bg-danger rounded-pill ms-auto">{{ $pendingCount }}</span>
              @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.eventner.index') || request()->routeIs('admin.eventner.show') ? 'active' : '' }}"
              href="{{ route('admin.eventner.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-building"></i>
              </span>
              <span class="hide-menu">Eventner Aktif</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
              href="{{ route('admin.users.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-user-circle"></i>
              </span>
              <span class="hide-menu">Manajemen User</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}"
              href="{{ route('admin.schools.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-school"></i>
              </span>
              <span class="hide-menu">Data Sekolah</span>
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- PENGATURAN --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Pengaturan</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.settings.index') && !request()->routeIs('admin.settings.landing-page') ? 'active' : '' }}"
              href="{{ route('admin.settings.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-settings"></i>
              </span>
              <span class="hide-menu">Pengaturan Situs</span>
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
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('admin.revenue') ? 'active' : '' }}"
              href="{{ route('admin.revenue') }}" aria-expanded="false">
              <span>
                <i class="ti ti-chart-arcs"></i>
              </span>
              <span class="hide-menu">Pendapatan Platform</span>
            </a>
          </li>
        @endif

        @if(auth()->user()->role === 'Eventner')
          @php $ev = auth()->user()->eventner; @endphp
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
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.event-qr.*') ? 'active' : '' }}"
              href="{{ route('eventner.event-qr.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-qrcode"></i>
              </span>
              <span class="hide-menu">QR Link Event</span>
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
                <i class="ti ti-user-check"></i>
              </span>
              <span class="hide-menu">Daftar Juri</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.drawing.*') ? 'active' : '' }}"
              href="{{ route('eventner.drawing.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-arrows-shuffle"></i>
              </span>
              <span class="hide-menu">Drawing / Undian</span>
              @if($ev && !$ev->canAccessFeature('drawing')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.rundown.*') ? 'active' : '' }}"
              href="{{ route('eventner.rundown.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-list-details"></i>
              </span>
              <span class="hide-menu">Rundown Acara</span>
              @if($ev && !$ev->canAccessFeature('rundown')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
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
            <a class="sidebar-link {{ request()->routeIs(['eventner.format-nilai.builder','eventner.format-nilai.copy-form','eventner.format-nilai.copy-execute','eventner.format-nilai.pdf','eventner.format-nilai.pdf-child']) ? 'active' : '' }}"
              href="{{ route('eventner.format-nilai.builder') }}" aria-expanded="false">
              <span>
                <i class="ti ti-checklist"></i>
              </span>
              <span class="hide-menu">Format Penilaian</span>
              @if($ev && !$ev->canAccessFeature('format_nilai')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.format-nilai.download') ? 'active' : '' }}"
              href="{{ route('eventner.format-nilai.download') }}" aria-expanded="false">
              <span>
                <i class="ti ti-file-download"></i>
              </span>
              <span class="hide-menu">Unduh Format Penilaian</span>
              @if($ev && !$ev->canAccessFeature('format_nilai')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.scoring.*') ? 'active' : '' }}"
              href="{{ route('eventner.scoring.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-pencil"></i>
              </span>
              <span class="hide-menu">Input Nilai</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.score-recap.*') ? 'active' : '' }}"
              href="{{ route('eventner.score-recap.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-chart-bar"></i>
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
              @if($ev && !$ev->canAccessFeature('champion_categories')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.certificate.*') ? 'active' : '' }}"
              href="{{ route('eventner.certificate.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-certificate"></i>
              </span>
              <span class="hide-menu">Sertifikat</span>
              @if($ev && !$ev->canAccessFeature('certificate')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- VOTING --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Voting</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.vote-settings.*') ? 'active' : '' }}"
              href="{{ route('eventner.vote-settings.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-settings"></i>
              </span>
              <span class="hide-menu">Pengaturan Vote</span>
              @if($ev && !$ev->canAccessFeature('vote_settings')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.vote-booster.*') ? 'active' : '' }}"
              href="{{ route('eventner.vote-booster.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-bolt"></i>
              </span>
              <span class="hide-menu">Vote Booster</span>
              @if($ev && !$ev->canAccessFeature('vote_booster')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.vote-results.*') ? 'active' : '' }}"
              href="{{ route('eventner.vote-results.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-chart-bar"></i>
              </span>
              <span class="hide-menu">Hasil Voting</span>
              @if($ev && !$ev->canAccessFeature('vote_results')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.vote-transactions.*') ? 'active' : '' }}"
              href="{{ route('eventner.vote-transactions.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-file-invoice"></i>
              </span>
              <span class="hide-menu">Transaksi Voting</span>
              @if($ev && !$ev->canAccessFeature('vote_transactions')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.vote-comments.*') ? 'active' : '' }}"
              href="{{ route('eventner.vote-comments.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-messages"></i>
              </span>
              <span class="hide-menu">Komentar Voting</span>
              @if($ev && !$ev->canAccessFeature('vote_transactions')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- TIKET --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Tiket</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.tickets.settings') ? 'active' : '' }}"
              href="{{ route('eventner.tickets.settings') }}" aria-expanded="false">
              <span>
                <i class="ti ti-ticket"></i>
              </span>
              <span class="hide-menu">Pengaturan Tiket</span>
              @if($ev && !$ev->canAccessFeature('ticket_settings')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.tickets.index') && !request()->routeIs('eventner.tickets.settings') ? 'active' : '' }}"
              href="{{ route('eventner.tickets.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-receipt"></i>
              </span>
              <span class="hide-menu">Daftar Tiket</span>
              @if($ev && !$ev->canAccessFeature('tickets')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- OVERLAY --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Overlay</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.livestream.*') ? 'active' : '' }}"
              href="{{ route('eventner.livestream.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-video"></i>
              </span>
              <span class="hide-menu">Livestream Overlay</span>
              @if($ev && !$ev->canAccessFeature('livestream')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
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
              @if($ev && !$ev->canAccessFeature('activity_log')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.faq.*') ? 'active' : '' }}"
              href="{{ route('eventner.faq.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-info-circle"></i>
              </span>
              <span class="hide-menu">FAQ</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.gallery.*') ? 'active' : '' }}"
              href="{{ route('eventner.gallery.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-photo"></i>
              </span>
              <span class="hide-menu">Galeri</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.notification.*') ? 'active' : '' }}"
              href="{{ route('eventner.notification.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-bell"></i>
              </span>
              <span class="hide-menu">Kirim Notifikasi</span>
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- PARTNER & TENANT --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Partner & Tenant</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.sponsors.*') ? 'active' : '' }}"
              href="{{ route('eventner.sponsors.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-affiliate"></i>
              </span>
              <span class="hide-menu">Sponsor & Partner</span>
              @if($ev && !$ev->canAccessFeature('sponsors')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.tenants.*') ? 'active' : '' }}"
              href="{{ route('eventner.tenants.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-building-store"></i>
              </span>
              <span class="hide-menu">Tenant / Stand</span>
              @if($ev && !$ev->canAccessFeature('tenants')) <i class="ti ti-lock text-muted ms-auto" style="font-size: 0.7rem;"></i> @endif
            </a>
          </li>

          {{-- ============================================ --}}
          {{-- KEUANGAN --}}
          {{-- ============================================ --}}
          <li class="nav-small-cap">
            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
            <span class="hide-menu">Keuangan</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.finance.*') ? 'active' : '' }}"
              href="{{ route('eventner.finance.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-wallet"></i>
              </span>
              <span class="hide-menu">Dashboard Keuangan</span>
              @php $paymentPendingCount = $ev ? \App\Models\Registration::where('eventner_id', $ev->id)->where('payment_status', 'pending_verification')->count() : 0; @endphp
              @if($paymentPendingCount > 0)
                <span class="badge bg-warning rounded-pill ms-auto">{{ $paymentPendingCount }}</span>
              @endif
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.bank-accounts.*') ? 'active' : '' }}"
              href="{{ route('eventner.bank-accounts.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-building-bank"></i>
              </span>
              <span class="hide-menu">Rekening Bank</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link {{ request()->routeIs('eventner.signatures.*') ? 'active' : '' }}"
              href="{{ route('eventner.signatures.index') }}" aria-expanded="false">
              <span>
                <i class="ti ti-signature"></i>
              </span>
              <span class="hide-menu">TTD &amp; Stempel</span>
            </a>
          </li>
          @if($ev && $ev->plan !== 'paid')
            <li class="sidebar-item">
              <a class="sidebar-link {{ request()->routeIs('eventner.billing.*') ? 'active' : '' }}"
                href="{{ route('eventner.billing.upgrade') }}" aria-expanded="false">
                <span>
                  <i class="ti ti-bolt"></i>
                </span>
                <span class="hide-menu">Upgrade Paket</span>
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