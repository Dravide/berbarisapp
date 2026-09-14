<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Drawing\Spin;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gerbang undian tidak boleh cuma bergantung pada properti Livewire.
 *
 * $isAuthenticated di-#[Locked] sehingga klien tidak bisa menulisnya, tapi
 * nilainya ikut tersimpan di snapshot: halaman dibuka saat kode belum
 * dipasang (true), kode kemudian dipasang panitia, dan snapshot lama tetap
 * membawa true — akses tidak pernah menutup lagi.
 */
class DrawingAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * EventnerFactory memakai Str::random() yang bisa mengandung huruf besar,
     * sedangkan validasi subdomain hanya menerima huruf kecil.
     */
    private function buatEventner(string $slug, ?string $kode): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create([
            'user_id' => $user->id,
            'slug' => $slug,
            'status' => 'approved',
            'subdomain' => null,
            'drawing_code' => $kode,
        ]);
    }

    private function komponen(Eventner $eventner)
    {
        // Komponen disajikan lewat route publik /event/{slug}/drawing, bukan
        // lewat Auth — jadi mount-nya memang tanpa login.
        return Livewire::test(Spin::class, ['slug' => $eventner->slug]);
    }

    public function test_tanpa_kode_halaman_terbuka(): void
    {
        $eventner = $this->buatEventner('undian-terbuka', null);

        // Fitur aslinya: event yang memang belum punya kode tidak punya
        // gerbang, jadi tidak boleh ikut terkunci.
        $this->komponen($eventner)
            ->assertSet('isAuthenticated', true);
    }

    public function test_dengan_kode_halaman_terkunci(): void
    {
        $eventner = $this->buatEventner('undian-terkunci', 'RAHASIA2026');

        $this->komponen($eventner)
            ->assertSet('isAuthenticated', false);
    }

    public function test_kode_salah_tidak_membuka(): void
    {
        $eventner = $this->buatEventner('undian-salah', 'RAHASIA2026');

        $this->komponen($eventner)
            ->set('inputCode', 'SALAH')
            ->call('verifyCode')
            ->assertSet('isAuthenticated', false)
            ->assertHasErrors('inputCode');
    }

    public function test_kode_benar_membuka(): void
    {
        $eventner = $this->buatEventner('undian-benar', 'RAHASIA2026');

        $this->komponen($eventner)
            ->set('inputCode', 'RAHASIA2026')
            ->call('verifyCode')
            ->assertSet('isAuthenticated', true);
    }

    /**
     * Inti perbaikannya: sesi yang dibuka saat kode belum ada tidak boleh
     * tetap terbuka setelah kode dipasang.
     */
    public function test_sesi_lama_menutup_setelah_kode_dipasang(): void
    {
        $eventner = $this->buatEventner('undian-disusulkan', null);
        $kategori = CompetitionCategory::factory()->child()->create([
            'eventner_id' => $eventner->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
            'urutan_tampil' => 5,
        ]);

        $komponen = $this->komponen($eventner)
            ->set('activeTab', $kategori->id)
            ->assertSet('isAuthenticated', true);

        // Panitia memasang kode setelah halaman publik ini terbuka.
        // Sengaja lewat query builder, bukan $eventner->update(), supaya
        // instance yang dipegang komponen tidak ikut tersegarkan — snapshot
        // Livewire balapan dengan DB, persis seperti di produksi.
        Eventner::whereKey($eventner->id)->update(['drawing_code' => 'RAHASIA2026']);

        // Snapshot lama masih membawa isAuthenticated = true, tapi aksinya
        // harus tetap ditolak karena kodenya dibaca ulang dari DB.
        $komponen->call('resetDrawing');

        $this->assertSame(
            5,
            Registration::where('competition_category_id', $kategori->id)->value('urutan_tampil'),
            'Reset undian seharusnya ditolak setelah kode dipasang.'
        );
    }

    public function test_tanpa_kode_reset_undian_tetap_jalan(): void
    {
        $eventner = $this->buatEventner('undian-tanpa-kode', null);
        $kategori = CompetitionCategory::factory()->child()->create([
            'eventner_id' => $eventner->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
            'urutan_tampil' => 5,
        ]);

        $this->komponen($eventner)
            ->set('activeTab', $kategori->id)
            ->call('resetDrawing');

        $this->assertNull(
            Registration::where('competition_category_id', $kategori->id)->value('urutan_tampil')
        );
    }
}
