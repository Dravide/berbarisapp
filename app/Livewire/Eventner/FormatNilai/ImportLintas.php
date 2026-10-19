<?php

namespace App\Livewire\Eventner\FormatNilai;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Support\FormatNilaiSalin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Import rubrik dari event MILIK PENYELENGGARA LAIN, dibuka dengan kode berbagi.
 *
 * Tiga langkah dalam satu modal, dan tidak satu pun menyentuh DB sebelum
 * langkah ketiga: pilih event + ketik kode → pratinjau isi rubriknya → pilih
 * tingkat tujuan milik sendiri, lalu simpan dalam satu transaksi.
 *
 * Kode dinilai SAAT SUBMIT, bukan saat mengetik. Menilainya per ketikan
 * mengubah isian ini jadi oracle: siapa pun bisa menebak kode karakter demi
 * karakter sampai pesannya berubah. Karena itu tidak ada pemeriksaan di
 * `updatedShareCode()` — hanya di verifyAndPreview() dan confirmImport().
 *
 * Yang TIDAK dibocorkan sebelum kode benar: nama kategori, jumlah kriteria,
 * nama juri. `sumberEvents()` sengaja hanya mengambil tiga kolom — ikut
 * menyeleksi `format_share_code` akan mengirim kode berbagi event lain ke
 * browser, dan gerbang ini jadi hiasan.
 */
class ImportLintas extends Component
{
    /**
     * Empat sebab gagal, empat kalimat. Menggabungkannya jadi satu pesan umum
     * membuat panitia menelepon orang yang salah: "kode salah" berarti minta
     * kode ulang, "belum dibagikan" berarti minta dibagikan, "belum disetujui"
     * berarti menunggu.
     */
    public const MSG_KODE_SALAH = 'Kode berbagi tidak cocok dengan event sumber ini.';

    public const MSG_BELUM_DIBAGIKAN = 'Event sumber belum membagikan formatnya.';

    public const MSG_DICABUT = 'Kode berbagi sudah dicabut panitia event sumber.';

    public const MSG_BELUM_DISETUJUI = 'Event sumber belum disetujui, formatnya belum bisa dipakai.';

    /** 1 = pilih event + kode, 2 = pratinjau, 3 = tingkat tujuan + simpan. */
    public int $step = 1;

    /** Tingkat tujuan (dari Builder). '' = belum dipilih. */
    public $activeTab = '';

    /** Id event sumber yang dipilih — divalidasi ulang setiap kali dipakai. */
    public $sourceEventnerId = '';

    /** Kode berbagi yang diketik panitia. Dinilai saat submit. */
    public string $shareCode = '';

    /** Baris tabel pratinjau. */
    public array $previewData = [];

    /** Ringkasan angka + nama sumber/tujuan + nama kategori yang sudah ada. */
    public array $previewMeta = [];

    /** Session key hasil baca, supaya menyimpan tidak perlu membaca ulang. */
    public string $previewSessionKey = '';

    public function mount(string $activeTab = '')
    {
        $this->activeTab = $this->normalizeActiveTab($activeTab);
    }

    public function setActiveTab($id)
    {
        $this->activeTab = $this->normalizeActiveTab($id);
    }

    /**
     * Tingkat tujuan ditulis ke competition_category_id, sedangkan nilainya
     * berasal dari klien. selectable() sekaligus menolak induk ber-anak: bukan
     * tingkat lomba, dan rubriknya tidak akan pernah muncul di halaman penilaian.
     */
    protected function normalizeActiveTab($id): string
    {
        if ($id === '' || $id === null) {
            return '';
        }

        $ada = CompetitionCategory::where('eventner_id', $this->eventnerId())
            ->selectable()
            ->find($id);

        return $ada ? (string) $ada->id : '';
    }

    protected function eventnerId()
    {
        return Auth::user()?->eventner?->id;
    }

    /**
     * Event yang formatnya boleh dibaca: disetujui, dan bukan event sendiri.
     *
     * Hanya tiga kolom. Subdomain ikut karena dua event boleh bernama hampir
     * sama — subdomain yang membedakannya saat panitia saling menelepon.
     */
    public function sumberEvents()
    {
        return Eventner::approved()
            ->where('id', '!=', $this->eventnerId())
            ->orderBy('nama_event')
            ->get(['id', 'nama_event', 'subdomain']);
    }

    /** Tingkat lomba milik event sendiri — sasaran tempel yang sah. */
    public function competitionCategories()
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId())
            ->selectable()
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    public function targetName(): string
    {
        if ($this->activeTab !== '') {
            return CompetitionCategory::where('eventner_id', $this->eventnerId())
                ->find($this->activeTab)?->full_name ?? 'Tingkat (tidak ditemukan)';
        }

        return 'Semua Tingkat (Global)';
    }

    /**
     * Langkah 1 → 2. Kode + status sumber diperiksa di sini, satu tempat.
     *
     * Urutan pemeriksaan penting: status sumber lebih dulu, karena event yang
     * belum disetujui memang belum punya kode untuk dibandingkan — menyebut
     * "kodenya salah" di sana menyesatkan.
     */
    public function verifyAndPreview()
    {
        $this->validate([
            'sourceEventnerId' => ['required'],
            'shareCode' => ['required', 'string', 'max:50'],
        ], [
            'sourceEventnerId.required' => 'Pilih event sumber terlebih dahulu.',
            'shareCode.required' => 'Masukkan kode berbagi dari event sumber.',
        ]);

        $sumber = Eventner::whereKey($this->sourceEventnerId)->first();

        // Event sendiri tidak bisa jadi sumber: menyalin ke diri sendiri hanya
        // menggandakan kategori, bukan memindahkan apa pun.
        if (! $sumber || (int) $sumber->id === (int) $this->eventnerId()) {
            $this->addError('sourceEventnerId', 'Event sumber tidak ditemukan.');

            return;
        }

        $sebab = $this->sebabKodeDitolak($sumber);

        if ($sebab !== null) {
            $this->addError('shareCode', $sebab);

            return;
        }

        $this->resetErrorBag();

        $normalized = FormatNilaiSalin::baca($sumber);
        $ringkas = FormatNilaiSalin::ringkas($normalized);

        $targetLevelId = $this->activeTab !== '' ? (int) $this->activeTab : null;

        $this->previewData = $ringkas['rows'];
        $this->previewMeta = [
            'sourceName' => (string) $sumber->nama_event,
            'sourceSubdomain' => (string) $sumber->subdomain,
            'targetName' => $this->targetName(),
            'rubrikCount' => $ringkas['rubrikCount'],
            'criteriaCount' => $ringkas['criteriaCount'],
            'penguranganCount' => $ringkas['penguranganCount'],
            'deductionCriteriaCount' => $ringkas['deductionCriteriaCount'],
            'namaKategori' => collect($normalized['rubrik'])->pluck('name')->values()->all(),
            // Nama yang sudah dipakai di tingkat tujuan: ditandai, bukan
            // ditolak. Panitia yang tahu mana yang harus dihapus, bukan kode.
            'senama' => FormatNilaiSalin::namaSudahAda((int) $this->eventnerId(), $targetLevelId),
        ];

        $this->previewSessionKey = 'format_nilai_lintas_'.$this->eventnerId().'_'.bin2hex(random_bytes(6));
        session([$this->previewSessionKey => $normalized]);

        $this->step = 2;
    }

    /**
     * Satu-satunya penilai kode. null = diterima.
     */
    private function sebabKodeDitolak(Eventner $sumber): ?string
    {
        if ($sumber->status !== 'approved') {
            return self::MSG_BELUM_DISETUJUI;
        }

        if (! $sumber->format_share_code) {
            return $sumber->format_share_revoked_at
                ? self::MSG_DICABUT
                : self::MSG_BELUM_DIBAGIKAN;
        }

        if (! hash_equals((string) $sumber->format_share_code, $this->shareCode)) {
            return self::MSG_KODE_SALAH;
        }

        return null;
    }

    /** Langkah 2 → 3. Belum menyentuh DB. */
    public function lanjutPilihTingkat()
    {
        if ($this->step !== 2) {
            return;
        }

        $this->step = 3;
    }

    /**
     * Minta konfirmasi sebelum menyimpan.
     *
     * Konfirmasi dilakukan di JS, bukan wire:confirm: di dalam modal Bootstrap
     * wire:confirm selalu membatalkan aksinya, dan tombol Simpan jadi tombol
     * mati yang tampak hidup. Param dikirim NAMED — param posisional sampai di
     * JS sebagai array, dan d.jumlah selalu undefined.
     */
    public function mintaKonfirmasiSimpan()
    {
        $this->dispatch(
            'lintas:simpan',
            jumlah: $this->previewMeta['rubrikCount'] ?? 0,
            asal: $this->previewMeta['sourceName'] ?? 'event sumber',
            tujuan: $this->targetName(),
            komponen: $this->getId(),
        );
    }

    /** Kembali ke langkah 2 dari langkah 3, tanpa kehilangan pratinjau. */
    public function kembaliKePratinjau()
    {
        $this->resetErrorBag('activeTab');

        if ($this->step === 3) {
            $this->step = 2;
        }
    }

    public function closePreview()
    {
        $this->forgetSessionPreview();
        $this->reset('step', 'previewData', 'previewMeta', 'sourceEventnerId', 'shareCode');
        $this->step = 1;
    }

    /**
     * Langkah 3 → tulis. Semua baris menempel ke eventner_id milik pengguna
     * login; tidak satu pun tetap menunjuk ke event sumber.
     *
     * Kode diperiksa ULANG dari nilai hidup di DB, bukan dari hasil langkah 1:
     * antara pratinjau dibuka dan tombol simpan ditekan, panitia sumber bisa
     * mencabut kodenya. Pratinjau lama dibuang supaya isinya tidak tersimpan
     * lewat sesi yang sudah basi — dan supaya panitia tidak menekan Simpan
     * berulang kali di atas sesi mati yang sama.
     */
    public function confirmImport()
    {
        $eventnerId = $this->eventnerId();
        if (! $eventnerId) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $targetLevelId = $this->normalizeActiveTab($this->activeTab);

        // Pengurangan tingkat tidak menempel ke kategori mana pun, jadi ia butuh
        // tingkat pemilik. Menyimpannya ke "Semua Tingkat" akan membuat kelompok
        // itu tak punya tempat — dan membuangnya diam-diam adalah kegagalan yang
        // tampak seperti event sumbernya memang tak punya sanksi tingkat.
        if ($targetLevelId === '') {
            $this->addError('activeTab', 'Pilih tingkat tujuan terlebih dahulu.');

            return;
        }

        if (! $this->previewSessionKey || ! session()->has($this->previewSessionKey)) {
            $this->buangPratinjauLama('Sesi pratinjau berakhir. Silakan buka pratinjau lagi.');

            return;
        }

        $sumber = Eventner::whereKey($this->sourceEventnerId)->first();
        $sebab = $sumber ? $this->sebabKodeDitolak($sumber) : 'Event sumber tidak ditemukan lagi.';

        if ($sebab !== null) {
            $this->buangPratinjauLama($sebab);

            return;
        }

        $normalized = session($this->previewSessionKey);
        $targetCompetitionCategoryId = (int) $targetLevelId;

        if (empty($normalized['rubrik']) && empty($normalized['pengurangan']) && empty($normalized['pengurangan_tingkat'])) {
            $this->buangPratinjauLama('Event sumber belum menyusun format penilaian.');

            return;
        }

        try {
            DB::transaction(function () use ($eventnerId, $normalized, $targetCompetitionCategoryId) {
                // Indeks rubrik → id kategori baru, supaya kelompok pengurangan
                // menempel ke kategori asalnya, bukan ke kategori pertama.
                $rubrikMap = [];

                foreach ($normalized['rubrik'] as $rubrik) {
                    $maxOrder = AssessmentCategory::where('eventner_id', $eventnerId)->max('sort_order') ?? 0;

                    $category = AssessmentCategory::create([
                        'eventner_id' => $eventnerId,
                        'competition_category_id' => $targetCompetitionCategoryId,
                        'name' => strip_tags($rubrik['name']),
                        'sort_order' => $maxOrder + 1,
                    ]);
                    $rubrikMap[] = $category->id;

                    foreach ($rubrik['subCategories'] as $subIndex => $sub) {
                        $newSub = AssessmentSubCategory::create([
                            'assessment_category_id' => $category->id,
                            'name' => strip_tags($sub['name']),
                            'sort_order' => $subIndex + 1,
                        ]);

                        foreach ($sub['criterias'] as $critIndex => $crit) {
                            AssessmentCriteria::create([
                                'assessment_sub_category_id' => $newSub->id,
                                'name' => strip_tags($crit['name']),
                                'score_options' => $crit['score_options'],
                                'weight' => $crit['weight'] >= 0 ? $crit['weight'] : 1,
                                'sort_order' => $critIndex + 1,
                            ]);
                        }
                    }
                }

                foreach ($normalized['pengurangan'] as $dedGroup) {
                    $assessmentCategoryId = $rubrikMap[$dedGroup['rubrik_index']] ?? null;
                    if (! $assessmentCategoryId) {
                        continue;
                    }

                    $this->tulisKelompokPengurangan(
                        $eventnerId,
                        $dedGroup,
                        $targetCompetitionCategoryId,
                        $assessmentCategoryId,
                        DeductionCategory::SCOPE_CATEGORY
                    );
                }

                // "Pengurangan Nilai Tingkat" milik sumber ikut tersalin.
                // Tanpanya salinan tampak utuh padahal sanksi tingkatnya hilang.
                foreach ($normalized['pengurangan_tingkat'] ?? [] as $dedGroup) {
                    $this->tulisKelompokPengurangan(
                        $eventnerId,
                        $dedGroup,
                        $targetCompetitionCategoryId,
                        null,
                        DeductionCategory::SCOPE_GLOBAL
                    );
                }
            });
        } catch (\Throwable $e) {
            $this->addError('shareCode', 'Gagal menyimpan salinan: '.$e->getMessage());

            return;
        }

        $meta = $this->previewMeta;
        $sumberName = $meta['sourceName'] ?? 'event sumber';
        $targetName = $meta['targetName'] ?? 'tingkat tujuan';

        $this->forgetSessionPreview();
        $this->reset('step', 'previewData', 'previewMeta', 'sourceEventnerId', 'shareCode');
        $this->step = 1;

        $message = ($meta['criteriaCount'] ?? 0)." kriteria dari {$sumberName} ditambahkan ke {$targetName}.";
        $this->dispatch('import:done', message: $message);
        session()->flash('success', $message);
    }

    /**
     * Pratinjau mati di tengah jalan: buang isinya, kembalikan ke langkah 1,
     * dan taruh sebabnya di tempat panitia akan melihatnya.
     */
    private function buangPratinjauLama(string $pesan): void
    {
        $this->forgetSessionPreview();
        $this->reset('step', 'previewData', 'previewMeta');
        $this->step = 1;
        $this->addError('shareCode', $pesan);
    }

    /**
     * Satu lingkar tulis untuk kedua cakupan pengurangan — bedanya cuma kolom
     * penempelnya, dan menuliskannya dua kali membuat salah satunya pasti
     * ketinggalan saat aturan berubah.
     */
    private function tulisKelompokPengurangan(
        int $eventnerId,
        array $dedGroup,
        int $targetCompetitionCategoryId,
        ?int $assessmentCategoryId,
        string $scope
    ): void {
        $dedMaxOrder = DeductionCategory::where('eventner_id', $eventnerId)->max('sort_order') ?? 0;

        $dedCat = DeductionCategory::create([
            'eventner_id' => $eventnerId,
            'assessment_category_id' => $assessmentCategoryId,
            'competition_category_id' => $targetCompetitionCategoryId,
            'scope' => $scope,
            'name' => strip_tags($dedGroup['name']),
            'sort_order' => $dedMaxOrder + 1,
        ]);

        foreach ($dedGroup['criterias'] as $critIndex => $dedCrit) {
            DeductionCriteria::create([
                'deduction_category_id' => $dedCat->id,
                'name' => strip_tags($dedCrit['name']),
                'deduction_options' => $dedCrit['deduction_options'],
                'sort_order' => $critIndex + 1,
            ]);
        }
    }

    private function forgetSessionPreview()
    {
        if ($this->previewSessionKey) {
            session()->forget($this->previewSessionKey);
            $this->previewSessionKey = '';
        }
    }

    public function render()
    {
        return view('livewire.eventner.format-nilai.import-lintas');
    }
}
