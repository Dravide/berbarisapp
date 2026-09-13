<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use App\Models\VoteTransaction;
use App\Models\CompetitionCategory;
use App\Models\Judge;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Show extends Component
{
    public $eventnerId;
    public $eventner;
    public $totalRevenue = 0;
    public $totalRegistrations = 0;
    public $totalCategories = 0;
    public $totalJudges = 0;
    public $recentRegistrations;

    /** Paket yang sedang dipilih di kartu "Paket SaaS" ('' = legacy). */
    public $planId = '';

    public function mount($id)
    {
        $this->eventnerId = $id;
        $this->loadData();
    }

    public function loadData()
    {
        $this->eventner = Eventner::with(['user', 'competitionCategories', 'saasPlan.features'])
            ->findOrFail($this->eventnerId);

        $this->planId = $this->eventner->saas_plan_id ?? '';

        // Stats
        $this->totalRevenue = VoteTransaction::where('eventner_id', $this->eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $this->totalRegistrations = Registration::where('eventner_id', $this->eventnerId)->count();
        $this->totalCategories = $this->eventner->competitionCategories()->count();
        
        // Count unique judges in this eventner (through categories)
        $this->totalJudges = Judge::where('eventner_id', $this->eventnerId)->count();

        // Recent registrations
        $this->recentRegistrations = Registration::with('competitionCategory')
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Paket yang boleh dipasang admin.
     *
     * Paket "hubungi admin" (is_contact) IKUT ditawarkan — is_contact hanya
     * mengubah tombol di halaman harga publik, bukan fitur. Lihat catatan di
     * Admin\Eventner\Index::getPlansProperty().
     *
     * Pengecualian: paket yang sedang terpasang di event ini selalu ikut
     * ditampilkan, walau sudah dinonaktifkan. Tanpa itu, select tidak punya
     * opsi yang cocok dengan nilai saat ini dan admin terkunci — menyimpan
     * berarti memindahkan event ini ke paket lain tanpa jalan kembali.
     */
    public function getPlansProperty(): Collection
    {
        return SaasPlan::where(function ($q) {
                $q->where('is_active', true)
                  ->orWhere('id', $this->eventner->saas_plan_id);
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function savePlan()
    {
        $this->validate([
            'planId' => [
                'required',
                // Paket nonaktif ditolak, kecuali paket yang sedang terpasang:
                // tanpa pengecualian ini, menyimpan ulang event yang paketnya
                // sudah dinonaktifkan akan gagal tanpa jalan keluar.
                Rule::exists('saas_plans', 'id')->where(function ($q) {
                    $q->where('is_active', 1);

                    if ($this->eventner->saas_plan_id !== null) {
                        $q->orWhere('id', $this->eventner->saas_plan_id);
                    }
                }),
            ],
        ], [], ['planId' => 'paket']);

        $plan = SaasPlan::findOrFail($this->planId);
        $this->eventner->assignPlan($plan);

        $this->loadData();

        session()->flash('success', "Paket event berhasil diubah ke \"{$plan->name}\".");
    }

    /**
     * Status modul per fitur eventner: jumlah data + akses (sesuai paket SaaS).
     */
    public function getFeatureStatusesProperty(): array
    {
        $e = $this->eventner;

        $modules = [
            'ticket' => [
                'label' => 'Tiket Online',
                'route' => 'eventner.tickets.index',
                'feature' => 'tickets',
                'icon' => 'ti-ticket',
                'count' => $e->tickets()->count(),
                'count_label' => 'Tiket',
                'meta' => $e->ticket_active
                    ? ($e->hasTicketPrice() ? 'Aktif — Rp ' . number_format((int) $e->startingTicketPrice(), 0, ',', '.') : 'Aktif — harga belum diisi')
                    : 'Belum diaktifkan',
            ],
            'vote' => [
                'label' => 'Voting',
                'route' => 'eventner.vote-results.index',
                'feature' => 'vote_results',
                'icon' => 'ti-heart',
                'count' => $e->voteTransactions()->count(),
                'count_label' => 'Transaksi',
                'meta' => $e->vote_active ? 'Aktif — Rp ' . number_format((int) $e->vote_price, 0, ',', '.') . '/vote' : 'Belum diaktifkan',
            ],
            'champion' => [
                'label' => 'Kategori Juara',
                'route' => null,
                'feature' => 'champion_categories',
                'icon' => 'ti-trophy',
                'count' => $e->championCategories()->count(),
                'count_label' => 'Kategori',
                'meta' => null,
            ],
            'format_nilai' => [
                'label' => 'Format Penilaian',
                'route' => 'eventner.format-nilai.builder',
                'feature' => 'format_nilai',
                'icon' => 'ti-checklist',
                'count' => $e->assessmentCategories()->count(),
                'count_label' => 'Aspek',
                'meta' => null,
            ],
            'drawing' => [
                'label' => 'Drawing / Undian',
                'route' => 'eventner.drawing.index',
                'feature' => 'drawing',
                'icon' => 'ti-arrows-shuffle',
                'count' => $e->registrations()->whereNotNull('qr_token')->count(),
                'count_label' => 'Peserta',
                'meta' => $e->drawing_code ? "Kode: {$e->drawing_code}" : null,
            ],
            'rundown' => [
                'label' => 'Rundown Acara',
                'route' => 'eventner.rundown.index',
                'feature' => 'rundown',
                'icon' => 'ti-list-details',
                'count' => $e->eventRundowns()->count(),
                'count_label' => 'Agenda',
                'meta' => null,
            ],
            'livestream' => [
                'label' => 'Livestream Overlay',
                'route' => 'eventner.livestream.index',
                'feature' => 'livestream',
                'icon' => 'ti-video',
                'count' => $e->overlaySetting ? 1 : 0,
                'count_label' => 'Setting',
                'meta' => $e->link_livestreaming ? 'Stream terhubung' : 'Belum ada stream',
            ],
            'sponsors' => [
                'label' => 'Sponsor & Partner',
                'route' => 'eventner.sponsors.index',
                'feature' => 'sponsors',
                'icon' => 'ti-affiliate',
                'count' => $e->sponsors()->count(),
                'count_label' => 'Sponsor',
                'meta' => null,
            ],
            'tenants' => [
                'label' => 'Tenant / Stand',
                'route' => 'eventner.tenants.index',
                'feature' => 'tenants',
                'icon' => 'ti-building-store',
                'count' => $e->tenants()->count(),
                'count_label' => 'Tenant',
                'meta' => null,
            ],
            'certificate' => [
                'label' => 'Sertifikat',
                'route' => 'eventner.certificate.index',
                'feature' => 'certificate',
                'icon' => 'ti-certificate',
                'count' => $e->certificateTemplates()->count(),
                'count_label' => 'Template',
                'meta' => null,
            ],
            'faq' => [
                'label' => 'FAQ',
                'route' => 'eventner.faq.index',
                'feature' => null,
                'icon' => 'ti-info-circle',
                'count' => $e->faqs()->count(),
                'count_label' => 'Pertanyaan',
                'meta' => null,
            ],
            'gallery' => [
                'label' => 'Galeri',
                'route' => 'eventner.gallery.index',
                'feature' => null,
                'icon' => 'ti-photo',
                'count' => $e->galleries()->count(),
                'count_label' => 'Foto',
                'meta' => null,
            ],
            'signature' => [
                'label' => 'TTD & Stempel',
                'route' => 'eventner.signatures.index',
                'feature' => null,
                'icon' => 'ti-signature',
                'count' => $e->signatures()->count(),
                'count_label' => 'TTD',
                'meta' => null,
            ],
            'bank' => [
                'label' => 'Rekening Bank',
                'route' => 'eventner.bank-accounts.index',
                'feature' => null,
                'icon' => 'ti-building-bank',
                'count' => $e->bankAccounts()->count(),
                'count_label' => 'Rekening',
                'meta' => null,
            ],
        ];

        // Tandai akses via gate eventner
        foreach ($modules as $key => &$m) {
            $m['locked'] = $m['feature'] !== null && !$e->canAccessFeature($m['feature']);
        }

        return $modules;
    }

    public function render()
    {
        return view('livewire.admin.eventner.show')
            ->title('Detail Event: ' . $this->eventner->nama_event . ' - ' . app_name());
    }
}
