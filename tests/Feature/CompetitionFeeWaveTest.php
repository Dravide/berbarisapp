<?php

namespace Tests\Feature;

use App\Livewire\Eventner\CompetitionCategory\Show as CategoryShow;
use App\Livewire\Public\Registration\Create as RegistrationCreate;
use App\Models\CompetitionCategory;
use App\Models\CompetitionFeeWave;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gelombang harga biaya pendaftaran: satu rentang tanggal, satu angka.
 *
 * Sebelum ini biaya pendaftaran sebuah tingkat cuma satu angka, dan panitia
 * yang mau memberi harga lebih murah bagi pendaftar awal tidak punya cara
 * menuliskannya — jalan satu-satunya mengubah angkanya manual di tengah
 * periode pendaftaran, yang mengubah harga bagi semua orang tanpa jejak.
 *
 * Yang dijaga tes di sini bukan tabnya, melainkan DUA janji:
 *
 *  1. Angka yang dilihat pendaftar sama persis dengan yang ditagih.
 *     Kalau layar memakai satu penghitung sementara penagih memakai kolom
 *     mentah, pendaftar melihat Rp 100.000 di layar dan ditagih Rp 150.000.
 *  2. Tagihan yang sudah masuk tidak pernah berubah. `registrations.total_fee`
 *     adalah snapshot saat booking — menambah atau menghapus gelombang tidak
 *     boleh menyentuh baris lama.
 */
class CompetitionFeeWaveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Eventner $eventner;

    private CompetitionCategory $induk;

    private CompetitionCategory $tingkat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);

        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'gelombang-uji',
            'status' => 'approved',
            'subdomain' => null,
            // Tanpa deadline, formulir publik merender "Pendaftaran Ditutup".
            'tanggal_pendaftaran' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $this->induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);

        $this->tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'Regu Inti',
            'registration_fee' => 200000,
            'max_registrations_per_school' => 3,
        ]);
    }

    /** Halaman detail tingkat — tempat tab Gelombang diatur. */
    private function tab()
    {
        return Livewire::actingAs($this->user)->test(CategoryShow::class, [
            'category' => $this->tingkat->id,
        ]);
    }

    /** Formulir booking publik. */
    private function form()
    {
        return Livewire::test(RegistrationCreate::class, ['slug' => $this->eventner->slug]);
    }

    /** Isi langkah 2 lalu submit, mengembalikan komponennya. */
    private function booking()
    {
        return $this->form()
            ->call('toggleCategory', $this->tingkat->id)
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SMP Negeri Uji')
            ->set('nama_pelatih', 'Pelatih Uji')
            ->set('no_hp', '08123456789')
            ->set('school_email', 'uji@example.test')
            ->call('submit');
    }

    /** Gelombang disimpan lewat komponen, bukan langsung ke DB. */
    private function simpanGelombang(string $nama, string $mulai, string $selesai, $harga)
    {
        return $this->tab()
            ->set('waveName', $nama)
            ->set('waveStartsOn', $mulai)
            ->set('waveEndsOn', $selesai)
            ->set('waveAmount', $harga)
            ->call('saveWave');
    }

    public function test_tab_gelombang_menyimpan_harga_per_rentang_tanggal()
    {
        $this->simpanGelombang('Gelombang 1', '2026-10-01', '2026-10-31', 150000);

        $this->assertDatabaseHas('competition_fee_waves', [
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'amount' => 150000,
        ]);
    }

    /**
     * Rentang yang bertabrakan ditolak, dan pesannya menyebut gelombang mana.
     *
     * "Bentrok" saja tidak cukup: panitia harus tahu gelombang mana yang harus
     * digeser, apalagi kalau daftarnya sudah panjang.
     */
    public function test_gelombang_bentrok_tanggal_ditolak_dan_menyebut_nama_gelombang_lain()
    {
        $this->simpanGelombang('Gelombang 1', '2026-10-01', '2026-10-31', 150000);

        $this->tab()
            ->set('waveName', 'Gelombang 2')
            ->set('waveStartsOn', '2026-10-15')
            ->set('waveEndsOn', '2026-11-15')
            ->set('waveAmount', 175000)
            ->call('saveWave')
            ->assertDispatched('toast', function (string $event, array $params) {
                return $params['type'] === 'error'
                    && str_contains($params['message'], 'Gelombang 1');
            });

        $this->assertDatabaseMissing('competition_fee_waves', ['name' => 'Gelombang 2']);
    }

    /** Gelombang berdampingan sah: berakhir 31 Okt, mulai 1 Nov. */
    public function test_gelombang_berdampingan_tanggal_tetap_diterima()
    {
        $this->simpanGelombang('Gelombang 1', '2026-10-01', '2026-10-31', 150000);
        $this->simpanGelombang('Gelombang 2', '2026-11-01', '2026-11-30', 200000);

        $this->assertDatabaseCount('competition_fee_waves', 2);
    }

    /**
     * Batas hari inklusif: gelombang 1–31 Okt dan gelombang berikutnya yang
     * mulai 31 Okt memang bertabrakan; yang mulai 1 Nov tidak.
     *
     * Dikunci terpisah karena batas inklusif adalah hal yang paling gampang
     * berubah tanpa sengaja saat seseorang menyalin rumus.
     */
    public function test_tanggal_mulai_sama_dengan_akhir_gelombang_sebelumnya_diterima()
    {
        $this->simpanGelombang('Gelombang 1', '2026-10-01', '2026-10-31', 150000);
        $this->simpanGelombang('Gelombang 2', '2026-10-31', '2026-11-30', 200000);

        $this->assertDatabaseMissing('competition_fee_waves', ['name' => 'Gelombang 2']);

        $this->simpanGelombang('Gelombang 3', '2026-11-01', '2026-11-30', 200000);

        $this->assertDatabaseHas('competition_fee_waves', ['name' => 'Gelombang 3']);
    }

    /** Booking di dalam rentang menagih harga gelombang, bukan harga biasa. */
    public function test_harga_yang_ditagih_memakai_gelombang_yang_berlaku()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->subDays(2)->toDateString(),
            'ends_on' => now()->addDays(2)->toDateString(),
            'amount' => 125000,
        ]);

        $this->booking();

        $reg = Registration::where('competition_category_id', $this->tingkat->id)->first();

        $this->assertNotNull($reg);
        $this->assertSame(125000.0, (float) $reg->total_fee);
        $this->assertSame('unpaid', $reg->payment_status);
    }

    /** Di luar semua gelombang, harga biasa yang dipakai. */
    public function test_di_luar_semua_gelombang_memakai_harga_biasa()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'amount' => 125000,
        ]);

        $this->booking();

        $reg = Registration::where('competition_category_id', $this->tingkat->id)->first();

        $this->assertSame(200000.0, (float) $reg->total_fee);
    }

    /** Tanpa harga biasa dan di luar gelombang: pendaftaran gratis. */
    public function test_tanpa_harga_biasa_dan_di_luar_gelombang_pendaftaran_gratis()
    {
        $this->tingkat->update(['registration_fee' => null]);

        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'amount' => 125000,
        ]);

        $this->booking();

        $reg = Registration::where('competition_category_id', $this->tingkat->id)->first();

        $this->assertNull($reg->total_fee);
        $this->assertSame('free', $reg->payment_status);
    }

    /** Kartu publik menyebut "mulai dari" bila ada gelombang yang lebih murah. */
    public function test_kartu_publik_menampilkan_mulai_dari_saat_ada_gelombang_lebih_murah()
    {
        // Hari ini di luar gelombang, jadi yang ditagih Rp 200.000; gelombang
        // nanti Rp 125.000. Pendaftar berhak tahu ada harga lebih rendah.
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'amount' => 125000,
        ]);

        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Rp 200.000', false)
            ->assertSee('mulai dari Rp 125.000', false);
    }

    /**
     * Gelombang yang lebih MAHAL tidak memunculkan "mulai dari".
     *
     * "Mulai dari" adalah janji angka termurah; menuliskannya di atas harga
     * yang lebih rendah dari gelombang berikutnya justru menyesatkan.
     */
    public function test_kartu_publik_tidak_menulis_mulai_dari_saat_gelombang_lebih_mahal()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(10)->toDateString(),
            'amount' => 250000,
        ]);

        $this->get("/event/{$this->eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Rp 200.000', false)
            ->assertDontSee('mulai dari', false);
    }

    /** Gelombang satu tingkat tidak bocor ke tingkat lain. */
    public function test_gelombang_tingkat_lain_tidak_berlaku()
    {
        $tingkatLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'Regu Cadangan',
            'registration_fee' => 300000,
        ]);

        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $tingkatLain->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addDay()->toDateString(),
            'amount' => 99000,
        ]);

        $this->assertSame(200000.0, $this->tingkat->fresh()->biayaPada());
        $this->assertSame(99000.0, $tingkatLain->fresh()->biayaPada());
    }

    /** Id gelombang tenant lain tidak bisa disunting dari halaman ini. */
    public function test_tenant_lain_tidak_bisa_mengubah_gelombang()
    {
        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
        ]);
        $indukLain = CompetitionCategory::factory()->create([
            'eventner_id' => $eventLain->id,
            'parent_id' => null,
        ]);
        $tingkatLain = CompetitionCategory::factory()->create([
            'eventner_id' => $eventLain->id,
            'parent_id' => $indukLain->id,
        ]);
        $gelombangLain = CompetitionFeeWave::create([
            'eventner_id' => $eventLain->id,
            'competition_category_id' => $tingkatLain->id,
            'name' => 'Gelombang 1',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'amount' => 100000,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->tab()->call('deleteWave', $gelombangLain->id);
    }

    /**
     * Menghapus gelombang tidak menyentuh tagihan pendaftar lama.
     *
     * `registrations.total_fee` adalah snapshot saat booking. Kalau baris lama
     * ikut berubah, rekap pendapatan dan invoice yang sudah dikirim jadi tidak
     * cocok dengan yang benar-benar dibayar.
     */
    public function test_hapus_gelombang_tidak_mengubah_total_fee_pendaftar_lama()
    {
        $gelombang = CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addDay()->toDateString(),
            'amount' => 125000,
        ]);

        $this->booking();

        $reg = Registration::where('competition_category_id', $this->tingkat->id)->first();
        $this->assertSame(125000.0, (float) $reg->total_fee);

        $this->tab()->call('deleteWave', $gelombang->id);

        $this->assertSame(125000.0, (float) $reg->fresh()->total_fee);
        $this->assertSame(200000.0, $this->tingkat->fresh()->biayaPada());
    }

    /**
     * Harga yang tampil di keranjang booking sama dengan yang ditagih.
     *
     * Ini janji yang paling gampang patah: kalau blade memakai kolom mentah
     * sementara submit() memakai resolver, layar menulis Rp 200.000 dan
     * tagihannya Rp 125.000.
     */
    public function test_keranjang_booking_menampilkan_harga_gelombang_yang_berlaku()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addDay()->toDateString(),
            'amount' => 125000,
        ]);

        $this->form()
            ->call('toggleCategory', $this->tingkat->id)
            ->assertSee('Rp 125.000', false)
            ->assertDontSee('Rp 200.000', false);
    }

    /** Gelombang berharga 0 berarti gratis pada rentangnya, bukan "tanpa gelombang". */
    public function test_gelombang_berharga_nol_membuat_pendaftaran_gratis_pada_rentangnya()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang Promo',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addDay()->toDateString(),
            'amount' => 0,
        ]);

        $this->booking();

        $reg = Registration::where('competition_category_id', $this->tingkat->id)->first();

        $this->assertSame(0.0, (float) $reg->total_fee);
        $this->assertSame('free', $reg->payment_status);
    }

    /** Mengedit gelombang sendiri boleh menahan rentangnya sendiri. */
    public function test_edit_gelombang_tidak_dianggap_bentrok_dengan_dirinya_sendiri()
    {
        $this->simpanGelombang('Gelombang 1', '2026-10-01', '2026-10-31', 150000);

        $gelombang = CompetitionFeeWave::where('name', 'Gelombang 1')->firstOrFail();

        $this->tab()
            ->call('editWave', $gelombang->id)
            ->set('waveAmount', 175000)
            ->call('saveWave')
            ->assertDispatched('toast', fn (string $event, array $params) => $params['type'] === 'success');

        $this->assertSame(175000.0, (float) $gelombang->fresh()->amount);
    }

    /**
     * Tanpa gelombang sama sekali, perilakunya persis seperti sebelum fitur ini.
     *
     * Gelombang bersifat aditif. Kalau tingkat tanpa gelombang tiba-tiba
     * berubah harga, setiap event lama di produksi ikut berubah diam-diam.
     */
    public function test_tanpa_gelombang_perilaku_sama_seperti_sebelumnya()
    {
        $this->assertSame(200000.0, $this->tingkat->biayaPada());
        $this->assertSame(200000.0, $this->tingkat->biayaTerendah());
        $this->assertTrue($this->tingkat->punyaBiaya());

        $this->booking();

        $reg = Registration::where('competition_category_id', $this->tingkat->id)->first();
        $this->assertSame(200000.0, (float) $reg->total_fee);
    }

    /**
     * Gelombang yang belum mulai tidak mengubah harga hari ini.
     *
     * Tanggalnya disebut eksplisit, bukan lewat now(): hasil tes tidak boleh
     * bergantung jam mesin yang menjalankannya.
     */
    public function test_gelombang_belum_mulai_tidak_mengubah_biaya_hari_ini()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => '2026-11-01',
            'ends_on' => '2026-11-30',
            'amount' => 99000,
        ]);

        $this->assertSame(200000.0, $this->tingkat->biayaPada(new \DateTime('2026-10-15')));
        $this->assertSame(99000.0, $this->tingkat->biayaPada(new \DateTime('2026-11-15')));
        $this->assertSame(200000.0, $this->tingkat->biayaPada(new \DateTime('2026-12-01')));
    }

    /**
     * Batas akhir gelombang ikut terhitung: tanggal terakhir masih kena harga
     * gelombang, sehari sesudahnya tidak.
     *
     * Dikunci terpisah dari tes bentrok — dua-duanya soal batas inklusif, tapi
     * yang ini soal harga yang ditagih, bukan penolakan saat menyimpan.
     */
    public function test_batas_akhir_gelombang_ikut_terhitung()
    {
        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'amount' => 125000,
        ]);

        $this->assertSame(125000.0, $this->tingkat->biayaPada(new \DateTime('2026-10-01')));
        $this->assertSame(125000.0, $this->tingkat->biayaPada(new \DateTime('2026-10-31')));
        $this->assertSame(200000.0, $this->tingkat->biayaPada(new \DateTime('2026-11-01')));
    }

    /**
     * Rekap keuangan memakai harga gelombang untuk potensi pendapatan.
     *
     * Layar finance dan PDF rekapnya punya perhitungan sendiri
     * (FinanceDashboardController), terpisah dari FinanceDashboard — dan
     * keduanya pernah membaca kolom mentah. Angka potensi yang ditampilkan
     * panitia harus sama dengan angka yang akan benar-benar ditagih.
     */
    public function test_potensi_pendapatan_finance_memakai_harga_gelombang()
    {
        \App\Models\CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->tingkat->id,
            'name' => 'Gelombang 1',
            'starts_on' => now()->subDay()->toDateString(),
            'ends_on' => now()->addDay()->toDateString(),
            'amount' => 125000,
        ]);

        // Tiga pendaftar yang belum lunas.
        for ($i = 0; $i < 3; $i++) {
            Registration::factory()->create([
                'eventner_id' => $this->eventner->id,
                'competition_category_id' => $this->tingkat->id,
                'payment_status' => 'unpaid',
            ]);
        }

        $komponen = Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\FinanceDashboard::class);

        $baris = collect($komponen->instance()->categoryBreakdown)
            ->firstWhere('id', $this->tingkat->id);

        $this->assertNotNull($baris);
        $this->assertSame(125000.0, (float) $baris['fee']);
        $this->assertSame(375000.0, (float) $baris['potential_revenue']);
    }

    /**
     * Jumlah query kartu publik tidak tumbuh seiring jumlah tingkat.
     *
     * Harga kartu dihitung lewat biayaPada(); kalau resolver membangun query
     * baru tiap panggilan, eager load tidak menolong dan satu halaman detail
     * event menembak dua query per tingkat.
     */
    public function test_kartu_publik_tidak_menembak_query_per_tingkat()
    {
        $hitung = function (): int {
            \Illuminate\Support\Facades\DB::flushQueryLog();
            \Illuminate\Support\Facades\DB::enableQueryLog();
            $this->get("/event/{$this->eventner->slug}")->assertStatus(200);
            $jumlah = count(\Illuminate\Support\Facades\DB::getQueryLog());
            \Illuminate\Support\Facades\DB::disableQueryLog();

            return $jumlah;
        };

        $sedikit = $hitung();

        for ($i = 0; $i < 6; $i++) {
            $tingkat = CompetitionCategory::factory()->create([
                'eventner_id' => $this->eventner->id,
                'parent_id' => $this->induk->id,
                'registration_fee' => 200000,
            ]);
            CompetitionFeeWave::create([
                'eventner_id' => $this->eventner->id,
                'competition_category_id' => $tingkat->id,
                'name' => 'Gelombang 1',
                'starts_on' => now()->subDay()->toDateString(),
                'ends_on' => now()->addDay()->toDateString(),
                'amount' => 125000,
            ]);
        }

        $banyak = $hitung();

        $this->assertLessThan(5, $banyak - $sedikit, 'Query bertambah seiring jumlah tingkat — relasi gelombang tidak ter-eager-load.');
    }

    /**
     * Perbaikan hierarki ikut memindahkan gelombang ke anak barunya.
     *
     * Command `categories:fix-parent-registrations` membuat anak "Regu Inti"
     * dari induk flat. Tanpa menyalin gelombangnya, tingkat bergelombang
     * diam-diam kembali ke harga biasa setelah perbaikan dijalankan.
     */
    public function test_perbaikan_hierarki_ikut_memindahkan_gelombang()
    {
        // Induk flat (tanpa anak) yang punya pendaftar dan gelombang.
        $flat = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Lama',
            'registration_fee' => 200000,
        ]);

        CompetitionFeeWave::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $flat->id,
            'name' => 'Gelombang 1',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
            'amount' => 125000,
        ]);

        Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $flat->id,
        ]);

        $this->artisan('categories:fix-parent-registrations')->assertSuccessful();

        $anak = CompetitionCategory::where('parent_id', $flat->id)->firstOrFail();

        $this->assertDatabaseHas('competition_fee_waves', [
            'competition_category_id' => $anak->id,
            'name' => 'Gelombang 1',
            'amount' => 125000,
        ]);
        $this->assertSame(125000.0, $anak->fresh()->biayaPada(new \DateTime('2026-10-15')));
    }
}
