<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class SearchLinks extends Component
{
    public $search = '';

    public function getQuickLinksProperty(): array
    {
        $user = Auth::user();

        if ($user->role === 'Admin') {
            return $this->resolveUrls($this->adminLinks());
        }

        return $this->resolveUrls($this->eventnerLinks());
    }

    /**
     * Pre-compute route URLs so Alpine can use them directly.
     */
    private function resolveUrls(array $groups): array
    {
        foreach ($groups as &$group) {
            $group['items'] = array_values(array_filter($group['items'], fn($item) => !isset($item['condition']) || $item['condition']));

            foreach ($group['items'] as &$item) {
                $path = $item['route'] ? route($item['route'], $item['params'] ?? [], false) : '';
                $item['url'] = $path ? url($path) : '#';
                $item['path'] = $path ? '/' . ltrim($path, '/') : '';
                $item['target'] = $item['target'] ?? '_self';
                unset($item['params'], $item['condition']);
            }
            unset($item);
        }
        unset($group);

        return array_values(array_filter($groups, fn($group) => !empty($group['items'])));
    }

    private function adminLinks(): array
    {
        return [
            [
                'category' => 'Manajemen',
                'items' => [
                    [
                        'label' => 'Pendaftaran Eventner',
                        'route' => 'admin.eventner.pending',
                        'icon' => 'ti ti-user-plus',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Eventner Aktif',
                        'route' => 'admin.eventner.index',
                        'icon' => 'ti ti-building',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Manajemen User',
                        'route' => 'admin.users.index',
                        'icon' => 'ti ti-user-circle',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Data Sekolah',
                        'route' => 'admin.schools.index',
                        'icon' => 'ti ti-school',
                        'locked' => false,
                    ],
                ],
            ],
            [
                'category' => 'Pengaturan',
                'items' => [
                    [
                        'label' => 'Pengaturan Situs',
                        'route' => 'admin.settings.index',
                        'icon' => 'ti ti-settings',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Landing Page',
                        'route' => 'admin.settings.landing-page',
                        'icon' => 'ti ti-layout',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Pengaturan Harga',
                        'route' => 'admin.pricing-settings',
                        'icon' => 'ti ti-currency-dollar',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Pendapatan',
                        'route' => 'admin.revenue',
                        'icon' => 'ti ti-chart-line',
                        'locked' => false,
                    ],
                ],
            ],
        ];
    }

    private function eventnerLinks(): array
    {
        $ev = Auth::user()->eventner;
        $features = config('eventner_features', []);

        $locked = fn(string $key) => isset($features[$key])
            && $features[$key]['locked_free']
            && $ev
            && !$ev->canAccessFeature($key);

        return [
            [
                'category' => 'Acara',
                'items' => [
                    [
                        'label' => 'Dashboard Event',
                        'route' => 'eventner.dashboard',
                        'icon' => 'ti ti-aperture',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Profil Event',
                        'route' => 'eventner.profile.index',
                        'icon' => 'ti ti-home-cog',
                        'locked' => false,
                    ],
                    [
                        'label' => 'QR Link Event',
                        'route' => 'eventner.event-qr.index',
                        'icon' => 'ti ti-qrcode',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Tempat Lomba',
                        'route' => 'eventner.venues.index',
                        'icon' => 'ti ti-map-pin',
                        'locked' => false,
                    ],
                ],
            ],
            [
                'category' => 'Peserta',
                'items' => [
                    [
                        'label' => 'Kategori Lomba',
                        'route' => 'eventner.competition-categories.index',
                        'icon' => 'ti ti-layers-intersect',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Daftar Peserta',
                        'route' => 'eventner.participants.index',
                        'icon' => 'ti ti-users',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Daftar Juri',
                        'route' => 'eventner.judges.index',
                        'icon' => 'ti ti-user-check',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Drawing / Undian',
                        'route' => 'eventner.drawing.index',
                        'icon' => 'ti ti-arrows-shuffle',
                        'locked' => $locked('drawing'),
                    ],
                    [
                        'label' => 'Rundown Acara',
                        'route' => 'eventner.rundown.index',
                        'icon' => 'ti ti-list-details',
                        'locked' => $locked('rundown'),
                    ],
                ],
            ],
            [
                'category' => 'Penilaian',
                'items' => [
                    [
                        'label' => 'Format Penilaian',
                        'route' => 'eventner.format-nilai.builder',
                        'icon' => 'ti ti-checklist',
                        'locked' => $locked('format_nilai'),
                    ],
                    [
                        'label' => 'Input Nilai',
                        'route' => 'eventner.scoring.index',
                        'icon' => 'ti ti-pencil',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Rekap Nilai',
                        'route' => 'eventner.score-recap.index',
                        'icon' => 'ti ti-chart-bar',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Kategori Juara',
                        'route' => 'eventner.champion-categories.index',
                        'icon' => 'ti ti-trophy',
                        'locked' => $locked('champion_categories'),
                    ],
                    [
                        'label' => 'Sertifikat',
                        'route' => 'eventner.certificate.index',
                        'icon' => 'ti ti-certificate',
                        'locked' => $locked('certificate'),
                    ],
                ],
            ],
            [
                'category' => 'Voting',
                'items' => [
                    [
                        'label' => 'Pengaturan Vote',
                        'route' => 'eventner.vote-settings.index',
                        'icon' => 'ti ti-settings',
                        'locked' => $locked('vote_settings'),
                    ],
                    [
                        'label' => 'Vote Booster',
                        'route' => 'eventner.vote-booster.index',
                        'icon' => 'ti ti-bolt',
                        'locked' => $locked('vote_booster'),
                    ],
                    [
                        'label' => 'Hasil Voting',
                        'route' => 'eventner.vote-results.index',
                        'icon' => 'ti ti-chart-bar',
                        'locked' => $locked('vote_results'),
                    ],
                    [
                        'label' => 'Transaksi Voting',
                        'route' => 'eventner.vote-transactions.index',
                        'icon' => 'ti ti-file-invoice',
                        'locked' => $locked('vote_transactions'),
                    ],
                    [
                        'label' => 'Komentar Voting',
                        'route' => 'eventner.vote-comments.index',
                        'icon' => 'ti ti-messages',
                        'locked' => $locked('vote_transactions'),
                    ],
                ],
            ],
            [
                'category' => 'Tiket',
                'items' => [
                    [
                        'label' => 'Pengaturan Tiket',
                        'route' => 'eventner.tickets.settings',
                        'icon' => 'ti ti-ticket',
                        'locked' => $locked('ticket_settings'),
                    ],
                    [
                        'label' => 'Daftar Tiket',
                        'route' => 'eventner.tickets.index',
                        'icon' => 'ti ti-receipt',
                        'locked' => $locked('tickets'),
                    ],
                ],
            ],
            [
                'category' => 'Overlay',
                'items' => [
                    [
                        'label' => 'Livestream Overlay',
                        'route' => 'eventner.livestream.index',
                        'icon' => 'ti ti-video',
                        'locked' => $locked('livestream'),
                    ],
                    [
                        'label' => 'Live Scoreboard',
                        'route' => 'public.scoreboard',
                        'params' => ['scoringCode' => $ev?->scoring_code],
                        'icon' => 'ti ti-presentation',
                        'locked' => false,
                        'target' => '_blank',
                        'condition' => (bool) $ev?->scoring_code,
                    ],
                    [
                        'label' => 'Pengumuman Juara',
                        'route' => 'public.champions',
                        'params' => ['scoringCode' => $ev?->scoring_code],
                        'icon' => 'ti ti-trophy',
                        'locked' => false,
                        'target' => '_blank',
                        'condition' => (bool) $ev?->scoring_code,
                    ],
                ],
            ],
            [
                'category' => 'Lainnya',
                'items' => [
                    [
                        'label' => 'Activity Log',
                        'route' => 'eventner.activity-log.index',
                        'icon' => 'ti ti-history',
                        'locked' => false,
                    ],
                    [
                        'label' => 'FAQ',
                        'route' => 'eventner.faq.index',
                        'icon' => 'ti ti-info-circle',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Galeri',
                        'route' => 'eventner.gallery.index',
                        'icon' => 'ti ti-photo',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Kirim Notifikasi',
                        'route' => 'eventner.notification.index',
                        'icon' => 'ti ti-bell',
                        'locked' => false,
                    ],
                ],
            ],
            [
                'category' => 'Partner & Tenant',
                'items' => [
                    [
                        'label' => 'Sponsor & Partner',
                        'route' => 'eventner.sponsors.index',
                        'icon' => 'ti ti-affiliate',
                        'locked' => $locked('sponsors'),
                    ],
                    [
                        'label' => 'Tenant / Stand',
                        'route' => 'eventner.tenants.index',
                        'icon' => 'ti ti-building-store',
                        'locked' => $locked('tenants'),
                    ],
                ],
            ],
            [
                'category' => 'Keuangan',
                'items' => [
                    [
                        'label' => 'Dashboard Keuangan',
                        'route' => 'eventner.finance.index',
                        'icon' => 'ti ti-wallet',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Rekening Bank',
                        'route' => 'eventner.bank-accounts.index',
                        'icon' => 'ti ti-building-bank',
                        'locked' => false,
                    ],
                    [
                        'label' => 'TTD & Stempel',
                        'route' => 'eventner.signatures.index',
                        'icon' => 'ti ti-signature',
                        'locked' => false,
                    ],
                    [
                        'label' => 'Upgrade Paket',
                        'route' => 'eventner.billing.upgrade',
                        'icon' => 'ti ti-bolt',
                        'locked' => false,
                        'condition' => $ev && $ev->plan !== 'paid',
                    ],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.search-links');
    }
}
