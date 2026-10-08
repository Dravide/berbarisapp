<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Registration;
use App\Support\ScoreOptions;

/**
 * Perbandingan nilai antar juri satu tingkat lomba.
 *
 * ── Kenapa satuannya SEL, bukan juri ────────────────────────────────────
 *
 * Sejak rubrik boleh dibagi antar juri (assessment_category_judge, lihat
 * AssessmentCategory::bolehDinilaiOleh()), TOTAL PER JURI SUDAH TIDAK SETARA.
 * Juri A memegang kriteria berskala 0–25, juri B memegang 0–100; menjumlahkan
 * keduanya lalu menyimpulkan "juri B terlalu tinggi" berarti membandingkan dua
 * pekerjaan yang berbeda. Panel Input Nilai sudah mencatatnya sendiri: yang
 * tetap setara dengan ChampionCalculator adalah "Jumlah Semua Juri".
 *
 * Karena itu satuan analisis di sini adalah SEL = peserta × kriteria. Unique
 * index unique_score_participant_criteria_judge menjamin satu sel berisi paling
 * banyak satu nilai per juri, jadi sel ini tepat — bukan agregat yang bisa
 * salah dijumlahkan. Juri hanya dibandingkan terhadap juri lain di dalam sel
 * yang MEREKA BERDUA isi; dua juri tanpa satu pun sel beririsan tidak
 * dibandingkan sama sekali, dan itu dikatakan apa adanya.
 *
 * ── Kenapa bobot kriteria tidak dipakai ─────────────────────────────────
 *
 * Bobot ($crit->weight) adalah sifat KRITERIA, bukan sifat juri. Memasukkannya
 * membuat juri yang kebetulan memegang kriteria berbobot besar terlihat "lebih
 * tinggi" padahal ia menilai sama seperti rekannya. Bobot tetap hidup di
 * ScoreRecapBuilder dan ChampionCalculator, tempat ia memang berhak.
 *
 * Skalanya disetarakan lewat porsi (x / batas atas opsi kriteria), lalu
 * ditampilkan kembali dalam poin supaya tetap terbaca panitia.
 *
 * Dipisah dari komponen Livewire dengan alasan yang sama seperti
 * ScoreRecapBuilder: seluruh matematikanya bisa diuji tanpa HTTP, dan
 * pembaca berikutnya (PDF/CSV) tinggal memanggilnya tanpa menyalin rumus.
 */
class JudgeScoreComparison
{
    /** Batas "seimbang": 5% skala. */
    public const AMBANG_BIAS = 0.05;

    /** Batas konsistensi: 10% skala. */
    public const AMBANG_SIGMA = 0.10;

    /** Sel ditandai kalau rentang nilai antar jurinya lewat 20% skala. */
    public const AMBANG_RENTANG_SEL = 0.20;

    /** Juri dengan kurang dari ini sel pembanding tidak diberi putusan. */
    public const MIN_SEL = 5;

    /** Batas atas skala cadangan saat opsi kriteria tak memuat angka. */
    private const SKALA_CADANGAN = 100;

    /** Kelas juri. */
    public const KELAS_TINGGI = 'Cenderung tinggi';
    public const KELAS_RENDAH = 'Cenderung rendah';
    public const KELAS_TAK_KONSISTEN = 'Tak konsisten';
    public const KELAS_SEIMBANG = 'Seimbang';
    public const KELAS_KURANG = 'Data belum cukup';
    public const KELAS_TAK_BISA = 'Tidak dapat dibandingkan';

    /** Kelas, diurutkan dari yang paling perlu ditinjau. */
    private const URUTAN_KELAS = [
        self::KELAS_TINGGI => 0,
        self::KELAS_RENDAH => 1,
        self::KELAS_TAK_KONSISTEN => 2,
        self::KELAS_SEIMBANG => 3,
        self::KELAS_KURANG => 4,
        self::KELAS_TAK_BISA => 5,
    ];

    /**
     * @param  ?int|string  $groupId  '' / null = seluruh tingkat.
     * @return array{
     *     juri: array, kriteria: array, sel: array, temuan: array,
     *     ringkas: array, keadaan: string, punya_data: bool
     * }
     */
    public function build(Eventner $eventner, int $categoryId, ?int $roundId = null, $groupId = null): array
    {
        $groupId = ($groupId === null || $groupId === '') ? null : (int) $groupId;

        $peserta = Registration::with('competitionSeries')
            ->where('eventner_id', $eventner->id)
            ->where('competition_category_id', $categoryId)
            ->when($groupId !== null, fn ($q) => $q->where('competition_group_id', $groupId))
            ->orderBy('nama_sekolah')
            ->get();

        if ($peserta->isEmpty()) {
            return $this->hasilKosong('tanpa_peserta');
        }

        // ── Juri per peserta + rubrik yang boleh mereka isi ──────────────
        //
        // rubrikUntukPeserta() dipanggil PER (juri, seri): pintu yang sama
        // dengan tablet juri dan ScoreFinalizationService. Rubrik berseri hanya
        // berlaku untuk peserta berseri itu, dan babak final mengabaikan seri —
        // keduanya sudah jadi aturan di scopeForEntry(), jadi tak perlu cabang
        // baru di sini. Hasilnya di-memo per (juri, seri): satu juri yang
        // menilai sepuluh peserta satu seri cukup dihitung sekali.
        $rubrikCache = [];
        $juriPerPeserta = [];
        $penugasanPerPeserta = [];
        $semuaJuri = [];
        $sel = [];

        foreach ($peserta as $reg) {
            $juri = CompetitionGroup::judgesForRegistration($reg, $roundId);

            if ($juri->isEmpty()) {
                continue;
            }

            $juriPerPeserta[$reg->id] = $juri->pluck('id')->all();
            $penugasanPerPeserta[$reg->id] = CompetitionGroup::penugasanUntukPeserta($reg, $roundId);

            foreach ($juri as $j) {
                $semuaJuri[$j->id] = $j;

                $cacheKey = $j->id . ':' . ($reg->competition_series_id ?? '-');

                if (! isset($rubrikCache[$cacheKey])) {
                    $rubrikCache[$cacheKey] = AssessmentCategory::rubrikUntukPeserta(
                        $eventner->id,
                        $reg->competition_category_id,
                        $reg->competition_series_id,
                        $roundId,
                        (int) $j->id,
                    )->get();
                }

                foreach ($rubrikCache[$cacheKey] as $kat) {
                    foreach ($kat->subCategories as $sub) {
                        foreach ($sub->criterias as $crit) {
                            $key = $reg->id . ':' . $crit->id;

                            if (! isset($sel[$key])) {
                                $sel[$key] = [
                                    'registration_id' => $reg->id,
                                    'peserta' => $reg->nama_sekolah,
                                    'seri' => $reg->competitionSeries?->name,
                                    'criteria' => $crit,
                                    'kategori' => $kat->name,
                                    'penugasan' => $penugasanPerPeserta[$reg->id],
                                    'skala' => $this->skala($crit),
                                    'boleh' => [],
                                    'nilai' => [],
                                ];
                            }

                            // Sel ini masuk tugas juri itu, walau belum ia isi.
                            // Tanpa penanda ini kelengkapan tak bisa dihitung
                            // dan juri yang absen tampak "tak punya tugas".
                            $sel[$key]['boleh'][$j->id] = true;
                        }
                    }
                }
            }
        }

        if ($sel === []) {
            // Tiga sebab berbeda, tiga pesan berbeda. "Belum ada peserta" untuk
            // tingkat yang pesertanya ada tapi jurinya belum ditugaskan akan
            // mengirim panitia memeriksa daftar peserta yang sudah benar;
            // "belum ada nilai" untuk tingkat yang rubriknya belum dibuat akan
            // mengirim mereka menunggu juri yang sebenarnya sudah siap.
            return $this->hasilKosong(match (true) {
                $juriPerPeserta === [] => 'tanpa_juri',
                default => 'tanpa_rubrik',
            });
        }

        // ── Nilai tersimpan ─────────────────────────────────────────────
        //
        // Dibaca lewat ScoreOptions::value() — assessment_scores.score bertipe
        // varchar dan opsinya bebas diketik, jadi cast (int) membuang pecahan
        // diam-diam (lihat catatan di ScoreOptions).
        $baris = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $peserta->pluck('id'))
            ->when($semuaJuri !== [], fn ($q) => $q->whereIn('judge_id', array_keys($semuaJuri)))
            ->get();

        $adaNilai = false;

        foreach ($baris as $s) {
            $key = $s->registration_id . ':' . $s->assessment_criteria_id;

            // Nilai atas sel yang bukan tugas juri itu dibuang di sini. Saring
            // ini yang menegakkan rubrik, babak, dan seri sekaligus — termasuk
            // baris sisa dari sebelum rubriknya dibagi.
            if (! isset($sel[$key])) {
                continue;
            }

            $x = ScoreOptions::value($s->score);

            if ($x == 0.0) {
                continue;
            }

            $adaNilai = true;
            $sel[$key]['nilai'][(int) $s->judge_id] = [
                'raw' => $x,
                'porsi' => $x / $sel[$key]['skala'],
            ];
        }

        if (! $adaNilai) {
            return $this->hasilKosong('tanpa_nilai');
        }

        // ── Statistik ───────────────────────────────────────────────────
        $sel = array_values($sel);
        $juriStats = $this->hitungJuri($sel, $semuaJuri, $juriPerPeserta, $penugasanPerPeserta);
        $kriteriaStats = $this->hitungKriteria($sel);
        $temuan = $this->susunTemuan($juriStats, $sel);

        $dibandingkan = collect($juriStats)->where('pembanding', '>=', self::MIN_SEL)->count();
        $selDitandai = collect($sel)->where('flag', true)->count();
        $selDibandingkan = collect($sel)->where('juri_terisi', '>=', 2)->count();

        // Dua keadaan kosong yang berbeda sebab. "Belum ada data" untuk
        // keduanya membuat panitia mencari masalah di tempat yang salah:
        // "belum ada nilai sama sekali" bukan "nilai lengkap tapi tiap juri
        // memegang kriteria sendiri".
        $keadaan = ($selDitandai === 0 && $dibandingkan === 0)
            ? 'tanpa_pembanding'
            : 'ok';

        return [
            'juri' => $juriStats,
            'kriteria' => $kriteriaStats,
            'sel' => $sel,
            'temuan' => $temuan,
            'ringkas' => [
                'juri_dibandingkan' => $dibandingkan,
                'juri_tak_dibandingkan' => count($juriStats) - $dibandingkan,
                'juri_total' => count($juriStats),
                'sel_total' => count($sel),
                'sel_terisi' => collect($sel)->where('juri_terisi', '>=', 1)->count(),
                'sel_dibandingkan' => $selDibandingkan,
                'sel_ditandai' => $selDitandai,
                'kriteria_total' => count($kriteriaStats),
            ],
            'keadaan' => $keadaan,
            'punya_data' => true,
        ];
    }

    /**
     * Statistik per juri + penandaan tiap sel.
     *
     * d = porsi juri ini − rata-rata porsi sel itu. Rata-rata SEL, bukan
     * rata-rata juri: yang dicari "apakah ia menyimpang dari rekannya di
     * penilaian yang sama", bukan "apakah nilainya besar".
     *
     * Statistik dan penandaan dihitung dalam satu lintasan karena keduanya
     * butuh rata-rata sel yang sama — memisahkannya berarti menghitung ulang.
     *
     * $sel lewat REFERENSI: penandaan (rerata, rentang, flag, menyimpang)
     * dituliskan ke dalam sel itu sendiri, dan pemanggillah yang mengembalikan
     * sel bertanda itu. Lewat nilai, tabel "Sel Berselisih" akan selalu kosong.
     */
    private function hitungJuri(array &$sel, $semuaJuri, array $juriPerPeserta, array $penugasanPerPeserta): array
    {
        $d = [];
        $tugas = [];

        foreach ($sel as &$s) {
            foreach (array_keys($s['boleh']) as $judgeId) {
                $tugas[$judgeId] = ($tugas[$judgeId] ?? 0) + 1;
            }

            $nilai = $s['nilai'];
            $jumlah = count($nilai);
            $s['juri_terisi'] = $jumlah;

            if ($jumlah < 2) {
                continue;
            }

            $porsi = array_map(fn ($v) => $v['porsi'], $nilai);
            $rerata = array_sum($porsi) / $jumlah;
            $rentang = max($porsi) - min($porsi);

            // Juri yang paling jauh dari rata-rata sel. Dua juri simetris di
            // sekitar rerata (mis. 10 vs 25 di skala 25 — rerata 17,5) berarti
            // tak ada yang lebih menyimpang; memaksa memilih satu akan
            // menuduh juri yang nilainya justru sama jauhnya dari yang lain.
            $menyimpang = null;
            $jauh = -1.0;
            foreach ($nilai as $judgeId => $v) {
                $jarak = abs($v['porsi'] - $rerata);
                if ($jarak > $jauh + 1e-9) {
                    $jauh = $jarak;
                    $menyimpang = (int) $judgeId;
                } elseif (abs($jarak - $jauh) <= 1e-9) {
                    $menyimpang = null;
                }
            }

            $s['rerata'] = $rerata;
            $s['rentang'] = $rentang;
            $s['menyimpang'] = $menyimpang;
            $s['flag'] = $rentang > self::AMBANG_RENTANG_SEL;

            foreach ($nilai as $judgeId => $v) {
                $d[$judgeId][] = $v['porsi'] - $rerata;
            }
        }
        unset($s);

        $juriStats = [];

        foreach ($semuaJuri as $judgeId => $juri) {
            $deret = $d[$judgeId] ?? [];
            $pembanding = count($deret);

            // Angka bias baru berarti setelah sel pembandingnya cukup. Di bawah
            // MIN_SEL rata-rata dua sel bisa jadi +30% hanya karena satu sel
            // ekstrem, dan menampilkan angka itu mengundang panitia menyimpulkan
            // dari derau. Kelas "Data belum cukup" saja, tanpa angka.
            $cukup = $pembanding >= self::MIN_SEL;

            $bias = $cukup ? array_sum($deret) / $pembanding : null;
            $sigma = $cukup ? $this->simpanganBaku($deret) : null;

            [$kelas, $konsisten] = $this->kelasJuri($bias, $sigma, $pembanding);

            $dinilai = 0;
            foreach ($sel as $s) {
                if (isset($s['nilai'][$judgeId])) {
                    $dinilai++;
                }
            }

            $juriStats[] = [
                'judge' => $juri,
                'penugasan' => $this->labelPenugasan($judgeId, $juriPerPeserta, $penugasanPerPeserta),
                'tugas' => $tugas[$judgeId] ?? 0,
                'dinilai' => $dinilai,
                'pembanding' => $pembanding,
                'bias' => $bias,
                'sigma' => $sigma,
                'selisih_ekstrem' => $cukup ? max(array_map('abs', $deret)) : null,
                'kelas' => $kelas,
                'konsisten' => $konsisten,
            ];
        }

        usort($juriStats, function ($a, $b) {
            $pa = self::URUTAN_KELAS[$a['kelas']] ?? 9;
            $pb = self::URUTAN_KELAS[$b['kelas']] ?? 9;

            return $pa <=> $pb ?: strcmp((string) $a['judge']->name, (string) $b['judge']->name);
        });

        return $juriStats;
    }

    /**
     * Kelas satu juri. Dievaluasi berurutan — urutannya bagian dari aturannya.
     *
     * "bias" dan "konsisten" sengaja dipisah. Juri bisa SELALU lebih tinggi 8%
     * (bias besar, sebaran kecil — kecenderungan yang bisa dikoreksi dengan
     * sadar) atau ACAK (bias kecil, sebaran besar). Satu putusan yang mencampur
     * keduanya menghapus perbedaan itu.
     *
     * @return array{0: string, 1: bool|null}
     */
    private function kelasJuri(?float $bias, ?float $sigma, int $pembanding): array
    {
        if ($pembanding === 0) {
            return [self::KELAS_TAK_BISA, null];
        }

        if ($pembanding < self::MIN_SEL) {
            return [self::KELAS_KURANG, null];
        }

        $konsisten = $sigma !== null && $sigma <= self::AMBANG_SIGMA;

        if (abs($bias) <= self::AMBANG_BIAS) {
            return [$konsisten ? self::KELAS_SEIMBANG : self::KELAS_TAK_KONSISTEN, $konsisten];
        }

        return [$bias > 0 ? self::KELAS_TINGGI : self::KELAS_RENDAH, $konsisten];
    }

    /**
     * Sebaran tiap kriteria.
     *
     * Rentang diukur dalam PORSI skala, bukan poin: rentang 11 poin di skala
     * 25 berarti 44%, di skala 100 cuma 11%. Tanpa normalisasi ini kriteria
     * berskala besar selalu terlihat paling bermasalah.
     *
     * Bobot kriteria sengaja tidak muncul di sini — lihat catatan kelas.
     */
    private function hitungKriteria(array $sel): array
    {
        $kelompok = [];

        foreach ($sel as $s) {
            if ($s['nilai'] === []) {
                continue;
            }

            $id = $s['criteria']->id;
            $kelompok[$id] ??= [
                'criteria' => $s['criteria'],
                'kategori' => $s['kategori'],
                'skala' => $s['skala'],
                'porsi' => [],
            ];

            foreach ($s['nilai'] as $v) {
                $kelompok[$id]['porsi'][] = $v['porsi'];
            }
        }

        $hasil = [];

        foreach ($kelompok as $k) {
            $p = $k['porsi'];
            $n = count($p);
            sort($p);

            $min = $p[0];
            $maks = $p[$n - 1];
            $rentang = $maks - $min;

            $hasil[] = [
                'criteria' => $k['criteria'],
                'kategori' => $k['kategori'],
                'skala' => $k['skala'],
                'n' => $n,
                'rerata' => array_sum($p) / $n,
                'median' => $n % 2 === 1
                    ? $p[intdiv($n, 2)]
                    : (($p[$n / 2 - 1] + $p[$n / 2]) / 2),
                'min' => $min,
                'maks' => $maks,
                'rentang' => $rentang,
                'sigma' => $this->simpanganBaku($p),
                'flag' => $rentang > self::AMBANG_RENTANG_SEL,
            ];
        }

        usort($hasil, fn ($a, $b) => $b['rentang'] <=> $a['rentang']);

        return $hasil;
    }

    /**
     * Temuan yang bisa dibaca panitia: apa yang terlihat, di mana, dan berapa
     * sel yang mendasarinya.
     *
     * Sengaja bukan putusan. Tidak ada "salah", "curang", atau "tidak
     * kompeten" — yang keluar hanya kecenderungan, plus jumlah selnya supaya
     * panitia bisa menilai sendiri.
     */
    private function susunTemuan(array $juriStats, array $sel): array
    {
        $temuan = [];

        foreach ($juriStats as $j) {
            if (in_array($j['kelas'], [self::KELAS_TINGGI, self::KELAS_RENDAH], true)) {
                $temuan[] = [
                    'tingkat' => 'perhatian',
                    'teks' => sprintf(
                        '%s %s: rata-rata %.1f%% dari skala.%s',
                        $j['judge']->name,
                        mb_strtolower($j['kelas']),
                        abs($j['bias']) * 100,
                        $j['konsisten'] === false ? ' Sebarannya juga lebar.' : '',
                    ),
                ];

                continue;
            }

            if ($j['kelas'] === self::KELAS_TAK_KONSISTEN) {
                $temuan[] = [
                    'tingkat' => 'perhatian',
                    'teks' => sprintf(
                        '%s tidak konsisten: rata-ratanya seimbang, tapi sebarannya %.1f%% dari skala.',
                        $j['judge']->name,
                        $j['sigma'] * 100,
                    ),
                ];
            }
        }

        $ditandai = collect($sel)->where('flag', true)->count();
        $dibandingkan = collect($sel)->where('juri_terisi', '>=', 2)->count();

        if ($ditandai > 0) {
            $temuan[] = [
                'tingkat' => 'perhatian',
                'teks' => sprintf(
                    '%d dari %d sel punya selisih antar juri di atas %d%% skala.',
                    $ditandai,
                    $dibandingkan,
                    (int) round(self::AMBANG_RENTANG_SEL * 100),
                ),
            ];
        }

        $takBisa = collect($juriStats)->where('kelas', self::KELAS_TAK_BISA)->count();
        if ($takBisa > 0) {
            $temuan[] = [
                'tingkat' => 'info',
                'teks' => sprintf(
                    '%d juri tidak memegang satu pun kriteria bersama juri lain, jadi tidak bisa dibandingkan. Itu pembagian rubrik, bukan selisih nilai.',
                    $takBisa,
                ),
            ];
        }

        $kurang = collect($juriStats)->where('kelas', self::KELAS_KURANG)->count();
        if ($kurang > 0) {
            $temuan[] = [
                'tingkat' => 'info',
                'teks' => sprintf(
                    '%d juri punya kurang dari %d sel pembanding — terlalu sedikit untuk disimpulkan.',
                    $kurang,
                    self::MIN_SEL,
                ),
            ];
        }

        return $temuan;
    }

    /**
     * Baris penugasan juri ini, disimpulkan dari peserta yang menampilkannya.
     *
     * Satu juri bisa muncul di beberapa baris (mis. juri final yang juga
     * memegang grup). Kalau begitu labelnya menyebut keduanya, bukan memilih
     * salah satu — memilih satu akan menyembunyikan penugasan yang nyata.
     *
     * @param  array<int, array<int>>  $juriPerPeserta
     * @param  array<int, array{scope: ?string, group_id: ?int}>  $penugasanPerPeserta
     */
    private function labelPenugasan(int $judgeId, array $juriPerPeserta, array $penugasanPerPeserta): string
    {
        $grupIds = [];
        $scope = null;

        foreach ($juriPerPeserta as $regId => $juriIds) {
            if (! in_array($judgeId, $juriIds, true)) {
                continue;
            }

            $t = $penugasanPerPeserta[$regId] ?? ['scope' => null, 'group_id' => null];

            if ($t['scope'] === CompetitionGroup::SCOPE_GROUP && $t['group_id'] !== null) {
                $grupIds[$t['group_id']] = true;
            } elseif ($t['scope'] !== null) {
                $scope = $t['scope'];
            }
        }

        $label = [];

        if ($grupIds !== []) {
            $nama = CompetitionGroup::whereIn('id', array_keys($grupIds))
                ->orderBy('name')->pluck('name')->all();
            $label[] = 'Grup ' . implode(', ', $nama);
        }

        if ($scope !== null) {
            $label[] = CompetitionGroup::SCOPE_LABELS[$scope] ?? ucfirst($scope);
        }

        return $label === [] ? '—' : implode(' + ', $label);
    }

    /** Batas atas skala satu kriteria; 0 jatuh ke cadangan. */
    private function skala(?AssessmentCriteria $criteria): int
    {
        if (! $criteria) {
            return self::SKALA_CADANGAN;
        }

        $maks = ScoreOptions::maxValue($criteria->score_options ?? []);

        return $maks > 0 ? $maks : self::SKALA_CADANGAN;
    }

    /**
     * Simpangan baku populasi.
     *
     * Populasi, bukan sampel: himpunan sel pembanding juri ini adalah seluruh
     * datanya, bukan sampel dari sesuatu yang lebih besar.
     */
    private function simpanganBaku(array $deret): float
    {
        $n = count($deret);

        if ($n === 0) {
            return 0.0;
        }

        $rerata = array_sum($deret) / $n;
        $jumlah = 0.0;

        foreach ($deret as $v) {
            $jumlah += ($v - $rerata) ** 2;
        }

        return sqrt($jumlah / $n);
    }

    private function hasilKosong(string $keadaan): array
    {
        return [
            'juri' => [],
            'kriteria' => [],
            'sel' => [],
            'temuan' => [],
            'ringkas' => [
                'juri_dibandingkan' => 0,
                'juri_tak_dibandingkan' => 0,
                'juri_total' => 0,
                'sel_total' => 0,
                'sel_terisi' => 0,
                'sel_dibandingkan' => 0,
                'sel_ditandai' => 0,
                'kriteria_total' => 0,
            ],
            'keadaan' => $keadaan,
            'punya_data' => false,
        ];
    }
}
