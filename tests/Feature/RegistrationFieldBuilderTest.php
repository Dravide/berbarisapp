<?php

namespace Tests\Feature;

use App\Livewire\Eventner\RegistrationField\Index;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Field builder formulir pendaftaran.
 *
 * Panitia mengatur sendiri field apa yang diminta saat pendaftaran. Yang
 * dijaga di sini: field bawaan tidak bisa dihapus, field yang sudah terisi
 * jawaban tidak bisa dihapus, dan panitia satu event tidak bisa menyentuh
 * field milik event lain.
 */
class RegistrationFieldBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function makeEventner(): Eventner
    {
        $user = User::factory()->create(['role' => 'Eventner']);

        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $user->update(['eventner_id' => $eventner->id]);

        return $eventner->fresh();
    }

    private function actingAsEventner(Eventner $eventner): User
    {
        return User::find($eventner->user_id);
    }

    /**
     * Event baru sudah punya definisi field lengkap sejak dibuat — hook
     * Eventner::created memanggil ensureDefaults(), bukan builder yang
     * mengisinya saat pertama dibuka.
     */
    public function test_event_baru_langsung_punya_field_bawaan()
    {
        $eventner = $this->makeEventner();

        $keys = RegistrationField::where('eventner_id', $eventner->id)->pluck('field_key');

        $this->assertContains('nama_sekolah', $keys);
        $this->assertContains('surat_tugas', $keys);
        $this->assertSame(count(RegistrationField::defaults($eventner)), $keys->count());
    }

    public function test_tambah_field_baru()
    {
        $eventner = $this->makeEventner();

        Livewire::actingAs($this->actingAsEventner($eventner))
            ->test(Index::class)
            ->call('create')
            ->set('label', 'Asal Kabupaten')
            ->set('type', 'text')
            ->set('is_required', true)
            ->call('save')
            ->assertHasNoErrors();

        $field = RegistrationField::where('eventner_id', $eventner->id)
            ->where('label', 'Asal Kabupaten')
            ->first();

        $this->assertNotNull($field);
        $this->assertNotSame('asal_kabupaten', $field->field_key);
        $this->assertSame('asal_kabupaten_2', $field->field_key);
        $this->assertTrue((bool) $field->is_required);
        $this->assertFalse((bool) $field->is_builtin);
        $this->assertNull($field->builtin_source);
    }

    public function test_field_key_berbeda_saat_label_sama()
    {
        $eventner = $this->makeEventner();

        $komponen = Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class);

        foreach ([1, 2] as $ulang) {
            $komponen->call('create')
                ->set('label', 'Asal Kabupaten')
                ->call('save')
                ->assertHasNoErrors();
        }

        $keys = RegistrationField::where('eventner_id', $eventner->id)
            ->where('label', 'Asal Kabupaten')
            ->pluck('field_key')
            ->all();

        $this->assertCount(2, $keys);
        $this->assertSame(['asal_kabupaten_2', 'asal_kabupaten_3'], $keys);
    }

    public function test_tipe_pilihan_butuh_opsi()
    {
        $eventner = $this->makeEventner();

        Livewire::actingAs($this->actingAsEventner($eventner))
            ->test(Index::class)
            ->call('create')
            ->set('label', 'Kategori Umur')
            ->set('type', 'select')
            ->set('optionsText', '')
            ->call('save')
            ->assertHasErrors('optionsText');

        $this->assertSame(0, RegistrationField::where('eventner_id', $eventner->id)->where('label', 'Kategori Umur')->count());
    }

    public function test_opsi_diparse_dari_baris_teks()
    {
        $eventner = $this->makeEventner();

        Livewire::actingAs($this->actingAsEventner($eventner))
            ->test(Index::class)
            ->call('create')
            ->set('label', 'Kategori Umur')
            ->set('type', 'select')
            ->set('optionsText', "U13 | Usia 13\nU15")
            ->call('save')
            ->assertHasNoErrors();

        $field = RegistrationField::where('eventner_id', $eventner->id)->where('label', 'Kategori Umur')->firstOrFail();

        $this->assertSame([
            ['value' => 'U13', 'label' => 'Usia 13'],
            ['value' => 'U15', 'label' => 'U15'],
        ], $field->options);
    }

    public function test_field_bawaan_tidak_bisa_dihapus()
    {
        $eventner = $this->makeEventner();

        $komponen = Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class);
        $bawaan = RegistrationField::where('eventner_id', $eventner->id)->where('is_builtin', true)->firstOrFail();

        $komponen->call('delete', $bawaan->id)->assertOk();

        $this->assertDatabaseHas('registration_fields', ['id' => $bawaan->id]);
    }

    public function test_field_yang_sudah_terisi_tidak_bisa_dihapus()
    {
        $eventner = $this->makeEventner();

        $komponen = Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class);

        $field = RegistrationField::create([
            'eventner_id' => $eventner->id,
            'field_key' => 'asal_kabupaten_kota',
            'label' => 'Asal Kabupaten',
            'type' => 'text',
        ]);

        $registrasi = Registration::factory()->create(['eventner_id' => $eventner->id]);

        RegistrationFieldValue::create([
            'registration_id' => $registrasi->id,
            'registration_field_id' => $field->id,
            'value' => 'Bogor',
        ]);

        $komponen->call('delete', $field->id)->assertOk();

        $this->assertDatabaseHas('registration_fields', ['id' => $field->id]);
    }

    public function test_field_bawaan_tipenya_terkunci_di_edit()
    {
        $eventner = $this->makeEventner();

        $komponen = Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class);
        $logo = RegistrationField::where('eventner_id', $eventner->id)->where('field_key', 'logo_sekolah')->firstOrFail();

        $komponen->call('edit', $logo->id)
            ->set('label', 'Logo Sekolah (resmi)')
            ->set('type', 'text')
            ->call('save')
            ->assertHasNoErrors();

        $logo->refresh();

        $this->assertSame('image', $logo->type);
        $this->assertSame('Logo Sekolah (resmi)', $logo->label);
    }

    public function test_urutan_bisa_dinaikkan()
    {
        $eventner = $this->makeEventner();

        $komponen = Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class);

        $daftar = RegistrationField::where('eventner_id', $eventner->id)->orderBy('sort_order')->get();
        $pertama = $daftar[0];
        $kedua = $daftar[1];

        $komponen->call('moveUp', $kedua->id)->assertOk();

        // Yang dinaikkan harus menempati posisi pertama, dan yang tergeser turun.
        $urutan = RegistrationField::where('eventner_id', $eventner->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('field_key')
            ->all();

        $this->assertSame($kedua->field_key, $urutan[0]);
        $this->assertSame($pertama->field_key, $urutan[1]);
    }

    public function test_menonaktifkan_surat_tugas_menyinkronkan_kolom_lama()
    {
        $eventner = $this->makeEventner();
        $eventner->update(['surat_tugas_required' => true]);

        $komponen = Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class);
        $surat = RegistrationField::where('eventner_id', $eventner->id)->where('field_key', 'surat_tugas')->firstOrFail();

        $komponen->call('toggleActive', $surat->id)->assertOk();

        $this->assertFalse((bool) $surat->fresh()->is_active);
        $this->assertFalse((bool) $eventner->fresh()->surat_tugas_required);
    }

    /**
     * Toggle lama (kwitansi_required) hanya bilang "wajib", bukan "tampil".
     * Kalau ikut memetakan ke is_active, event dengan kwitansi_required=0
     * kehilangan field kwitansi sama sekali — panitia tidak punya cara
     * mengunggahnya. Jadi yang tidak diwajibkan tetap tampil, hanya tanpa
     * tanda wajib.
     */
    public function test_kwitansi_tidak_wajib_tetap_aktif()
    {
        $eventner = $this->makeEventner();
        $eventner->update(['kwitansi_required' => false]);

        Livewire::actingAs($this->actingAsEventner($eventner))->test(Index::class)->assertOk();

        $kwitansi = RegistrationField::where('eventner_id', $eventner->id)
            ->where('field_key', 'bukti_pendaftaran')
            ->firstOrFail();

        $this->assertFalse((bool) $kwitansi->is_required);
        $this->assertTrue((bool) $kwitansi->is_active);
    }

    /**
     * Panitia event A tidak boleh menyentuh field milik event B — semua query
     * builder di-scope ke eventner_id.
     */
    public function test_lintas_tenant_tidak_bisa_menyentuh_field_event_lain()
    {
        $eventnerA = $this->makeEventner();
        $eventnerB = $this->makeEventner();

        $fieldB = RegistrationField::create([
            'eventner_id' => $eventnerB->id,
            'field_key' => 'milik_b',
            'label' => 'Milik Event B',
            'type' => 'text',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->actingAsEventner($eventnerA))
            ->test(Index::class)
            ->call('edit', $fieldB->id);
    }
}
