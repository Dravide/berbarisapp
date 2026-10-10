<?php

namespace Tests\Feature;

use App\Livewire\Admin\ErrorLogIndex;
use App\Models\ErrorLog as ErrorLogModel;
use App\Models\User;
use App\Support\ErrorCode;
use App\Support\RecordsErrorReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Log Error Admin + kode error publik (ER-XXXXXX) + halaman 404/500 berbeda.
 * Setiap error tak terduga dicatat ke error_logs, kodenya tampil di halaman
 * 500, dan admin bisa mencari/menutupnya di /admin/error-log.
 */
class AdminErrorLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    // ────────────────────────────────────────────────
    // Akses
    // ────────────────────────────────────────────────

    public function test_guest_diarahkan_ke_login()
    {
        $this->get(route('admin.error-log'))->assertRedirect(route('login'));
    }

    public function test_log_error_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.error-log'))->assertForbidden();
    }

    // ────────────────────────────────────────────────
    // Daftar, cari, filter
    // ────────────────────────────────────────────────

    public function test_daftar_menampilkan_kode_dan_pesan()
    {
        ErrorLogModel::factory()->create(['message' => 'Undefined variable $foo']);
        ErrorLogModel::factory()->create(['message' => 'Koneksi database putus']);

        Livewire::actingAs($this->admin())
            ->test(ErrorLogIndex::class)
            ->assertSee('Undefined variable $foo')
            ->assertSee('Koneksi database putus');
    }

    public function test_cari_berdasarkan_kode()
    {
        ErrorLogModel::factory()->create(['code' => 'ER-AAAAAA', 'message' => 'satu']);
        ErrorLogModel::factory()->create(['code' => 'ER-BBBBBB', 'message' => 'dua']);

        Livewire::actingAs($this->admin())
            ->test(ErrorLogIndex::class)
            ->set('search', 'ER-AAAAAA')
            ->assertSee('satu')
            ->assertDontSee('dua');
    }

    public function test_filter_status_selesai_dan_belum()
    {
        ErrorLogModel::factory()->create(['code' => 'ER-CCCCCC']);
        ErrorLogModel::factory()->resolved()->create(['code' => 'ER-DDDDDD']);

        Livewire::actingAs($this->admin())
            ->test(ErrorLogIndex::class)
            ->set('filterStatus', 'open')
            ->assertSee('ER-CCCCCC')
            ->assertDontSee('ER-DDDDDD')
            ->set('filterStatus', 'resolved')
            ->assertSee('ER-DDDDDD')
            ->assertDontSee('ER-CCCCCC');
    }

    public function test_baris_legacy_tanpa_occurrences_tetap_tampil()
    {
        // Baris yang dibuat sebelum migration occurrences/last_seen_at ada.
        ErrorLogModel::factory()->create([
            'code' => 'ER-LEGACY',
            'occurrences' => null,
            'last_seen_at' => null,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ErrorLogIndex::class)
            ->assertSee('ER-LEGACY')
            ->assertSee('1×');
    }

    // ────────────────────────────────────────────────
    // Aksi tandai selesai / buka kembali
    // ────────────────────────────────────────────────

    public function test_tandai_selesai_dan_buka_kembali()
    {
        $admin = $this->admin();
        $log = ErrorLogModel::factory()->create();

        Livewire::actingAs($admin)
            ->test(ErrorLogIndex::class)
            ->call('tandaiSelesai', $log->id);

        $log->refresh();
        $this->assertNotNull($log->resolved_at);
        $this->assertSame($admin->id, $log->resolved_by);

        Livewire::actingAs($admin)
            ->test(ErrorLogIndex::class)
            ->call('bukaKembali', $log->id);

        $log->refresh();
        $this->assertNull($log->resolved_at);
        $this->assertNull($log->resolved_by);
    }

    // ────────────────────────────────────────────────
    // ErrorCode + RecordsErrorReport
    // ────────────────────────────────────────────────

    public function test_format_kode_valid_dan_seribu_generate_unik()
    {
        $codes = [];

        for ($i = 0; $i < 1000; $i++) {
            $code = ErrorCode::generate();
            $this->assertTrue(ErrorCode::isValid($code), "Kode {$code} tak valid");
            $codes[] = $code;
        }

        $this->assertCount(1000, array_unique($codes));
        $this->assertTrue(ErrorCode::isValid('ER-986734'));
        $this->assertFalse(ErrorCode::isValid('er-986734'));
        $this->assertFalse(ErrorCode::isValid('ER-O9O9O9')); // O di luar alfabet
        $this->assertFalse(ErrorCode::isValid('ER-98673'));  // hanya 5 karakter
        $this->assertFalse(ErrorCode::isValid('ER-12'));     // terlalu pendek
    }

    public function test_record_mencatat_error_tak_terduga_dengan_kode()
    {
        $log = app(RecordsErrorReport::class)->record(new \RuntimeException('boom'));

        $this->assertNotNull($log);
        $this->assertTrue(ErrorCode::isValid($log->code));
        $this->assertSame('boom', $log->message);
        $this->assertSame(\RuntimeException::class, $log->exception_class);
        $this->assertSame(500, $log->http_status);
        $this->assertSame(1, $log->occurrences);
        $this->assertDatabaseCount('error_logs', 1);
    }

    public function test_error_sama_pakai_kode_sama_dan_kejadian_bertambah()
    {
        $service = app(RecordsErrorReport::class);

        // getFile()/getLine() menunjuk baris tempat `new` dipanggil, jadi
        // kedua kejadian dibuat dari closure yang sama (baris sama).
        $make = fn () => new \RuntimeException('boom');
        $pertama = $service->record($make());
        $kedua = $service->record($make());

        // Error sama (class+file+line) = kode sama, satu baris, counter naik.
        $this->assertSame($pertama->code, $kedua->code);
        $this->assertSame($pertama->id, $kedua->id);
        $this->assertSame(2, $kedua->occurrences);
        $this->assertDatabaseCount('error_logs', 1);

        $lain = $service->record(new \Error('beda', 7));
        $this->assertNotSame($pertama->code, $lain->code);
        $this->assertDatabaseCount('error_logs', 2);
    }

    public function test_error_selesai_muncul_lagi_terbuka_otomatis()
    {
        $service = app(RecordsErrorReport::class);
        $admin = $this->admin();
        $make = fn () => new \RuntimeException('boom');
        $log = $service->record($make());
        $log->update(['resolved_at' => now(), 'resolved_by' => $admin->id]);

        $service->record($make());

        $log->refresh();
        $this->assertNull($log->resolved_at);
        $this->assertNull($log->resolved_by);
        $this->assertSame(2, $log->occurrences);
    }

    public function test_record_melewati_not_found_http()
    {
        $result = app(RecordsErrorReport::class)
            ->record(new NotFoundHttpException('halaman tak ada'));

        $this->assertNull($result);
        $this->assertDatabaseCount('error_logs', 0);
    }

    // ────────────────────────────────────────────────
    // Halaman 500 dengan kode + JSON
    // ────────────────────────────────────────────────

    public function test_halaman_500_menampilkan_kode_error()
    {
        config(['app.debug' => false]);

        $this->get('/_test-500')
            ->assertStatus(500)
            ->assertSee('Terjadi Kesalahan')
            ->assertSee('ER-');

        $this->assertDatabaseCount('error_logs', 1);
        $log = ErrorLogModel::sole();
        $this->assertTrue(ErrorCode::isValid($log->code));
        $this->assertStringEndsWith('/_test-500', $log->url);
    }

    public function test_halaman_500_json_membawa_error_code()
    {
        config(['app.debug' => false]);

        // Route beda dari test HTML supaya sidik jari (file:line) tak sama.
        $this->getJson('/_test-500-json')
            ->assertStatus(500)
            ->assertJsonPath('error_code', ErrorLogModel::sole()->code);
    }

    // ────────────────────────────────────────────────
    // Halaman 404 baru
    // ────────────────────────────────────────────────

    public function test_halaman_404_tampilan_khusus()
    {
        $this->get('/halaman-tak-ada')
            ->assertStatus(404)
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Ke Beranda');
    }

    // ────────────────────────────────────────────────
    // Ekspor CSV
    // ────────────────────────────────────────────────

    public function test_ekspor_csv_log_error()
    {
        $log = ErrorLogModel::factory()->create(['code' => 'ER-EEEEEE']);

        $res = $this->actingAs($this->admin())
            ->get(route('admin.exports.error-logs'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('ER-EEEEEE', $res->streamedContent());
        $this->assertStringContainsString('Kode', $res->streamedContent());
    }

    public function test_ekspor_csv_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.exports.error-logs'))->assertForbidden();
    }
}
