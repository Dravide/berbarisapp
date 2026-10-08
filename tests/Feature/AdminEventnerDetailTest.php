<?php

namespace Tests\Feature;

use App\Livewire\Admin\Eventner\Modul;
use App\Livewire\Admin\Eventner\Penilaian;
use App\Livewire\Admin\Eventner\Struktur;
use App\Models\AssessmentCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionSeries;
use App\Models\DeductionCategory;
use App\Models\EventFaq;
use App\Models\EventGallery;
use App\Models\Eventner;
use App\Models\EventRundown;
use App\Models\EventnerBankAccount;
use App\Models\EventnerSignature;
use App\Models\Judge;
use App\Models\OverlaySetting;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEventnerDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    // ────────────────────────────────────────────────
    // Akses
    // ────────────────────────────────────────────────

    public function test_sub_halaman_butuh_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.eventner.penilaian', 1))->assertForbidden();
        $this->actingAs($user)->get(route('admin.eventner.struktur', 1))->assertForbidden();
        $this->actingAs($user)->get(route('admin.eventner.modul', 1))->assertForbidden();
    }

    // ────────────────────────────────────────────────
    // Sub-laman Format Penilaian
    // ────────────────────────────────────────────────

    public function test_halaman_penilaian_menampilkan_rubrik_lengkap()
    {
        $eventner = Eventner::factory()->create();
        $kategori = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Aspek Keterampilan',
            'sort_order' => 1,
        ]);
        $sub = \App\Models\AssessmentSubCategory::create([
            'assessment_category_id' => $kategori->id,
            'name' => 'Sub Varian Gerakan',
            'sort_order' => 1,
        ]);
        \App\Models\AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kerapian Barisan',
            'score_options' => [['score' => 100, 'label' => 'Baik Sekali'], ['score' => 50, 'label' => 'Cukup']],
            'weight' => 2,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Penilaian::class, ['id' => $eventner->id])
            ->assertSee('Aspek Keterampilan')
            ->assertSee('Sub Varian Gerakan')
            ->assertSee('Kerapian Barisan')
            ->assertSee('Baik Sekali')
            ->assertSee('Cukup');
    }

    public function test_halaman_penilaian_mengelompokkan_rubrik_per_tingkat()
    {
        $eventner = Eventner::factory()->create();
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SLA',
        ]);
        $anak = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Putri',
            'parent_id' => $induk->id,
        ]);

        AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $anak->id,
            'name' => 'Rubrik Putri',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Penilaian::class, ['id' => $eventner->id])
            ->assertSee('Tingkat SLA — Putri')
            ->assertSee('Rubrik Putri');
    }

    public function test_rubrik_tanpa_tingkat_tampil_di_grup_berlaku_semua_tingkat()
    {
        $eventner = Eventner::factory()->create();
        AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Rubrik Umum',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Penilaian::class, ['id' => $eventner->id])
            ->assertSee('Berlaku Semua Tingkat')
            ->assertSee('Rubrik Umum');
    }

    public function test_halaman_penilaian_menampilkan_pengurangan_per_kategori()
    {
        $eventner = Eventner::factory()->create();
        $kategori = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Aspek Perapihan',
            'sort_order' => 1,
        ]);
        $deduksi = DeductionCategory::create([
            'eventner_id' => $eventner->id,
            'assessment_category_id' => $kategori->id,
            'scope' => 'category',
            'name' => 'Keterlambatan',
            'sort_order' => 1,
        ]);
        \App\Models\DeductionCriteria::create([
            'deduction_category_id' => $deduksi->id,
            'name' => 'Datang Terlambat',
            'deduction_options' => ['5', '10', '20'],
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Penilaian::class, ['id' => $eventner->id])
            ->assertSee('Pengurangan Nilai')
            ->assertSee('Keterlambatan')
            ->assertSee('Datang Terlambat');
    }

    public function test_halaman_penilaian_menampilkan_pengurangan_global_per_tingkat()
    {
        $eventner = Eventner::factory()->create();
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SMA',
        ]);
        $deduksi = DeductionCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'scope' => \App\Models\DeductionCategory::SCOPE_GLOBAL,
            'name' => 'Pelanggaran Umum',
            'sort_order' => 1,
        ]);
        \App\Models\DeductionCriteria::create([
            'deduction_category_id' => $deduksi->id,
            'name' => 'Keluar Barisan',
            'deduction_options' => ['10'],
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Penilaian::class, ['id' => $eventner->id])
            ->assertSee('Pengurangan Global Tingkat Ini')
            ->assertSee('Pelanggaran Umum')
            ->assertSee('Keluar Barisan');
    }

    public function test_halaman_penilaian_menampilkan_pesan_kosong()
    {
        $eventner = Eventner::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Penilaian::class, ['id' => $eventner->id])
            ->assertSee('Belum ada format penilaian');
    }

    // ────────────────────────────────────────────────
    // Sub-laman Struktur & Juri
    // ────────────────────────────────────────────────

    public function test_halaman_struktur_menampilkan_pohon_tingkat_dan_sub_tingkat()
    {
        $eventner = Eventner::factory()->create();
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SD',
            'kuota' => 40,
        ]);
        CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Putra',
            'parent_id' => $induk->id,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Struktur::class, ['id' => $eventner->id])
            ->assertSee('Tingkat SD')
            ->assertSee('Putra');
    }

    public function test_halaman_struktur_menampilkan_grup_babak_dan_seri()
    {
        $eventner = Eventner::factory()->create();
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SMP',
        ]);
        CompetitionGroup::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);
        $babak = CompetitionRound::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => 'Penyisihan',
            'type' => \App\Models\CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        CompetitionSeries::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => 'Seri B',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Struktur::class, ['id' => $eventner->id])
            ->assertSee('Grup A')
            ->assertSee('Penyisihan')
            ->assertSee('Seri B');
    }

    public function test_halaman_struktur_menampilkan_juri_dan_penugasan_grup()
    {
        $eventner = Eventner::factory()->create();
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SD',
        ]);
        $grup = CompetitionGroup::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => 'Grup Elang',
            'sort_order' => 1,
        ]);
        $juri = Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Pak Surya',
            'phone_number' => '081234567890',
        ]);

        DB::table('competition_group_judge')->insert([
            'judge_id' => $juri->id,
            'competition_category_id' => $tingkat->id,
            'competition_group_id' => $grup->id,
            'scope' => CompetitionGroup::SCOPE_GROUP,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Struktur::class, ['id' => $eventner->id])
            ->assertSee('Pak Surya')
            ->assertSee('Grup Elang');
    }

    public function test_penugasan_tanpa_grup_memakai_label_scope()
    {
        $eventner = Eventner::factory()->create();
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SD',
        ]);
        $juri = Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Bu Rani',
        ]);

        DB::table('competition_group_judge')->insert([
            'judge_id' => $juri->id,
            'competition_category_id' => $tingkat->id,
            'competition_group_id' => null,
            'scope' => CompetitionGroup::SCOPE_FINAL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Struktur::class, ['id' => $eventner->id])
            ->assertSee('Bu Rani')
            ->assertSee('Final');
    }

    public function test_halaman_struktur_menampilkan_pesan_kosong()
    {
        $eventner = Eventner::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Struktur::class, ['id' => $eventner->id])
            ->assertSee('Belum ada tingkat lomba.')
            ->assertSee('Belum ada juri.');
    }

    // ────────────────────────────────────────────────
    // Sub-laman Modul
    // ────────────────────────────────────────────────

    public function test_halaman_modul_menampilkan_daftar_tiket()
    {
        $eventner = Eventner::factory()->create();
        Ticket::create([
            'eventner_id' => $eventner->id,
            'order_code' => 'TKT-ABC123',
            'buyer_name' => 'Bu Ani',
            'buyer_email' => 'ani@example.com',
            'buyer_phone' => '081200000000',
            'quantity' => 3,
            'price_per_ticket' => 25000,
            'total_amount' => 75000,
            'status' => 'PAID',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('TKT-ABC123')
            ->assertSee('Bu Ani');
    }

    public function test_halaman_modul_menampilkan_transaksi_vote()
    {
        $eventner = Eventner::factory()->create();
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SD',
        ]);
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
        ]);
        \App\Models\VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-TEST-1',
            'voter_name' => 'Doni',
            'amount' => 10000,
            'votes_earned' => 10,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Doni');
    }

    public function test_halaman_modul_menampilkan_kategori_juara()
    {
        $eventner = Eventner::factory()->create();
        ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Juara Umum');
    }

    public function test_halaman_modul_menampilkan_peserta_drawing_dengan_qr_token()
    {
        $eventner = Eventner::factory()->create();
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Tingkat SD',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'nama_sekolah' => 'SDN Mawar 1',
            'qr_token' => 'qrabc123',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('SDN Mawar 1')
            ->assertSee('qrabc123');
    }

    public function test_halaman_modul_menampilkan_rundown_dan_overlay()
    {
        $eventner = Eventner::factory()->create();
        EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Pembukaan',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);
        OverlaySetting::create([
            'eventner_id' => $eventner->id,
            'show_header' => true,
            'marquee_text' => 'Selamat Datang',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Pembukaan')
            ->assertSee('Selamat Datang');
    }

    public function test_halaman_modul_menampilkan_sponsor_tenant_dan_sertifikat()
    {
        $eventner = Eventner::factory()->create();
        \App\Models\Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Sponsor Utama',
        ]);
        \App\Models\Tenant::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Stand Makanan A',
        ]);
        \App\Models\CertificateTemplate::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Sertifikat Juara 1',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Sponsor Utama')
            ->assertSee('Stand Makanan A')
            ->assertSee('Sertifikat Juara 1');
    }

    public function test_halaman_modul_menampilkan_ttd_rekening_faq_dan_galeri()
    {
        $eventner = Eventner::factory()->create();
        EventnerSignature::create([
            'eventner_id' => $eventner->id,
            'name' => 'Ketua Panitia',
            'image' => 'signatures/test.png',
        ]);
        EventnerBankAccount::create([
            'eventner_id' => $eventner->id,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Panitia Berbaris',
            'is_active' => true,
        ]);
        EventFaq::factory()->create([
            'eventner_id' => $eventner->id,
            'question' => 'Kapan teknis meeting?',
        ]);
        EventGallery::factory()->create([
            'eventner_id' => $eventner->id,
            'caption' => 'Dokumentasi 2025',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Ketua Panitia')
            ->assertSee('BCA')
            ->assertSee('Kapan teknis meeting?')
            ->assertSee('Dokumentasi 2025');
    }

    public function test_halaman_modul_menampilkan_field_pendaftaran()
    {
        $eventner = Eventner::factory()->create();
        \App\Models\RegistrationField::factory()->create([
            'eventner_id' => $eventner->id,
            'label' => 'Ukuran Kaos',
            'field_key' => 'ukuran_kaos',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Ukuran Kaos');
    }

    public function test_modul_tanpa_data_menampilkan_pesan_kosong()
    {
        $eventner = Eventner::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Modul::class, ['id' => $eventner->id])
            ->assertSee('Belum ada data.');
    }

    // ────────────────────────────────────────────────
    // Pengayaan halaman utama
    // ────────────────────────────────────────────────

    public function test_detail_event_menampilkan_status_ditolak_dengan_alasan()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'rejected',
            'rejection_reason' => 'Surat pengantar belum lengkap',
        ]);

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertSee('Ditolak')
            ->assertSee('Surat pengantar belum lengkap');
    }

    public function test_detail_event_menampilkan_status_menunggu_persetujuan()
    {
        $eventner = Eventner::factory()->pending()->create();

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertSee('Menunggu Persetujuan');
    }

    public function test_detail_event_menampilkan_status_disetujui()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
        ]);

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertSee('Disetujui');
    }

    public function test_detail_event_menampilkan_kartu_akses_dan_token()
    {
        $eventner = Eventner::factory()->create([
            'scoring_code' => 'SCOR-X1Y2Z3',
            'checkin_token' => 'chk-token-abc',
            'checkin_pin' => '1234',
            'panitia_token' => 'pan-token-def',
            'panitia_pin' => '5678',
        ]);

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertSee('Akses & Token')
            ->assertSee('SCOR-X1Y2Z3')
            ->assertSee('chk-token-abc')
            ->assertSee('pan-token-def');
    }

    public function test_detail_event_menampilkan_tautan_tiga_sub_halaman()
    {
        $eventner = Eventner::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertSee(route('admin.eventner.penilaian', $eventner->id))
            ->assertSee(route('admin.eventner.struktur', $eventner->id))
            ->assertSee(route('admin.eventner.modul', $eventner->id));
    }

    public function test_detail_event_menampilkan_status_pendaftaran()
    {
        $eventner = Eventner::factory()->create([
            'tanggal_pendaftaran' => now()->addWeek()->format('Y-m-d'),
            'tanggal' => now()->addMonth()->format('Y-m-d'),
        ]);

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertSee('Pendaftaran Buka');
    }
}
