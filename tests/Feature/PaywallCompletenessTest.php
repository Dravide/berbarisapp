<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Layout\Index as LayoutIndex;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\SaasPlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Kelengkapan paywall: config, paket di DB, dan halaman yang menuntut gate.
 *
 * Bug yang melahirkan tes ini bukan bug kode. config/eventner_features.php
 * bertambah tiga kunci (`layout`, `schedule`, `vote_comments`) SETELAH paket
 * "Event Penuh" disemai, jadi baris saas_plan_feature-nya tak pernah ikut
 * tersalin. canAccessFeature() untuk ketiganya lalu mengembalikan false bagi
 * semua orang — termasuk yang sudah membayar — dan halaman yang menuntutnya
 * melempar pengguna ke /upgrade. Tak ada tes unit yang bisa melihat itu:
 * fungsinya benar, datanya yang bolong.
 *
 * Karena itu tiga arah yang dijaga di sini:
 *
 * 1. Setiap kunci config ber-locked_free=true dipegang minimal satu paket
 *    berbayar (kalau tidak, tak seorang pun bisa membukanya).
 * 2. Setiap baris saas_plan_feature menunjuk kunci yang masih ada di config
 *    (kebalikannya: kunci yang dihapus dari config meninggalkan sampah).
 * 3. Setiap `requiredFeature` yang dipakai komponen Livewire benar-benar
 *    terdaftar di config — salah ketik di sini mengunci halaman selamanya,
 *    dan tak ada gejala lain selain "halaman ini selalu minta upgrade".
 */
class PaywallCompletenessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Gate yang DITUNTUT komponen tapi SENGAJA di luar config paywall.
     *
     * config/eventner_features.php hanya memuat fitur yang boleh dikunci, jadi
     * kunci di luar sana tak pernah menutup halaman — lihat cabang pertama
     * canAccessFeature(). `activity_log` memang begitu: log aktivitas harus
     * terbaca semua paket, termasuk yang gratis. Tes di bawah gagal kalau ada
     * kunci lain menyusul tanpa keputusan sadar.
     */
    private const GATE_SELALU_TERBUKA = ['activity_log'];

    /**
     * Kunci config yang boleh dikunci.
     *
     * @return array<int, string>
     */
    private function kunciTerkunci(): array
    {
        return collect(config('eventner_features', []))
            ->filter(fn ($c) => $c['locked_free'] ?? true)
            ->keys()
            ->all();
    }

    public function test_setiap_kunci_terkunci_dipegang_paket_berbayar(): void
    {
        $dipegang = SaasPlanFeature::query()
            ->whereIn('saas_plan_id', SaasPlan::where('is_free', false)->pluck('id'))
            ->pluck('feature_key')
            ->unique()
            ->all();

        $yatim = array_values(array_diff($this->kunciTerkunci(), $dipegang));

        $this->assertSame(
            [],
            $yatim,
            'Kunci config ini tak dipegang paket berbayar mana pun, jadi tak bisa dibuka siapa pun: '
                . implode(', ', $yatim)
        );
    }

    public function test_setiap_baris_fitur_paket_menunjuk_kunci_yang_masih_ada(): void
    {
        $dariConfig = array_keys(config('eventner_features', []));

        foreach (SaasPlan::with('features')->get() as $plan) {
            $asing = array_values(array_diff($plan->featureKeys(), $dariConfig));

            $this->assertSame(
                [],
                $asing,
                "Paket \"{$plan->name}\" memegang fitur yang tak ada di config/eventner_features.php: "
                    . implode(', ', $asing)
            );
        }
    }

    /**
     * Arah ketiga: salah ketik nama gate.
     *
     * `requiredFeature` yang tidak ada di config BUKAN halaman terkunci — ia
     * halaman yang tak terkunci sama sekali, karena canAccessFeature() menang
     * lebih dulu di cabang "tidak terdaftar". Jadi salah ketik di sini berarti
     * fitur berbayar bocor terbuka, gagal senyap, tanpa gejala apa pun.
     *
     * Satu pengecualian didaftarkan di bawah. Namanya sengaja ditulis di sini,
     * bukan disimpulkan: menambah gate yang tak bisa dikunci harus jadi pilihan
     * sadar, bukan efek samping.
     */
    public function test_required_feature_di_komponen_benar_benar_terdaftar_di_config(): void
    {
        $dariConfig = array_keys(config('eventner_features', []));
        $dituntut = $this->gateYangDituntutKomponen();

        $this->assertNotEmpty($dituntut, 'Tidak ada requiredFeature yang terbaca — pola pemindaiannya basi.');

        $asing = array_values(array_diff($dituntut, $dariConfig, self::GATE_SELALU_TERBUKA));

        $this->assertSame(
            [],
            $asing,
            'Komponen Livewire menuntut gate yang tak ada di config/eventner_features.php, '
                . 'sehingga halamannya justru terbuka bagi siapa pun: ' . implode(', ', $asing)
        );
    }

    /**
     * Gerbang yang tak berpemilik — bugnya yang sebenarnya, dan satu-satunya
     * yang gejalanya sampai ke pengguna: pemilik paket berbayar ditolak.
     *
     * Hanya kunci yang TERDAFTAR di config yang diperiksa; yang tak terdaftar
     * selalu terbuka (lihat tes di atas). Inilah kombinasi yang lolos dari
     * ketiga tes sebelumnya — gate dituntut halaman, ada di config, tapi tak
     * dipegang paket berbayar mana pun. `layout`, `schedule`, `vote_comments`
     * persis segitiga itu.
     */
    public function test_setiap_gate_yang_dituntut_halaman_dipegang_paket_berbayar(): void
    {
        $dipegang = SaasPlanFeature::query()
            ->whereIn('saas_plan_id', SaasPlan::where('is_free', false)->pluck('id'))
            ->pluck('feature_key')
            ->unique()
            ->all();

        $terdaftar = array_intersect($this->gateYangDituntutKomponen(), array_keys(config('eventner_features', [])));

        $yatim = array_values(array_diff($terdaftar, $dipegang));

        $this->assertSame(
            [],
            $yatim,
            'Gate ini dituntut halaman tapi tak dipegang paket berbayar mana pun: '
                . implode(', ', $yatim)
        );
    }

    /**
     * Migrasi penambal harus menyembuhkan data yang sudah rusak, bukan cuma
     * benar untuk instalasi baru. Karena itu kerusakannya dibuat lebih dulu di
     * sini, lalu migrasinya dijalankan ulang.
     */
    public function test_migrasi_menambal_paket_yang_kehilangan_kunci(): void
    {
        $paket = SaasPlan::where('is_free', false)->firstOrFail();
        $paket->features()->whereIn('feature_key', ['layout', 'schedule', 'vote_comments'])->delete();

        // Penyesuaian manual admin pada kunci lain harus selamat — itulah
        // alasan migrasinya firstOrCreate, bukan delete-lalu-create-many.
        $paket->features()->where('feature_key', 'tickets')->delete();

        $this->assertSame('layout', $this->kunciPertamaYangHilang($paket));

        (require database_path('migrations/2026_10_20_000002_lengkapi_fitur_paket_berbayar.php'))->up();

        $sesudah = $paket->fresh()->featureKeys();

        foreach (['layout', 'schedule', 'vote_comments'] as $kunci) {
            $this->assertContains($kunci, $sesudah, "Migrasi tidak menambal \"{$kunci}\".");
        }

        // `tickets` sengaja dihapus admin; migrasi tak boleh menghidupkannya
        // kembali — kalau ia kembali, berarti migrasinya menimpa pilihan admin.
        $this->assertNotContains(
            'tickets',
            $sesudah,
            'Migrasi menghidupkan ulang fitur yang sengaja dimatikan admin.'
        );

        // Idempoten: menjalankannya dua kali tak boleh menggandakan baris.
        (require database_path('migrations/2026_10_20_000002_lengkapi_fitur_paket_berbayar.php'))->up();

        $this->assertSame(
            count($paket->fresh()->featureKeys()),
            count(array_unique($paket->fresh()->featureKeys())),
            'Migrasi menggandakan baris fitur saat dijalankan dua kali.'
        );
    }

    /**
     * Pembuktian perilaku nyata, bukan sekadar isi tabel: halaman ber-gate
     * kembali terbuka bagi eventner berpaket berbayar.
     */
    public function test_eventner_berbayar_bisa_membuka_halaman_ber_gate(): void
    {
        $paket = SaasPlan::where('is_free', false)->firstOrFail();

        $eventner = Eventner::factory()->create([
            'saas_plan_id' => $paket->id,
            'plan' => 'free',
            'trial_ends_at' => null,
        ]);

        foreach (['layout', 'schedule', 'vote_comments'] as $kunci) {
            $this->assertTrue(
                $eventner->canAccessFeature($kunci),
                "Eventner berbayar tetap terkunci dari \"{$kunci}\" — paywall-nya bocor ke arah pembeli."
            );
        }

        $this->assertNotContains('Denah Lapangan', array_values($eventner->lockedFeatures()));
        $this->assertNotContains('Jadwal Pertandingan', array_values($eventner->lockedFeatures()));
        $this->assertNotContains('Komentar Voting', array_values($eventner->lockedFeatures()));
    }

    /**
     * Nama kelas komponen di atas sengaja dirujuk supaya gate `layout` yang
     * jadi biang keroknya tetap punya jejak di berkas ini.
     */
    public function test_halaman_denah_menuntut_gate_layout(): void
    {
        $properti = (new \ReflectionClass(LayoutIndex::class))->getProperty('requiredFeature');

        $this->assertSame('layout', $properti->getDefaultValue());
    }

    /**
     * Baca `protected string $requiredFeature = '...'` dari seluruh komponen
     * Livewire. Pemindaian statis, bukan daftar manual — daftar manual justru
     * hal yang membuat bug ini tak terlihat selama ini.
     *
     * @return array<int, string>
     */
    private function gateYangDituntutKomponen(): array
    {
        $gate = [];

        foreach (File::allFiles(app_path('Livewire')) as $berkas) {
            $isi = File::get($berkas->getPathname());

            if (preg_match_all('/requiredFeature\s*=\s*\'([^\']+)\'/', $isi, $cocok)) {
                foreach ($cocok[1] as $kunci) {
                    $gate[] = $kunci;
                }
            }
        }

        sort($gate);

        return array_values(array_unique($gate));
    }

    private function kunciPertamaYangHilang(SaasPlan $paket): string
    {
        $yatim = array_values(array_diff($this->kunciTerkunci(), $paket->featureKeys()));
        sort($yatim);

        return $yatim[0] ?? '';
    }
}
