<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Faq\Index as FaqIndex;
use App\Livewire\Eventner\Gallery\Index as GalleryIndex;
use App\Livewire\Eventner\Judge\Index as JudgeIndex;
use App\Livewire\Eventner\Rundown\Index as RundownIndex;
use App\Livewire\Eventner\Sponsor\Index as SponsorIndex;
use App\Livewire\Eventner\Tenant\Index as TenantIndex;
use App\Models\CompetitionCategory;
use App\Models\EventFaq;
use App\Models\EventGallery;
use App\Models\Eventner;
use App\Models\EventRundown;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Panel konten milik eventner — sponsor, tenant, galeri, FAQ, rundown.
 *
 * Lima layar ini sebelumnya hanya tersentuh tes tidak langsung (halaman publik
 * yang membacanya), jadi yang tak pernah diuji justru jalur tulisnya: sekat
 * antar-event dan berkas yang ditinggalkan saat logo diganti atau dihapus.
 * Dua hal itu yang paling mahal kalau salah — yang pertama membocorkan data
 * penyelenggara lain, yang kedua menumpuk berkas yatim di disk publik tanpa
 * ada yang pernah melihatnya.
 *
 * Galeri dan FAQ sengaja tidak ber-`FeatureGatedComponent`; sponsor, tenant,
 * dan rundown berpaket (`sponsors`, `tenants`, `rundown`), jadi keduanya diuji
 * di sini sekaligus sebagai penjaga gerbangnya.
 */
class ContentPanelsTest extends TestCase
{
    use RefreshDatabase;

    /** Eventner berbayar — `plan='paid'` tanpa paket DB membuka semua gerbang. */
    private function panitia(array $atribut = []): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->paid()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
        ], $atribut));

        return [$user, $eventner];
    }

    /** Eventner lain, untuk membuktikan sekatnya — bukan sekadar id barisnya. */
    private function eventLain(): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->paid()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }

    // ────────────────────────────────────────────────
    // Sponsor & media partner
    // ────────────────────────────────────────────────

    public function test_sponsor_baru_tersimpan_beserta_logonya()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->set('name', 'PT Maju Bersama')
            ->set('type', 'sponsor')
            ->set('link', 'https://maju.test')
            ->set('sort_order', 3)
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertHasNoErrors();

        $sponsor = Sponsor::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('PT Maju Bersama', $sponsor->name);
        $this->assertSame('sponsor', $sponsor->type);
        $this->assertSame('https://maju.test', $sponsor->link);
        $this->assertSame(3, (int) $sponsor->sort_order);
        $this->assertNotNull($sponsor->logo);
        Storage::disk('public')->assertExists($sponsor->logo);
    }

    public function test_nama_sponsor_tidak_menyimpan_tag_html()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->set('name', '<b>PT Maju</b>')
            ->set('type', 'sponsor')
            ->set('sort_order', 0)
            ->call('save')
            ->assertHasNoErrors();

        $nama = Sponsor::where('eventner_id', $eventner->id)->firstOrFail()->name;

        // Kontraknya `strip_tags`, bukan pembersih HTML penuh: tag dibuang,
        // dan yang penting tak ada `<`/`>` yang tersimpan. Isi `<script>` tetap
        // ikut sebagai teks (`strip_tags` memang tak membuang isinya), jadi
        // jangan andalkan ini untuk keamanan — yang menahan XSS adalah `{{ }}`
        // di Blade, dan itu diuji terpisah di bawah.
        $this->assertStringNotContainsString('<', $nama);
        $this->assertStringNotContainsString('>', $nama);
    }

    public function test_nama_sponsor_di_escape_saat_dirender()
    {
        [$user, $eventner] = $this->panitia();

        // Sengaja lewat model, bukan lewat `save()`: menulis lewat panel akan
        // dibersihkan `strip_tags`, jadi lapis escape di Blade tak akan pernah
        // teruji. Baris seperti ini nyata — data lama sebelum pembersihan
        // dipasang, atau hasil impor. Yang dijaga di sini: apa pun isi kolomnya,
        // Blade tak pernah mengirimnya sebagai tag.
        Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'PT <script>alert(1)</script> Maju',
        ]);

        $html = Livewire::actingAs($user)->test(SponsorIndex::class)->html();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_link_sponsor_harus_url_yang_sah()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->set('name', 'PT Maju')
            ->set('type', 'sponsor')
            ->set('link', 'bukan-url')
            ->set('sort_order', 0)
            ->call('save')
            ->assertHasErrors('link');

        $this->assertSame(0, Sponsor::where('eventner_id', $eventner->id)->count());
    }

    public function test_link_sponsor_kosong_disimpan_sebagai_null()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->set('name', 'PT Maju')
            ->set('type', 'sponsor')
            ->set('link', '')
            ->set('sort_order', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Sponsor::where('eventner_id', $eventner->id)->firstOrFail()->link);
    }

    public function test_ganti_logo_sponsor_menghapus_berkas_lama()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $lama = 'sponsors/lama.png';
        Storage::disk('public')->put($lama, 'isi-lama');

        $sponsor = Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'PT Lama',
            'logo' => $lama,
        ]);

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->call('edit', $sponsor->id)
            ->set('logo', UploadedFile::fake()->image('baru.png'))
            ->call('save')
            ->assertHasNoErrors();

        $sponsor->refresh();

        $this->assertNotSame($lama, $sponsor->logo, 'Logo harus diganti, bukan dipertahankan.');
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($sponsor->logo);
    }

    public function test_hapus_sponsor_ikut_menghapus_logonya()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $logo = 'sponsors/hapus.png';
        Storage::disk('public')->put($logo, 'isi');

        $sponsor = Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'logo' => $logo,
        ]);

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->call('delete', $sponsor->id);

        $this->assertNull(Sponsor::find($sponsor->id));
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_sponsor_diurutkan_dari_sort_order_terkecil()
    {
        [$user, $eventner] = $this->panitia();

        Sponsor::factory()->create(['eventner_id' => $eventner->id, 'name' => 'Ketiga', 'sort_order' => 30]);
        Sponsor::factory()->create(['eventner_id' => $eventner->id, 'name' => 'Pertama', 'sort_order' => 10]);
        Sponsor::factory()->create(['eventner_id' => $eventner->id, 'name' => 'Kedua', 'sort_order' => 20]);

        $nama = Livewire::actingAs($user)->test(SponsorIndex::class)
            ->instance()
            ->sponsors
            ->pluck('name')
            ->all();

        $this->assertSame(['Pertama', 'Kedua', 'Ketiga'], $nama);
    }

    public function test_sponsor_event_lain_tidak_bisa_disunting_dihapus_atau_ditoggle()
    {
        [$user] = $this->panitia();
        $asing = $this->eventLain();

        $sponsor = Sponsor::factory()->create([
            'eventner_id' => $asing->id,
            'is_active' => true,
        ]);

        foreach (['edit', 'delete', 'toggleActive'] as $aksi) {
            try {
                Livewire::actingAs($user)->test(SponsorIndex::class)->call($aksi, $sponsor->id);
                $this->fail("Aksi {$aksi} seharusnya menolak id milik event lain.");
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
                // memang begitu seharusnya
            }
        }

        $this->assertTrue((bool) $sponsor->fresh()->is_active);
        $this->assertNotNull(Sponsor::find($sponsor->id));
    }

    public function test_paket_gratis_dialihkan_ke_upgrade_untuk_sponsor_dan_tenant()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->expiredTrial()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        Livewire::actingAs($user)->test(SponsorIndex::class)
            ->assertRedirect(route('eventner.billing.upgrade'));

        Livewire::actingAs($user)->test(TenantIndex::class)
            ->assertRedirect(route('eventner.billing.upgrade'));

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    // ────────────────────────────────────────────────
    // Tenant / stand
    // ────────────────────────────────────────────────

    public function test_tenant_baru_tersimpan_beserta_logonya()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(TenantIndex::class)
            ->set('name', 'Kedai Bakso Pak Kumis')
            ->set('type', 'culinary')
            ->set('description', 'Bakso urat jumbo.')
            ->set('sort_order', 1)
            ->set('logo', UploadedFile::fake()->image('tenant.png'))
            ->call('save')
            ->assertHasNoErrors();

        $tenant = Tenant::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('Kedai Bakso Pak Kumis', $tenant->name);
        $this->assertSame('Bakso urat jumbo.', $tenant->description);
        Storage::disk('public')->assertExists($tenant->logo);
    }

    public function test_deskripsi_tenant_kosong_disimpan_sebagai_null()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(TenantIndex::class)
            ->set('name', 'Stand Kosong')
            ->set('type', 'bazaar')
            ->set('description', '')
            ->set('sort_order', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Tenant::where('eventner_id', $eventner->id)->firstOrFail()->description);
    }

    public function test_ganti_logo_tenant_menghapus_berkas_lama()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $lama = 'tenants/lama.png';
        Storage::disk('public')->put($lama, 'isi-lama');

        $tenant = Tenant::factory()->create([
            'eventner_id' => $eventner->id,
            'logo' => $lama,
        ]);

        Livewire::actingAs($user)->test(TenantIndex::class)
            ->call('edit', $tenant->id)
            ->set('logo', UploadedFile::fake()->image('baru.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($tenant->fresh()->logo);
    }

    public function test_tenant_event_lain_tidak_bisa_dihapus()
    {
        [$user] = $this->panitia();
        $asing = $this->eventLain();

        $tenant = Tenant::factory()->create(['eventner_id' => $asing->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($user)->test(TenantIndex::class)->call('delete', $tenant->id);
    }

    public function test_menyimpan_tenant_mode_edit_tidak_menggandakan_baris()
    {
        [$user, $eventner] = $this->panitia();

        $tenant = Tenant::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'Nama Lama',
        ]);

        Livewire::actingAs($user)->test(TenantIndex::class)
            ->call('edit', $tenant->id)
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Tenant::where('eventner_id', $eventner->id)->count());
        $this->assertSame('Nama Baru', $tenant->fresh()->name);
    }

    // ────────────────────────────────────────────────
    // Galeri
    // ────────────────────────────────────────────────

    public function test_unggah_foto_menyimpan_berkas_dan_keterangan()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(GalleryIndex::class)
            ->set('caption', 'Pembukaan')
            ->set('newImage', UploadedFile::fake()->image('foto.jpg'))
            ->call('unggahFoto')
            ->assertHasNoErrors();

        $foto = EventGallery::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('Pembukaan', $foto->caption);
        Storage::disk('public')->assertExists($foto->image);
    }

    public function test_keterangan_kosong_disimpan_sebagai_null()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(GalleryIndex::class)
            ->set('caption', '')
            ->set('newImage', UploadedFile::fake()->image('foto.jpg'))
            ->call('unggahFoto')
            ->assertHasNoErrors();

        $this->assertNull(EventGallery::where('eventner_id', $eventner->id)->firstOrFail()->caption);
    }

    public function test_unggahan_bukan_gambar_ditolak()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(GalleryIndex::class)
            ->set('newImage', UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'))
            ->call('unggahFoto')
            ->assertHasErrors('newImage');

        $this->assertSame(0, EventGallery::where('eventner_id', $eventner->id)->count());
    }

    public function test_unggahan_di_atas_lima_megabita_ditolak()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(GalleryIndex::class)
            ->set('newImage', UploadedFile::fake()->image('besar.jpg')->size(6000))
            ->call('unggahFoto')
            ->assertHasErrors('newImage');

        $this->assertSame(0, EventGallery::where('eventner_id', $eventner->id)->count());
    }

    public function test_unggahan_berikutnya_menaikkan_sort_order()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        EventGallery::factory()->create(['eventner_id' => $eventner->id, 'sort_order' => 7]);

        Livewire::actingAs($user)->test(GalleryIndex::class)
            ->set('newImage', UploadedFile::fake()->image('foto.jpg'))
            ->call('unggahFoto')
            ->assertHasNoErrors();

        $baru = EventGallery::where('eventner_id', $eventner->id)->orderByDesc('id')->firstOrFail();

        $this->assertSame(8, (int) $baru->sort_order);
    }

    public function test_hapus_foto_ikut_menghapus_berkasnya()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $berkas = 'galleries/hapus.jpg';
        Storage::disk('public')->put($berkas, 'isi');

        $foto = EventGallery::factory()->create([
            'eventner_id' => $eventner->id,
            'image' => $berkas,
        ]);

        Livewire::actingAs($user)->test(GalleryIndex::class)->call('delete', $foto->id);

        $this->assertNull(EventGallery::find($foto->id));
        Storage::disk('public')->assertMissing($berkas);
    }

    public function test_foto_event_lain_tidak_bisa_dihapus()
    {
        [$user] = $this->panitia();
        $asing = $this->eventLain();

        $foto = EventGallery::factory()->create(['eventner_id' => $asing->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($user)->test(GalleryIndex::class)->call('delete', $foto->id);
    }

    public function test_galeri_hanya_menampilkan_foto_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();
        $asing = $this->eventLain();

        EventGallery::factory()->create(['eventner_id' => $eventner->id, 'caption' => 'Punya sendiri']);
        EventGallery::factory()->create(['eventner_id' => $asing->id, 'caption' => 'Punya tetangga']);

        $caption = Livewire::actingAs($user)->test(GalleryIndex::class)
            ->viewData('images')
            ->pluck('caption')
            ->all();

        $this->assertSame(['Punya sendiri'], $caption);
    }

    // ────────────────────────────────────────────────
    // FAQ
    // ────────────────────────────────────────────────

    public function test_faq_baru_tersimpan_dengan_sort_order_berikutnya()
    {
        [$user, $eventner] = $this->panitia();

        EventFaq::factory()->create(['eventner_id' => $eventner->id, 'sort_order' => 4]);

        Livewire::actingAs($user)->test(FaqIndex::class)
            ->set('question', 'Kapan pendaftaran ditutup?')
            ->set('answer', 'Sampai 30 November.')
            ->call('save')
            ->assertHasNoErrors();

        $baru = EventFaq::where('eventner_id', $eventner->id)->orderByDesc('id')->firstOrFail();

        $this->assertSame('Kapan pendaftaran ditutup?', $baru->question);
        $this->assertSame(5, (int) $baru->sort_order);
    }

    public function test_menyimpan_faq_mode_edit_memperbarui_bukan_menggandakan()
    {
        [$user, $eventner] = $this->panitia();

        $faq = EventFaq::factory()->create([
            'eventner_id' => $eventner->id,
            'question' => 'Pertanyaan lama',
            'answer' => 'Jawaban lama',
        ]);

        Livewire::actingAs($user)->test(FaqIndex::class)
            ->call('edit', $faq->id)
            ->set('answer', 'Jawaban baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, EventFaq::where('eventner_id', $eventner->id)->count());
        $this->assertSame('Jawaban baru', $faq->fresh()->answer);
        $this->assertSame('Pertanyaan lama', $faq->fresh()->question);
    }

    public function test_pertanyaan_dan_jawaban_faq_dibersihkan_dari_tag_html()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(FaqIndex::class)
            ->set('question', '<i>Kapan?</i>')
            ->set('answer', '<p>Besok</p>')
            ->call('save')
            ->assertHasNoErrors();

        $faq = EventFaq::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('Kapan?', $faq->question);
        $this->assertSame('Besok', $faq->answer);
    }

    public function test_pertanyaan_faq_di_escape_saat_dirender()
    {
        [$user, $eventner] = $this->panitia();

        EventFaq::factory()->create([
            'eventner_id' => $eventner->id,
            'question' => 'Aman <script>alert(1)</script>',
        ]);

        $html = Livewire::actingAs($user)->test(FaqIndex::class)->html();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_pertanyaan_faq_wajib_dan_dibatasi_panjangnya()
    {
        [$user, $eventner] = $this->panitia();

        $komponen = Livewire::actingAs($user)->test(FaqIndex::class)
            ->set('question', '')
            ->set('answer', 'Ada jawaban')
            ->call('save')
            ->assertHasErrors('question');

        $komponen->set('question', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors('question');

        $this->assertSame(0, EventFaq::where('eventner_id', $eventner->id)->count());
    }

    public function test_faq_event_lain_tidak_bisa_disunting_atau_dihapus()
    {
        [$user] = $this->panitia();
        $asing = $this->eventLain();

        $faq = EventFaq::factory()->create(['eventner_id' => $asing->id]);

        foreach (['edit', 'delete'] as $aksi) {
            try {
                Livewire::actingAs($user)->test(FaqIndex::class)->call($aksi, $faq->id);
                $this->fail("Aksi {$aksi} seharusnya menolak id milik event lain.");
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
                // memang begitu seharusnya
            }
        }

        $this->assertNotNull(EventFaq::find($faq->id));
    }

    public function test_daftar_faq_diurutkan_dari_sort_order()
    {
        [$user, $eventner] = $this->panitia();

        EventFaq::factory()->create(['eventner_id' => $eventner->id, 'question' => 'C', 'sort_order' => 3]);
        EventFaq::factory()->create(['eventner_id' => $eventner->id, 'question' => 'A', 'sort_order' => 1]);
        EventFaq::factory()->create(['eventner_id' => $eventner->id, 'question' => 'B', 'sort_order' => 2]);

        $urutan = Livewire::actingAs($user)->test(FaqIndex::class)
            ->viewData('faqs')
            ->pluck('question')
            ->all();

        $this->assertSame(['A', 'B', 'C'], $urutan);
    }

    // ────────────────────────────────────────────────
    // Judge — berkas foto
    // ────────────────────────────────────────────────

    public function test_ganti_foto_juri_menghapus_berkas_lama()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $lama = 'judges/lama.png';
        Storage::disk('public')->put($lama, 'isi-lama');

        $juri = Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juri Lama',
            'photo' => $lama,
        ]);

        Livewire::actingAs($user)->test(JudgeIndex::class)
            ->call('edit', $juri->id)
            ->set('photo', UploadedFile::fake()->image('baru.png'))
            ->call('save')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($juri->fresh()->photo);
    }

    public function test_hapus_juri_ikut_menghapus_fotonya()
    {
        Storage::fake('public');
        [$user, $eventner] = $this->panitia();

        $foto = 'judges/hapus.png';
        Storage::disk('public')->put($foto, 'isi');

        $juri = Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juri Hapus',
            'photo' => $foto,
        ]);

        Livewire::actingAs($user)->test(JudgeIndex::class)->call('delete', $juri->id);

        $this->assertNull(Judge::find($juri->id));
        Storage::disk('public')->assertMissing($foto);
    }

    // ────────────────────────────────────────────────
    // Rundown
    // ────────────────────────────────────────────────

    public function test_item_rundown_manual_tersimpan()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('title', 'Upacara pembukaan')
            ->set('startTime', '07:30')
            ->set('endTime', '08:00')
            ->call('save')
            ->assertHasNoErrors();

        $item = EventRundown::where('eventner_id', $eventner->id)->firstOrFail();

        $this->assertSame('Upacara pembukaan', $item->title);
        $this->assertSame('07:30', $item->start_time->format('H:i'));
        $this->assertSame('08:00', $item->end_time->format('H:i'));
    }

    public function test_jam_selesai_rundown_harus_setelah_jam_mulai()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('title', 'Terbalik')
            ->set('startTime', '10:00')
            ->set('endTime', '09:00')
            ->call('save')
            ->assertHasErrors('endTime');

        $this->assertSame(0, EventRundown::where('eventner_id', $eventner->id)->count());
    }

    public function test_rundown_menerima_format_jam_yang_salah_sebagai_galat()
    {
        [$user, $eventner] = $this->panitia();

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('title', 'Salah format')
            ->set('startTime', '7 pagi')
            ->call('save')
            ->assertHasErrors('startTime');
    }

    public function test_generate_rundown_dari_undian_membuat_jam_berantai()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = $this->kategori($eventner);

        foreach ([1, 2, 3] as $nomor) {
            Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $kategori->id,
                'nama_sekolah' => "Sekolah {$nomor}",
                'urutan_tampil' => $nomor,
            ]);
        }

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('importCategoryId', $kategori->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 10)
            ->call('generateFromDrawing')
            ->assertHasNoErrors();

        $items = EventRundown::where('eventner_id', $eventner->id)
            ->whereNotNull('source_category_id')
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(3, $items);
        $this->assertSame(
            [['08:00', '08:10'], ['08:10', '08:20'], ['08:20', '08:30']],
            $items->map(fn ($i) => [$i->start_time->format('H:i'), $i->end_time->format('H:i')])->all()
        );
        $this->assertStringContainsString('Sekolah 1', $items->first()->title);
    }

    public function test_generate_rundown_menolak_kategori_tanpa_hasil_undian()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = $this->kategori($eventner);

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('importCategoryId', $kategori->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 10)
            ->call('generateFromDrawing')
            ->assertHasErrors('importCategoryId');

        $this->assertSame(0, EventRundown::where('eventner_id', $eventner->id)->count());
    }

    public function test_generate_rundown_menolak_kategori_milik_event_lain()
    {
        [$user, $eventner] = $this->panitia();
        $asing = $this->eventLain();
        $kategoriAsing = $this->kategori($asing);

        Registration::factory()->create([
            'eventner_id' => $asing->id,
            'competition_category_id' => $kategoriAsing->id,
            'urutan_tampil' => 1,
        ]);

        Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('importCategoryId', $kategoriAsing->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 10)
            ->call('generateFromDrawing')
            ->assertHasErrors('importCategoryId');

        $this->assertSame(0, EventRundown::where('eventner_id', $eventner->id)->count());
    }

    public function test_generate_ulang_tidak_menghapus_item_manual()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = $this->kategori($eventner);

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
            'urutan_tampil' => 1,
        ]);

        $manual = EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Istirahat',
            'start_time' => '12:00',
            'sort_order' => 1,
        ]);

        $komponen = Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('importCategoryId', $kategori->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 10);

        $komponen->call('generateFromDrawing')->assertHasNoErrors();
        $komponen->call('generateFromDrawing')->assertHasNoErrors();

        $this->assertSame(1, EventRundown::where('eventner_id', $eventner->id)
            ->whereNotNull('source_category_id')->count(), 'Generate ulang harus mengganti, bukan menumpuk.');
        $this->assertNotNull(EventRundown::find($manual->id), 'Item manual tidak boleh ikut terhapus.');
    }

    public function test_mengubah_durasi_menghitung_ulang_jam_pasukan_berikutnya()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = $this->kategori($eventner);

        foreach ([1, 2, 3] as $nomor) {
            Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $kategori->id,
                'urutan_tampil' => $nomor,
            ]);
        }

        $komponen = Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('importCategoryId', $kategori->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 10);

        $komponen->call('generateFromDrawing');

        $pertama = EventRundown::where('eventner_id', $eventner->id)
            ->orderBy('sort_order')->firstOrFail();

        $this->ubahDurasi($komponen, $pertama->id, 20)->assertHasNoErrors();

        $items = EventRundown::where('eventner_id', $eventner->id)
            ->orderBy('sort_order')->get();

        $this->assertSame(
            [['08:00', '08:20'], ['08:20', '08:30'], ['08:30', '08:40']],
            $items->map(fn ($i) => [$i->start_time->format('H:i'), $i->end_time->format('H:i')])->all()
        );
    }

    public function test_durasi_nol_ditolak()
    {
        [$user, $eventner] = $this->panitia();
        $kategori = $this->kategori($eventner);

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $kategori->id,
            'urutan_tampil' => 1,
        ]);

        $komponen = Livewire::actingAs($user)->test(RundownIndex::class)
            ->set('importCategoryId', $kategori->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 10);

        $komponen->call('generateFromDrawing');

        $item = EventRundown::where('eventner_id', $eventner->id)->firstOrFail();

        $this->ubahDurasi($komponen, $item->id, 0)
            ->assertHasErrors("durations.{$item->id}");
    }

    public function test_naik_turun_rundown_menukar_urutan()
    {
        [$user, $eventner] = $this->panitia();

        $pertama = EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Pertama',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);
        $kedua = EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Kedua',
            'start_time' => '09:00',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($user)->test(RundownIndex::class)->call('moveUp', $kedua->id);

        $this->assertSame(2, (int) $pertama->fresh()->sort_order);
        $this->assertSame(1, (int) $kedua->fresh()->sort_order);
    }

    public function test_naik_pada_item_teratas_tidak_mengubah_apa_pun()
    {
        [$user, $eventner] = $this->panitia();

        $pertama = EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Pertama',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);
        $kedua = EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Kedua',
            'start_time' => '09:00',
            'sort_order' => 2,
        ]);

        Livewire::actingAs($user)->test(RundownIndex::class)->call('moveUp', $pertama->id);

        $this->assertSame(1, (int) $pertama->fresh()->sort_order);
        $this->assertSame(2, (int) $kedua->fresh()->sort_order);
    }

    public function test_item_rundown_event_lain_tidak_bisa_disunting_dihapus_atau_digeser()
    {
        [$user] = $this->panitia();
        $asing = $this->eventLain();

        $item = EventRundown::create([
            'eventner_id' => $asing->id,
            'title' => 'Milik tetangga',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);

        foreach (['edit', 'delete', 'moveUp', 'moveDown'] as $aksi) {
            try {
                Livewire::actingAs($user)->test(RundownIndex::class)->call($aksi, $item->id);
                $this->fail("Aksi {$aksi} seharusnya menolak id milik event lain.");
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
                // memang begitu seharusnya
            }
        }

        $this->assertNotNull(EventRundown::find($item->id));
    }

    public function test_daftar_rundown_hanya_memuat_item_event_sendiri()
    {
        [$user, $eventner] = $this->panitia();
        $asing = $this->eventLain();

        EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Punya sendiri',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);
        EventRundown::create([
            'eventner_id' => $asing->id,
            'title' => 'Punya tetangga',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);

        $judul = Livewire::actingAs($user)->test(RundownIndex::class)
            ->viewData('items')
            ->pluck('title')
            ->all();

        $this->assertSame(['Punya sendiri'], $judul);
    }

    /**
     * `wire:model` + `wire:change` sampai di server sebagai SATU permintaan.
     * `set()` lalu `call()` adalah dua permintaan terpisah, dan di antaranya
     * `render()` menimpa `durations` dari DB — jadi nilai yang dikirim `set()`
     * sudah hilang saat `updateDuration()` membaca. `update()` mengirim
     * keduanya sekaligus, persis seperti peramban.
     */
    private function ubahDurasi($komponen, int $id, int $menit)
    {
        return $komponen->update(
            calls: [['method' => 'updateDuration', 'params' => [$id], 'path' => '']],
            updates: ["durations.{$id}" => $menit],
        );
    }

    /** Kategori anak milik event — rundown hanya menerima kategori anak. */
    private function kategori(Eventner $eventner): CompetitionCategory
    {
        $induk = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);

        return CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $induk->id,
        ]);
    }
}
