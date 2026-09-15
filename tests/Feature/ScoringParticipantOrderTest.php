<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Daftar peserta di /eventner/scoring harus urut nomor undian.
 *
 * Juri menilai mengikuti urutan tampil, jadi layar operator tidak boleh
 * menampilkan urutan lain (sebelumnya urut id — tidak ada hubungannya dengan
 * nomor undian yang dipanggil di lapangan).
 */
class ScoringParticipantOrderTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $lomba;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create(['status' => 'approved']);
        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->lomba = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create(['name' => 'PBB Beregu']);

        $this->actingAs($this->eventner->user);
    }

    private function reg(string $nama, ?int $urutan): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => $nama,
            'urutan_tampil' => $urutan,
        ]);
    }

    public function test_peserta_urut_nomor_undian()
    {
        // Dibuat terbalik dari urutan undiannya — urut id akan salah.
        $this->reg('Sekolah C', 3);
        $this->reg('Sekolah A', 1);
        $this->reg('Sekolah B', 2);

        $participants = Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->viewData('participants');

        $this->assertSame(
            ['Sekolah A', 'Sekolah B', 'Sekolah C'],
            $participants->pluck('nama_sekolah')->all()
        );
    }

    /**
     * Peserta yang belum diundi tidak boleh menyelip di tengah — ia tampil
     * paling bawah, dirapikan per nama sekolah.
     */
    public function test_peserta_tanpa_nomor_undian_di_urutan_terakhir()
    {
        $this->reg('Tanpa Undian B', null);
        $this->reg('Sudah Diundi', 7);
        $this->reg('Tanpa Undian A', null);

        $participants = Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->viewData('participants');

        $this->assertSame(
            ['Sudah Diundi', 'Tanpa Undian A', 'Tanpa Undian B'],
            $participants->pluck('nama_sekolah')->all()
        );
    }

    /** Pencarian tidak boleh mengembalikan urutan ke id. */
    public function test_urutan_tetap_saat_mencari()
    {
        $this->reg('SMP Satu', 2);
        $this->reg('SMP Dua', 1);

        $participants = Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->set('search', 'SMP')
            ->viewData('participants');

        $this->assertSame(
            ['SMP Dua', 'SMP Satu'],
            $participants->pluck('nama_sekolah')->all()
        );
    }
}
