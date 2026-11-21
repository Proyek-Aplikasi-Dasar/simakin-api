<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGelombangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:100'],
            'daftar_mulai' => ['required', 'date'],
            'daftar_selesai' => ['required', 'date', 'after_or_equal:daftar_mulai'],
            'praktik_mulai' => ['required', 'date'],
            'praktik_selesai' => ['required', 'date', 'after_or_equal:praktik_mulai'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'tahun_ajaran.max' => 'Tahun ajaran maksimal 20 karakter.',
            'nama.required' => 'Nama gelombang wajib diisi.',
            'nama.max' => 'Nama gelombang maksimal 100 karakter.',
            'daftar_mulai.required' => 'Tanggal mulai pendaftaran wajib diisi.',
            'daftar_mulai.date' => 'Format tanggal mulai pendaftaran tidak valid.',
            'daftar_selesai.required' => 'Tanggal selesai pendaftaran wajib diisi.',
            'daftar_selesai.date' => 'Format tanggal selesai pendaftaran tidak valid.',
            'daftar_selesai.after_or_equal' => 'Tanggal selesai pendaftaran harus setelah atau sama dengan tanggal mulai.',
            'praktik_mulai.required' => 'Tanggal mulai praktik wajib diisi.',
            'praktik_mulai.date' => 'Format tanggal mulai praktik tidak valid.',
            'praktik_selesai.required' => 'Tanggal selesai praktik wajib diisi.',
            'praktik_selesai.date' => 'Format tanggal selesai praktik tidak valid.',
            'praktik_selesai.after_or_equal' => 'Tanggal selesai praktik harus setelah atau sama dengan tanggal mulai.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }
}
