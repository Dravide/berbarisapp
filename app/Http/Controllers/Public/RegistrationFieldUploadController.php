<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Models\RegistrationFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Unggah berkas field pendaftaran dari portal magic link.
 *
 * Endpoint HTTP biasa, bukan Livewire: properti upload Livewire harus ada saat
 * compile, sedangkan field builder jumlahnya dinamis. FilePond menembak ke URL
 * ini langsung, jadi field berkas apa pun — termasuk yang baru dibuat panitia —
 * bisa diunggah tanpa satu properti per field.
 *
 * Otentikasi memakai magic_token di URL: tautan portal itu sendiri yang jadi
 * kredensialnya, sama seperti halaman /reg/{token}.
 *
 * FilePond memanggil process saat berkas dipilih dan revert saat dibatalkan.
 * Keduanya langsung menyimpan/menghapus nilai, jadi tidak ada state berkas yang
 * perlu ditahan sampai tombol simpan ditekan.
 */
class RegistrationFieldUploadController extends Controller
{
    public function store(Request $request, string $token, int $fieldId)
    {
        $registration = Registration::where('magic_token', $token)->firstOrFail();
        $field = $this->fieldFor($registration, $fieldId);

        $aturan = $field->type === 'image'
            ? 'required|image|max:' . $this->maxKb($field)
            : 'required|file|mimes:pdf,jpg,jpeg,png|max:' . $this->maxKb($field);

        $request->validate(['file' => $aturan], [
            'file.required' => $field->label . ' wajib diunggah.',
            'file.image' => $field->label . ' harus berupa gambar.',
            'file.mimes' => $field->label . ' harus berupa PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran ' . $field->label . ' melebihi batas ' . $this->maxKb($field) . ' KB.',
        ]);

        $path = $request->file('file')->store('registrations/fields/' . $registration->id, 'public');

        $this->simpan($registration, $field, $path);

        return response()->json([
            'path' => $path,
            'url' => asset('storage/' . $path),
        ]);
    }

    /**
     * Batalkan unggahan: hapus berkas dari disk dan kosongkan nilainya.
     *
     * FilePond mengirim nama berkas dari respon process — di sini dikirim ulang
     * agar berkas lama benar-benar terhapus, bukan hanya dilepas dari UI.
     */
    public function destroy(Request $request, string $token, int $fieldId)
    {
        $registration = Registration::where('magic_token', $token)->firstOrFail();
        $field = $this->fieldFor($registration, $fieldId);

        $lama = $field->builtin_source
            ? $registration->{$field->builtin_source}
            : $registration->fieldValues()->where('registration_field_id', $field->id)->value('value');

        if ($lama) {
            Storage::disk('public')->delete($lama);
        }

        $this->simpan($registration, $field, null);

        return response()->json(['ok' => true]);
    }

    /**
     * Field harus milik event registrasi ini, aktif, dan bertipe berkas.
     * Tanpa cek ini, id field event lain bisa dikirim ke URL mana pun.
     */
    private function fieldFor(Registration $registration, int $fieldId): RegistrationField
    {
        $field = RegistrationField::where('eventner_id', $registration->eventner_id)
            ->where('is_active', true)
            ->findOrFail($fieldId);

        abort_unless($field->isFile(), 404);

        return $field;
    }

    private function maxKb(RegistrationField $field): int
    {
        return $field->max_kb ?: 5120;
    }

    /**
     * Tulis nilai ke kolom registrations (field bawaan) atau ke
     * registration_field_values (field buatan panitia).
     */
    private function simpan(Registration $registration, RegistrationField $field, ?string $path): void
    {
        if ($field->builtin_source) {
            $registration->update([$field->builtin_source => $path]);

            return;
        }

        RegistrationFieldValue::updateOrCreate(
            [
                'registration_id' => $registration->id,
                'registration_field_id' => $field->id,
            ],
            ['value' => $path]
        );
    }
}
