<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLokasiPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['required', 'integer', Rule::exists('siswa', 'id')],
            'perusahaan_id' => ['required', 'integer', Rule::exists('perusahaan', 'id')],
            'guru_id' => ['nullable', 'integer', Rule::exists('guru', 'id')],
            'gelombang_id' => ['required', 'integer', Rule::exists('gelombang', 'id')],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'pembimbing_industri' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'siswa_id.required' => 'Siswa wajib dipilih.',
            'siswa_id.exists' => 'Siswa tidak ditemukan.',
            'perusahaan_id.required' => 'Perusahaan wajib dipilih.',
            'perusahaan_id.exists' => 'Perusahaan tidak ditemukan.',
            'guru_id.exists' => 'Guru tidak ditemukan.',
            'gelombang_id.required' => 'Gelombang wajib dipilih.',
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
