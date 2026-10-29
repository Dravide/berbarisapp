<?php

namespace App\Services;

use App\Models\ChampionCategory;
use App\Models\Registration;

/**
 * Sumber tunggal gelar sertifikat: registrasi ini juara apa, dan dengan
 * peringkat berapa.
 *
 * Sebelumnya aturan ini hidup di dua tempat yang menyalin satu sama lain —
 * CertificateController::downloadCertificateByToken dan
 * MagicLink\Registration::getCertificateRegistrationsProperty — sehingga
 * sertifikat yang diunduh dari portal bisa berbeda gelarnya dari badge di
 * halaman yang sama. Perhitungan peringkatnya sendiri tetap milik
 * ChampionCalculator; kelas ini hanya menjawab "sekolah ini menang di kategori
 * juara yang mana".
 */
class SertifikatKontingen
{
    /**
     * Hasil perhitungan per (kategori juara × mata lomba × grup) di dalam satu
     * permintaan. Satu perhitungan menyentuh seluruh peserta satu mata lomba,
     * jadi memanggilnya sekali per baris hasil pencarian berarti mengulang
     * pekerjaan yang sama untuk setiap sertifikat yang ditampilkan.
     *
     * @var array<string, array>
     */
    private array $cacheKemenangan = [];

    public function __construct(private ChampionCalculator $calculator) {}

    /**
     * Kemenangan satu registrasi — daftar, bukan satu nilai.
     *
     * Satu pasukan bisa masuk jajaran juara di lebih dari satu kategori juara
     * (mis. "Juara Umum" dan "Juara Favorit" dari rubrik berbeda), dan tiap
     * kemenangan menghasilkan halaman sertifikatnya sendiri. Yang mengembalikan
     * nilai pertama saja akan membuang salah satunya diam-diam.
     *
     * @return array<int, array{rank: int, title: string, total: float, championCategory: ChampionCategory}>
     */
    public function kemenangan(Registration $reg): array
    {
        $eventner = $reg->eventner;

        if (! $eventner) {
            return [];
        }

        $schoolKey = $this->kunciSekolah($reg);

        /** @var \Illuminate\Support\Collection<int, ChampionCategory> $championCategories */
        $championCategories = $this->kategoriJuaraRelevan($eventner->id, $reg);

        $hasil = [];

        foreach ($championCategories as $championCategory) {
            foreach ($this->winners($championCategory, $reg->competition_category_id, $reg->competition_group_id) as $winner) {
                /** @var Registration $regMenang */
                $regMenang = $winner['registration'];

                if ($this->kunciSekolah($regMenang) !== $schoolKey) {
                    continue;
                }

                // Gelar memakai nomor posisi dalam grup rank title bila
                // rentangnya meng-cover lebih dari satu peringkat — sama
                // dengan halaman /hasil.
                $title = $championCategory->titleForRank($winner['rank']);

                $hasil[] = [
                    'rank' => $winner['rank'],
                    'title' => $title ?: 'Juara ' . $winner['rank'],
                    'total' => $winner['total'],
                    'championCategory' => $championCategory,
                ];
            }
        }

        return $hasil;
    }

    /**
     * Kategori juara yang rubriknya berlaku untuk mata lomba + grup registrasi
     * ini. Kategori tingkat lain tidak ikut — pasukan SD tidak boleh menerima
     * gelar dari rubrik SMA.
     *
     * Publik karena pemanggil yang merender sertifikat PESERTA butuh SATU
     * kategori juara untuk mengisi field `kategori_juara` dan judul berkas;
     * tanpa itu keduanya jatuh ke null padahal halamannya tetap harus terbit.
     *
     * @return \Illuminate\Support\Collection<int, ChampionCategory>
     */
    public function kategoriJuaraRelevan(int $eventnerId, Registration $reg): \Illuminate\Support\Collection
    {
        return ChampionCategory::where('eventner_id', $eventnerId)
            ->with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias', 'criterias', 'tiebreakCriterias'])
            ->get()
            ->filter(fn ($cc) => $cc->isVisibleFor($reg->competition_category_id, $reg->competition_group_id))
            ->values();
    }

    /**
     * Peringkat juara satu kategori lomba + grup, di-cache per permintaan.
     *
     * @return array<int, array{registration: Registration, rank: int, title: string, total: float}>
     */
    private function winners(ChampionCategory $championCategory, $competitionCategoryId, $competitionGroupId): array
    {
        $kunci = implode(':', [
            $championCategory->id,
            $competitionCategoryId ?? '-',
            $competitionGroupId ?? '-',
        ]);

        return $this->cacheKemenangan[$kunci] ??= $this->calculator
            ->winners($championCategory, $competitionCategoryId, $competitionGroupId)[2];
    }

    /**
     * Identitas sekolah: NPSN bila ada, kalau tidak nama yang dinormalkan.
     *
     * NPSN adalah identitas sekolah, dan satu sekolah boleh punya beberapa
     * pasukan ("… (Regu B)") — jadi kuncinya NPSN. Kolom itu nullable sejak
     * 2026_09_21, dan `= NULL` tak pernah cocok di MySQL, jadi baris tanpa NPSN
     * dicocokkan lewat namanya.
     */
    public function kunciSekolah(Registration $reg): string
    {
        return $reg->npsn ?: mb_strtolower(trim((string) $reg->nama_sekolah));
    }

    /**
     * Susun halaman sertifikat satu registrasi untuk satu kategori juara.
     *
     * Kategori juara sengaja jadi parameter dan BUKAN diambil dari
     * kemenangan(): jalur PESERTA tidak punya kemenangan sama sekali, tapi
     * halamannya tetap harus terbit dengan field `kategori_juara` dan judul
     * berkas yang terisi. Pemanggil yang sudah tahu kategori juara mana yang
     * dimaksud (unduhan panitia per kategori) mengirimnya; yang hanya tahu
     * mata lomba (laman publik, portal) membiarkan gantinya dicari.
     *
     * @return array{0: ?ChampionCategory, 1: array<int, array>}
     */
    public function halamanUntukKategori(
        Registration $reg,
        ?ChampionCategory $championCategory,
        CertificatePdfBuilder $builder,
    ): array {
        $kategoriRelevan = $this->kategoriJuaraRelevan($reg->eventner_id, $reg);

        // Tanpa kategori juara eksplisit, pakai yang pertama relevan — field
        // `kategori_juara` sertifikat PESERTA harus terisi sesuatu selama
        // event ini memang punya kategori juara.
        $championCategory ??= $kategoriRelevan->first();

        if ($championCategory) {
            foreach ($this->kemenangan($reg) as $k) {
                if ($k['championCategory']->id === $championCategory->id) {
                    return [$championCategory, $builder->pagesForRegistration($reg, $k)];
                }
            }
        }

        return [$championCategory, $builder->pagesForRegistration($reg)];
    }
}
