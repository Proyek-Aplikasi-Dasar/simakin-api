<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerusahaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'pimpinan' => ['nullable', 'string', 'max:255'],
            'telp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\\-\\s()]+$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'kuota' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'radius_presensi' => ['nullable', 'integer', 'min:0'],
            'pembimbing_industri' => ['nullable', 'string', 'max:255'],
            'bidang_usaha' => ['nullable', 'string', 'max:100'],
            'jurusan_ids' => ['nullable', 'array'],
            'jurusan_ids.*' => [Rule::exists('jurusan', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama perusahaan wajib diisi.',
            'nama.max' => 'Nama perusahaan maksimal 255 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'pimpinan.max' => 'Pimpinan maksimal 255 karakter.',
            'telp.max' => 'Telepon maksimal 20 karakter.',
            'telp.regex' => 'Format nomor telepon tidak valid.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'kuota.integer' => 'Kuota harus berupa angka.',
            'kuota.min' => 'Kuota minimal 0.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
            'radius_presensi.integer' => 'Radius presensi harus berupa angka.',
            'radius_presensi.min' => 'Radius presensi minimal 0.',
            'pembimbing_industri.max' => 'Pembimbing industri maksimal 255 karakter.',
            'bidang_usaha.max' => 'Bidang usaha maksimal 100 karakter.',
            'jurusan_ids.array' => 'Jurusan harus berupa array.',
            'jurusan_ids.*.exists' => 'Jurusan tidak ditemukan.',
        ];
    }
}
