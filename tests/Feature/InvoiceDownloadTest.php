<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidRegistration(): Registration
    {
        $user = User::factory()->eventner()->create();
        $eventner = Eventner::factory()->for($user, 'user')->create();

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'payment_status' => 'paid',
            'total_fee' => 500000,
            'payment_verified_at' => now(),
        ]);

        return $reg->setRelation('user', $user);
    }

    public function test_eventner_can_download_invoice_for_paid_registration(): void
    {
        $reg = $this->makePaidRegistration();

        $response = $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_magic_link_can_download_invoice_for_paid_registration(): void
    {
        $reg = $this->makePaidRegistration();

        $response = $this->get(route('magic.link.invoice', $reg->magic_token));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invoice_merges_multiple_paid_pasukan_of_same_school(): void
    {
        $reg = $this->makePaidRegistration();

        // Pasukan B & C dari sekolah yang sama (NPSN sama), sudah paid.
        Registration::factory()->for($reg->eventner, 'eventner')->pasukan('B')->create([
            'npsn' => $reg->npsn,
            'nama_sekolah' => $reg->nama_sekolah,
            'payment_status' => 'paid',
            'total_fee' => 300000,
            'payment_verified_at' => now(),
        ]);
        Registration::factory()->for($reg->eventner, 'eventner')->pasukan('C')->create([
            'npsn' => $reg->npsn,
            'nama_sekolah' => $reg->nama_sekolah,
            'payment_status' => 'paid',
            'total_fee' => 200000,
            'payment_verified_at' => now(),
        ]);

        $response = $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        // Nama file kwitansi memakai nama sekolah (gabungan), bukan per pasukan.
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('Invoice_' . str_replace(' ', '_', $reg->nama_sekolah), $disposition);
    }

    public function test_invoice_excludes_unpaid_siblings_of_same_school(): void
    {
        $reg = $this->makePaidRegistration();

        // Saudara sekolah yang sama, belum paid — tidak boleh ikut kwitansi.
        Registration::factory()->for($reg->eventner, 'eventner')->pasukan('B')->create([
            'npsn' => $reg->npsn,
            'nama_sekolah' => $reg->nama_sekolah,
            'payment_status' => 'unpaid',
            'total_fee' => 300000,
        ]);

        $response = $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id));

        $response->assertOk();
    }

    public function test_invoice_forbidden_when_unpaid(): void
    {
        $reg = $this->makePaidRegistration();
        $reg->update(['payment_status' => 'unpaid']);

        $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id))
            ->assertForbidden();

        $this->get(route('magic.link.invoice', $reg->magic_token))
            ->assertForbidden();
    }

    /**
     * Sejak `npsn` boleh kosong (migrasi 2026_09_21), penggabungan per sekolah
     * TIDAK boleh memakai NPSN kosong sebagai penanda: di MySQL `= NULL` tidak
     * pernah cocok, dan bila dianggap cocok, semua pendaftar tanpa NPSN dari
     * sekolah berbeda akan tercetak dalam satu kwitansi.
     */
    public function test_invoice_does_not_merge_npsn_less_registrations_of_different_schools(): void
    {
        $reg = $this->makePaidRegistration();
        $reg->update(['npsn' => null, 'nama_sekolah' => 'SMP Negeri 1 Uji']);

        // Sekolah BERBEDA, sama-sama tanpa NPSN, sudah paid.
        Registration::factory()->for($reg->eventner, 'eventner')->pasukan('B')->create([
            'npsn' => null,
            'nama_sekolah' => 'SMP Negeri 2 Uji',
            'payment_status' => 'paid',
            'total_fee' => 300000,
            'payment_verified_at' => now(),
        ]);

        $response = $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id));

        $response->assertOk();

        // Nama berkas memakai sekolah pendaftar ini — bukan sekolah lain, dan
        // bukan nama gabungan.
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('SMP_Negeri_1_Uji', $disposition);
    }

    /** Pasukan lain dari sekolah tanpa NPSN yang SAMA tetap digabung. */
    public function test_invoice_merges_npsn_less_pasukan_of_same_school(): void
    {
        $reg = $this->makePaidRegistration();
        $reg->update(['npsn' => null, 'nama_sekolah' => 'SMP Negeri 1 Uji']);

        Registration::factory()->for($reg->eventner, 'eventner')->pasukan('B')->create([
            'npsn' => null,
            'nama_sekolah' => 'SMP Negeri 1 Uji',
            'payment_status' => 'paid',
            'total_fee' => 300000,
            'payment_verified_at' => now(),
        ]);

        $response = $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id));

        $response->assertOk();
        $this->assertStringContainsString('SMP_Negeri_1_Uji', $response->headers->get('Content-Disposition'));
    }

    public function test_invoice_forbidden_when_pending_verification(): void
    {
        $reg = $this->makePaidRegistration();
        $reg->update(['payment_status' => 'pending_verification']);

        $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id))
            ->assertForbidden();

        $this->get(route('magic.link.invoice', $reg->magic_token))
            ->assertForbidden();
    }

    public function test_invoice_forbidden_for_other_eventner(): void
    {
        $reg = $this->makePaidRegistration();
        $otherEventner = Eventner::factory()->create();
        $otherUser = User::find($otherEventner->user_id);

        $this->actingAs($otherUser)
            ->get(route('eventner.participants.invoice', $reg->id))
            ->assertForbidden();
    }

    /**
     * Kwitansi bukan salinan formulir: isian tambahan panitia (wilayah,
     * pembina, berkas) tidak ikut tercetak — hanya identitas pokok.
     */
    public function test_invoice_hanya_mencetak_identitas_pokok(): void
    {
        $reg = $this->makePaidRegistration();

        // `asal_kabupaten` sudah dibuat otomatis oleh ensureDefaults().
        $field = \App\Models\RegistrationField::where('eventner_id', $reg->eventner_id)
            ->where('field_key', 'asal_kabupaten')
            ->firstOrFail();
        $field->update(['label' => 'Asal Kabupaten Uji', 'is_active' => true]);

        \App\Models\RegistrationFieldValue::create([
            'registration_id' => $reg->id,
            'registration_field_id' => $field->id,
            'value' => 'Bandar Lampung',
        ]);

        $html = view('eventner.participant.pdf_invoice', [
            'eventner' => $reg->eventner,
            'registrations' => collect([$reg->fresh(['fieldValues', 'competitionCategory'])]),
        ])->render();

        $this->assertStringContainsString($reg->nama_sekolah, $html);
        $this->assertStringContainsString($reg->npsn, $html);
        $this->assertStringNotContainsString('Asal Kabupaten Uji', $html);
        $this->assertStringNotContainsString('Bandar Lampung', $html);
        // Berkas tidak ikut dicetak sebagai baris "Terlampir".
        $this->assertStringNotContainsString('Surat Tugas', $html);
    }
}
