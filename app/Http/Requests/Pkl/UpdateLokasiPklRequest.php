<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLokasiPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['sometimes', 'integer', Rule::exists('siswa', 'id')],
            'perusahaan_id' => ['sometimes', 'integer', Rule::exists('perusahaan', 'id')],
            'guru_id' => ['sometimes', 'nullable', 'integer', Rule::exists('guru', 'id')],
            'gelombang_id' => ['sometimes', 'integer', Rule::exists('gelombang', 'id')],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'tanggal_mulai' => ['sometimes', 'nullable', 'date'],
            'tanggal_selesai' => ['sometimes', 'nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'pembimbing_industri' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'siswa_id.exists' => 'Siswa tidak ditemukan.',
            'perusahaan_id.exists' => 'Perusahaan tidak ditemukan.',
            'guru_id.exists' => 'Guru tidak ditemukan.',
            'gelombang_id.exists' => 'Gelombang tidak ditemukan.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'pembimbing_industri.max' => 'Pembimbing industri maksimal 255 karakter.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
        ];
    }
}
