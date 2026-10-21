<?php

namespace App\Livewire\Public;

use App\Models\Eventner;
use App\Support\PenugasanJuri;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.frontend')]
class EventDetail extends Component
{
    public $eventner;

    // Tab state
    public $tab = 'info';
    public $registration_status;

    protected $queryString = [
        'tab' => ['except' => 'info'],
    ];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::with([
                'competitionCategories.venue',
                'competitionCategories.judges',
                'competitionCategories.registrations' => function ($query) {
                    $query->withSum(['voteTransactions as total_votes' => function ($q) {
                        $q->where('status', 'PAID');
                    }], 'votes_earned')
                        ->where('status_berkas', '!=', 'dibatalkan')
                        ->orderBy('urutan_tampil', 'asc');
                },
                'competitionCategories.registrations.participants',
                // Kartu "Dewan Juri" membaca competition_group_judge lewat
                // PenugasanJuri::perJuri(), bukan relasi rubrik — rubrik ikut
                // seri peserta, jadi di event nyata ia sering kosong.
                'judges',
                'sponsors' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order')->latest();
                },
                'tenants' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order')->latest();
                },
                'competitionCategories.children.judges',
                'competitionCategories.children.venue',
                'competitionCategories.children.registrations',
                // Kartu tingkat menanyakan harga yang berlaku hari ini lewat
                // biayaPada(); tanpa gelombang yang sudah dimuat, tiap kartu
                // menembak querynya sendiri.
                'competitionCategories.gelombang',
                'competitionCategories.children.gelombang',
            ])->approved()->where('slug', $slug)->firstOrFail();
        }

        $this->registration_status = $this->eventner->registration_status ?? 'open';

        if (!in_array($this->tab, ['info', 'participants', 'vote'])) {
            $this->tab = 'info';
        }
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    /**
     * Leaderboard voting: top kontingen per kategori lomba.
     *
     * Hanya tampil kalau vote-nya memang diaktifkan eventner. Dulu papan ini
     * muncul cukup dengan terisinya `vote_start`, sehingga event yang fitur
     * votingnya OFF tetap menampilkan "Voter Tertinggi per Kategori" berisi
     * angka nol — dan mengundang orang mengklik tombol Vote yang tidak bisa
     * dipakai. `vote_active` adalah sakelar yang sama dengan yang dipakai
     * EventVote::mount(), jadi papan ini tidak bisa lagi hidup sendiri.
     */
    #[Computed]
    public function voteLeaderboard()
    {
        if (! $this->eventner->vote_active) {
            return collect();
        }

        $now = now();
        $voteStart = $this->eventner->vote_start ? \Carbon\Carbon::parse($this->eventner->vote_start) : null;
        $voteEnd = $this->eventner->vote_end ? \Carbon\Carbon::parse($this->eventner->vote_end) : null;

        // Sembunyikan kalau vote_start belum sampai
        if ($voteStart && $now->lt($voteStart)) {
            return collect();
        }

        $categories = $this->eventner->competitionCategories->whereNotNull('parent_id');

        // Query langsung agar total_votes terisi di jalur subdomain (middleware bind tanpa eager load)
        $registrations = \App\Models\Registration::whereIn('competition_category_id', $categories->pluck('id'))
            ->withSum(['voteTransactions as total_votes' => function ($q) {
                $q->where('status', 'PAID');
            }], 'votes_earned')
            ->where('status_berkas', '!=', 'dibatalkan')
            ->get();

        $perCategory = [];

        foreach ($categories as $cat) {
            $top = $registrations
                ->where('competition_category_id', $cat->id)
                ->sortByDesc('total_votes')
                ->take(5)
                ->values();

            $perCategory[] = [
                'category' => $cat,
                'top' => $top,
            ];
        }

        return empty($perCategory) ? collect() : $perCategory;
    }

    /**
     * Dewan juri + tugas penilaiannya, siap dirender kartu.
     *
     * Urut A–Z, bukan urutan insert: tabel `judges` tak punya sort_order, jadi
     * urutan bawaan tak berarti apa-apa dan berbeda tiap kali data disemai.
     *
     * Sumber tugasnya competition_group_judge — sama dengan tablet juri dan
     * modal penugasan panitia (App\Support\PenugasanJuri). Dulu kartu ini
     * membaca `judges.assessmentCategories` (rubrik), yang di event nyata
     * sering kosong karena rubrik menempel pada seri peserta, bukan juri:
     * hasilnya baris chip kosong.
     *
     * @return \Illuminate\Support\Collection<int, array{judge: \App\Models\Judge, tugas: string, induk: ?string, inisial: string, aksen: string}>
     */
    #[Computed]
    public function dewanJuri()
    {
        $judges = $this->eventner->judges->sortBy(
            fn ($j) => mb_strtolower($j->name ?? '')
        )->values();

        $penugasan = PenugasanJuri::perJuri($this->eventner->id);

        return $judges->map(function ($judge, $urut) use ($penugasan) {
            $perTingkat = $penugasan->get($judge->id, collect());

            return [
                'judge' => $judge,
                'tugas' => PenugasanJuri::ringkas($perTingkat),
                // Nama induk tingkat pertama, untuk chip ringkas di bentuk
                // baris. Null kalau juri belum punya penugasan sama sekali.
                'induk' => $perTingkat->first()['induk'] ?? null,
                'inisial' => self::inisial($judge->name),
                // Warna aksen bergilir menurut urutan A–Z, bukan hash nama:
                // nama Indonesia yang mirip (Ahmad/Budi/Citra) menghasilkan sisa
                // bagi yang sama, jadi justru dewan tiga orang — kasus yang
                // bentuk kartu ini dibuat untuknya — tampil sewarna semua.
                // Bergilir menurut posisi menjamin tetangga selalu berbeda.
                'aksen' => self::AKSEN_JURI[$urut % count(self::AKSEN_JURI)],
            ];
        });
    }

    /**
     * Tiga warna aksen monogram juri, bergilir menurut urutan A–Z.
     *
     * Ketiganya harus terbaca sebagai teks di atas permukaan terang, jadi
     * neon-lime mentah (#a3e635) tidak dipakai — terlalu pucat untuk huruf.
     *
     * Ketiganya juga kelas yang SUDAH terpakai di blade lain. Kelas Tailwind
     * hanya lahir kalau namanya muncul di berkas yang dipindai @source, dan
     * berkas ini bukan salah satunya — kelas karangan baru di sini akan tercetak
     * di HTML tanpa satu pun aturan CSS di belakangnya, dan monogramnya kembali
     * sewarna seperti sebelumnya tanpa ada yang tahu.
     */
    private const AKSEN_JURI = ['text-primary', 'text-emerald-600', 'text-amber-700'];

    /**
     * Inisial dua huruf: awal kata pertama + awal kata terakhir.
     *
     * Dipakai bidang potret juri yang belum berfoto. Satu huruf membuat ketiga
     * kartu terbaca identik seperti sebelumnya; dua huruf cukup membedakan
     * tanpa menuntut kolom baru.
     */
    private static function inisial(?string $nama): string
    {
        $kata = preg_split('/\s+/', trim((string) $nama), -1, PREG_SPLIT_NO_EMPTY);

        if (!$kata) {
            return '';
        }

        $inisial = mb_substr($kata[0], 0, 1);

        if (count($kata) > 1) {
            $inisial .= mb_substr($kata[count($kata) - 1], 0, 1);
        }

        return mb_strtoupper($inisial);
    }

    public function render()
    {
        return view('livewire.public.event-detail', [
            'eventner' => $this->eventner,
        ])->title($this->eventner->nama_event . ' - ' . ($this->eventner->diselenggarakan_oleh ?: app_name()))
            ->layoutData(['eventner' => $this->eventner]);
    }
}
