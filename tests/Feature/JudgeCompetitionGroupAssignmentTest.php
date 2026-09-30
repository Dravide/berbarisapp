<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modal "Atur Babak, Grup & Seri" — layar tempat panitia menyatakan
 * "juri Grup A siapa saja".
 *
 * Inilah satu-satunya jalur tulis ke competition_group_judge, jadi yang dijaga
 * di sini bukan sekadar tampilannya: centang yang salah tersimpan akan
 * mengubah peserta mana yang bisa dinilai siapa, dan tablet juri membaca tabel
 * yang sama persis.
 *
 * Dua hal yang gampang rusak dan sengaja dikunci:
 *
 *  1. Baris dibaca dari TINGKAT yang tombol "Atur"-nya ditekan, bukan dari
 *     induknya. Di data nyata LOBB, kategori "Tingkat Kelas 9" beranak di bawah
 *     "LOBB" dan grupnya menempel pada anak itu — membaca induknya membuat
 *     baris grup hilang dan yang muncul cuma "Seluruh Tingkat".
 *  2. Id juri diverifikasi ke tenant sendiri. Id yang datang dari klien tidak
 *     boleh dipercaya, karena satu event bisa punya juri dari event lain.
 */
class JudgeCompetitionGroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Eventner $eventner;

    private CompetitionCategory $parent;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private Judge $juriA;

    private Judge $juriB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $this->parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->parent->id,
            'name' => 'Tingkat Kelas 9',
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);
        $this->groupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);

        $this->juriA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Dery']);
        $this->juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Ujang']);
    }

    /** Modal dibuka pada satu tingkat — persis seperti tombol "Atur" di daftar. */
    private function modal(int $categoryId = null)
    {
        return Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\CompetitionCategory\Index::class)
            ->call('openRoundPanel', $categoryId ?? $this->level->id);
    }

    /** Baris penugasan yang dirender modal, sebagai [key => label]. */
    private function baris($component): array
    {
        return collect($component->instance()->assignmentRows)
            ->mapWithKeys(fn ($row) => [$row['key'] => $row['label']])
            ->all();
    }

    /**
     * Grup anak tingkat muncul di modal, bukan hanya baris "Seluruh Tingkat".
     *
     * Inilah keluhan yang dilaporkan: "Tingkat Kelas 9 grup A dan B-nya tidak
     * muncul, hanya muncul Seluruh Tingkat level". Penyebabnya baris dibaca dari
     * kategori INDUK, sedangkan grupnya menempel di anaknya.
     */
    public function test_grup_tingkat_anak_muncul_di_modal()
    {
        $component = $this->modal();

        $this->assertSame(
            [
                'group:' . $this->groupA->id => 'Grup A',
                'group:' . $this->groupB->id => 'Grup B',
            ],
            $this->baris($component),
            'Grup tingkat ini tidak terbaca dari modalnya.'
        );

        $component->assertSee('Grup A')->assertSee('Grup B');
    }

    /** Centang juri tersimpan, dan tablet juri membaca tabel yang sama. */
    public function test_centang_juri_tersimpan_dan_terbaca_ulang()
    {
        $component = $this->modal()
            ->call('toggleGroupJudge', CompetitionGroup::SCOPE_GROUP, $this->groupA->id, $this->juriA->id, true)
            ->call('toggleGroupJudge', CompetitionGroup::SCOPE_GROUP, $this->groupA->id, $this->juriB->id, true);

        $this->assertSame(
            [$this->juriA->id, $this->juriB->id],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id)
        );

        // Modal berikutnya membaca centang itu kembali, bukan keadaan kosong.
        $component->call('toggleGroupJudge', CompetitionGroup::SCOPE_GROUP, $this->groupA->id, $this->juriB->id, false);

        $this->assertSame(
            [$this->juriA->id],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id)
        );

        $this->assertSame(
            [$this->juriA->id],
            $this->modal()->instance()->groupJudgeIds->get('group:' . $this->groupA->id)->all()
        );
    }

    /**
     * Centang grup TIDAK bocor ke grup lain.
     *
     * Baris `group` menyimpan competition_group_id, jadi satu centang salah
     * tempat membuat seluruh Grup B ikut dinilai juri Grup A — persis
     * kebocoran yang ditutup seluruh fitur ini.
     */
    public function test_centang_satu_grup_tidak_membocor_ke_grup_lain()
    {
        $this->modal()
            ->call('toggleGroupJudge', CompetitionGroup::SCOPE_GROUP, $this->groupA->id, $this->juriA->id, true);

        $this->assertSame(
            [],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupB->id)
        );
    }

    /**
     * Modal memperingatkan grup yang belum punya juri.
     *
     * Peserta di grup itu tak bisa dinilai siapa pun, dan itu kegagalan yang
     * diam: tak ada error, hanya daftar kosong di tablet. Peringatannya harus
     * muncul di layar tempat centangnya dipasang.
     */
    public function test_grup_tanpa_juri_ditandai()
    {
        $component = $this->modal()
            ->call('toggleGroupJudge', CompetitionGroup::SCOPE_GROUP, $this->groupA->id, $this->juriA->id, true);

        $this->assertSame(
            ['group:' . $this->groupA->id],
            $component->instance()->groupJudgeIds->keys()
                ->filter(fn ($k) => str_starts_with($k, 'group:'))->values()->all()
        );

        $component->assertSee('Belum ada juri');
    }

    /**
     * Menghapus grup melepas penugasan jurinya.
     *
     * Penugasan menempel pada grup, bukan pada peserta: grup yang dibubarkan
     * memang tak punya regu penilai lagi. Tanpa cascade, barisnya menggantung
     * dan menunjuk grup yang sudah tak ada.
     */
    public function test_hapus_grup_melepas_penugasan_jurinya()
    {
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id, [$this->juriA->id]);

        $this->modal()->call('deleteGroup', $this->groupA->id);

        $this->assertDatabaseMissing('competition_groups', ['id' => $this->groupA->id]);
        $this->assertDatabaseMissing('competition_group_judge', [
            'competition_group_id' => $this->groupA->id,
            'judge_id' => $this->juriA->id,
        ]);
    }

    /**
     * Baris Final muncul begitu tingkatnya punya babak final.
     *
     * Final bukan grup, jadi ia tak akan pernah muncul dari daftar grup. Juri
     * final punya penugasan tersendiri, dan tanpa baris ini ia tak bisa
     * dicentang di mana pun.
     */
    public function test_baris_final_muncul_saat_tingkat_punya_babak_final()
    {
        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 3,
        ]);

        $component = $this->modal();

        $this->assertSame('Final', $this->baris($component)[CompetitionGroup::SCOPE_FINAL] ?? null);

        $component->call('toggleGroupJudge', CompetitionGroup::SCOPE_FINAL, null, $this->juriA->id, true);

        $this->assertSame(
            [$this->juriA->id],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_FINAL)
        );

        // Dan baris tersebut terpisah dari baris grup mana pun.
        $this->assertSame(
            [],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id)
        );
    }

    /** Peserta yang belum bergrup mendapat barisnya sendiri, dan bisa dicentang. */
    public function test_baris_belum_bergrup_muncul_saat_ada_pesertanya()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => null,
            'nama_sekolah' => 'SMPN 9',
        ]);

        $component = $this->modal();

        $this->assertSame(
            'Belum Bergrup',
            $this->baris($component)[CompetitionGroup::SCOPE_UNGROUPED] ?? null
        );

        $component->call('toggleGroupJudge', CompetitionGroup::SCOPE_UNGROUPED, null, $this->juriB->id, true);

        $this->assertSame(
            [$this->juriB->id],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_UNGROUPED)
        );
    }

    /**
     * Tingkat tanpa grup sama sekali memakai baris "Seluruh Tingkat".
     *
     * Jalan tiga belas event lama. Sesudah layar centang rubrik dihapus, baris
     * inilah satu-satunya cara juri event lama tetap bisa menilai.
     */
    public function test_tingkat_tanpa_grup_memakai_baris_seluruh_tingkat()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $polos->id,
            'nama_sekolah' => 'SMPN 3',
        ]);

        $component = $this->modal($polos->id);

        $this->assertSame([
            CompetitionGroup::SCOPE_LEVEL => 'Seluruh Tingkat',
        ], $this->baris($component));

        $component->call('toggleGroupJudge', CompetitionGroup::SCOPE_LEVEL, null, $this->juriA->id, true);

        $this->assertSame(
            [$this->juriA->id],
            CompetitionGroup::judgeIdsUntuk($polos->id, CompetitionGroup::SCOPE_LEVEL)
        );
    }

    /** Tingkat polos tanpa peserta tak menampilkan pemilih apa pun. */
    public function test_tingkat_polos_tanpa_peserta_tidak_menampilkan_baris()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        $this->assertSame([], $this->baris($this->modal($polos->id)));
    }

    /**
     * Id juri dari event lain ditolak diam-diam.
     *
     * Modal menerima id mentah dari klien. Tanpa penyaringan tenant, panitia
     * satu event bisa menugaskan juri milik event lain — dan juri itu lalu
     * melihat peserta yang bukan miliknya.
     */
    public function test_juri_dari_event_lain_tidak_bisa_dicentang()
    {
        $eventnerLain = Eventner::factory()->create(['status' => 'approved']);
        $juriLain = Judge::create(['eventner_id' => $eventnerLain->id, 'name' => 'Juri Event Lain']);

        $this->modal()
            ->call('toggleGroupJudge', CompetitionGroup::SCOPE_GROUP, $this->groupA->id, $juriLain->id, true);

        $this->assertSame(
            [],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id)
        );
    }

    /**
     * Centang satu tingkat TIDAK menyalakan centang tingkat lain.
     *
     * Keluhan yang dilaporkan: modal "SD / MI - U12" menampilkan juri sudah
     * tercentang padahal di tingkat itu belum ada yang dicentang.
     *
     * Sebabnya tiga scope tanpa grup (`ungrouped`/`final`/`level`) memakai
     * KUNCI YANG SAMA di seluruh event, sedangkan `groupJudgeIds` membaca
     * competition_group_judge tanpa menyaring tingkatnya. Satu baris `level`
     * di tingkat mana pun menyalakan centang di seluruh tingkat yang punya
     * baris serupa — dan mengkliknya menulis penugasan ke tingkat yang salah,
     * sehingga juri melihat peserta yang bukan miliknya.
     */
    public function test_centang_satu_tingkat_tidak_menyalakan_centang_tingkat_lain()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $tingkatLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
            'name' => 'SD / MI - U12',
        ]);

        foreach ([$this->level, $tingkatLain] as $tingkat) {
            Registration::factory()->for($this->eventner, 'eventner')->create([
                'competition_category_id' => $tingkat->id,
                'nama_sekolah' => 'SMPN ' . $tingkat->id,
            ]);
        }

        // Tingkat pertama: baris "Seluruh Tingkat" dicentang ke Juri A.
        $this->modal()->call('toggleGroupJudge', CompetitionGroup::SCOPE_LEVEL, null, $this->juriA->id, true);

        $this->assertSame(
            [$this->juriA->id],
            CompetitionGroup::judgeIdsUntuk($this->level->id, CompetitionGroup::SCOPE_LEVEL)
        );

        // Tingkat kedua belum disentuh — centangnya harus kosong.
        $modalLain = $this->modal($tingkatLain->id);

        $this->assertSame(
            [],
            $modalLain->instance()->groupJudgeIds->get(CompetitionGroup::SCOPE_LEVEL, collect())->all(),
            'Centang tingkat lain bocor ke modal tingkat ini.'
        );

        $this->assertSame(
            [],
            CompetitionGroup::judgeIdsUntuk($tingkatLain->id, CompetitionGroup::SCOPE_LEVEL),
            'Penugasan tingkat ini tak boleh ada sebelum dicentang.'
        );
    }

    /**
     * Kotak centang yang dirender ikut bersih, bukan hanya data di belakangnya.
     *
     * Centang yang bocor cuma bisa dilihat di HTML — dan itu bentuk keluhannya:
     * "ada juri yang sudah terceklis padahal belum".
     *
     * Dibandingkan setelah spasi dibuang: `@checked` berada di baris terpisah
     * dari atribut `id`, jadi mencocokkan potongan teks mentah akan selalu
     * gagal cocok — dan tes yang selalu gagal cocok justru lulus tanpa
     * memeriksa apa pun.
     */
    public function test_kotak_centang_tingkat_lain_tidak_terceklis()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);

        // Dua tingkat POLOS, bukan $this->level yang punya grup: baris
        // "Seluruh Tingkat" hanya dirender kalau tingkatnya tak punya grup
        // sama sekali, jadi kotak centang yang mau diperiksa di sini memang
        // tak ada di modal tingkat bergrup.
        $tingkatDicentang = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
            'name' => 'SD / MI - U12',
        ]);
        $tingkatKosong = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
            'name' => 'SMP / MTs - U15',
        ]);

        foreach ([$tingkatDicentang, $tingkatKosong] as $tingkat) {
            Registration::factory()->for($this->eventner, 'eventner')->create([
                'competition_category_id' => $tingkat->id,
                'nama_sekolah' => 'SMPN ' . $tingkat->id,
            ]);
        }

        // Satu tingkat dicentang; tingkat polos yang lain tidak disentuh.
        $this->modal($tingkatDicentang->id)
            ->call('toggleGroupJudge', CompetitionGroup::SCOPE_LEVEL, null, $this->juriA->id, true);

        $idJuriA = 'id="juri-' . CompetitionGroup::SCOPE_LEVEL . '-' . $this->juriA->id . '"';

        $htmlKosong = preg_replace('/\s+/', '', $this->modal($tingkatKosong->id)->html());

        // Kotak centangnya ada, dan tepat sesudah atribut id langsung menuju
        // wire:change — tanpa `checked` di antaranya.
        $this->assertStringContainsString(
            $idJuriA . 'wire:change',
            $htmlKosong,
            'Kotak centang juri tidak dirender sama sekali di tingkat yang belum dicentang.'
        );

        // Bukti sebaliknya: tingkat yang memang dicentang MEMANG punya
        // `checked`, jadi pemeriksaan di atas bukan sekadar tak pernah cocok.
        $htmlDicentang = preg_replace('/\s+/', '', $this->modal($tingkatDicentang->id)->html());

        $this->assertStringContainsString(
            $idJuriA . 'checked',
            $htmlDicentang,
            'Kotak centang tingkat yang dicentang tidak terceklis — tesnya tak mengukur apa pun.'
        );
    }
}
