<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkApprovePengajuanPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists('pengajuan_pkl', 'id')],
            'guru_pembimbing_id' => ['nullable', 'integer', Rule::exists('guru', 'id')],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Daftar ID pengajuan wajib diisi.',
            'ids.array' => 'Daftar ID harus berupa array.',
            'ids.min' => 'Minimal satu ID pengajuan.',
            'ids.*.exists' => 'Pengajuan tidak ditemukan.',
            'guru_pembimbing_id.exists' => 'Guru pembimbing tidak ditemukan.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
