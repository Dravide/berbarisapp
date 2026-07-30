<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    private function getRegistration(Request $request): Registration
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        abort_unless($token, 401);
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
