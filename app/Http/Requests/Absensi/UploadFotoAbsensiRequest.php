<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class UploadFotoAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        return [
            'absensi_id' => ['required', 'integer', 'exists:absensi_siswa,id'],
            'foto_masuk' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'absensi_id.required' => 'ID absensi wajib diisi.',
            'absensi_id.exists' => 'Absensi tidak ditemukan.',
            'foto_masuk.required' => 'Foto wajib diunggah.',
            'foto_masuk.image' => 'File harus berupa gambar.',
            'foto_masuk.mimes' => 'Foto harus berformat jpeg, jpg, atau png.',
            'foto_masuk.max' => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
