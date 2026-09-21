<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    private function getRegistration(Request $request): Registration
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        abort_unless($token, 401);

        // Token harus milik model Registration — cegah token model lain
        // dipakai sebagai ID registrasi (sama seperti PortalController).
        abort_unless($token->tokenable_type === Registration::class, 403);

        return Registration::findOrFail($token->tokenable_id);
    }

    public function logo(Request $request)
    {
        return $this->uploadImage($request, 'logo', 'registrations/logos', 'logo_sekolah');
    }

    public function participantPhoto(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:3072',
            'participant_id' => 'nullable|exists:participants,id',
        ]);

        $reg = $this->getRegistration($request);
        $path = $request->file('file')->store('registrations/peserta', 'public');

        if ($request->participant_id) {
            $participant = $reg->participants()->findOrFail($request->participant_id);
            $participant->update(['foto' => $path]);
            return response()->json(['message' => 'Foto peserta berhasil diupload.', 'path' => $path]);
        }

        return response()->json(['message' => 'Foto berhasil diupload.', 'path' => $path]);
    }

    public function suratTugas(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,png|max:5120',
        ]);

        $reg = $this->getRegistration($request);
        $path = $request->file('file')->store('registrations/surat', 'public');
        $reg->update(['surat_tugas' => $path]);

        return response()->json(['message' => 'Surat tugas berhasil diupload.', 'path' => $path]);
    }

    public function pelatih(Request $request)
    {
        return $this->uploadImage($request, 'foto pelatih', 'registrations/pelatih', 'foto_pelatih');
    }

    public function danton(Request $request)
    {
        return $this->uploadImage($request, 'foto danton', 'registrations/danton', 'danton_foto');
    }

    public function paymentProof(Request $request)
    {
        return $this->uploadImage($request, 'bukti bayar', 'registrations/payment', 'payment_proof');
    }

    /**
     * Unggah berkas ke field builder mana pun (mis. surat tugas tambahan yang
     * dibuat panitia). Endpoint tetap yang dipakai app mobile: field_id dipilih
     * dari daftar field event, bukan nama kolom tetap.
     */
    public function registrationField(Request $request)
    {
        $request->validate([
            'field_id' => 'required|integer',
        ]);

        $reg = $this->getRegistration($request);

        $field = RegistrationField::where('eventner_id', $reg->eventner_id)
            ->where('is_active', true)
            ->findOrFail($request->field_id);

        abort_unless($field->isFile(), 422, 'Field ini bukan field unggahan.');

        $maxKb = $field->max_kb ?: 5120;

        $request->validate([
            'file' => $field->type === 'image'
                ? 'required|image|max:' . $maxKb
                : 'required|file|mimes:pdf,jpg,jpeg,png|max:' . $maxKb,
        ], [
            'file.max' => 'Ukuran ' . $field->label . ' melebihi batas ' . $maxKb . ' KB.',
            'file.mimes' => $field->label . ' harus berupa PDF, JPG, atau PNG.',
        ]);

        $path = $request->file('file')->store('registrations/fields/' . $reg->id, 'public');

        if ($field->builtin_source) {
            $reg->update([$field->builtin_source => $path]);
        } else {
            RegistrationFieldValue::updateOrCreate(
                [
                    'registration_id' => $reg->id,
                    'registration_field_id' => $field->id,
                ],
                ['value' => $path]
            );
        }

        return response()->json([
            'message' => $field->label . ' berhasil diupload.',
            'path' => $path,
            'url' => asset('storage/' . $path),
        ]);
    }

    private function uploadImage(Request $request, string $label, string $storagePath, string $column): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:3072',
        ]);

        $reg = $this->getRegistration($request);
        $path = $request->file('file')->store($storagePath, 'public');
        $reg->update([$column => $path]);

        return response()->json([
            'message' => "{$label} berhasil diupload.",
            'path' => $path,
            'url' => asset('storage/' . $path),
        ]);
    }
}
