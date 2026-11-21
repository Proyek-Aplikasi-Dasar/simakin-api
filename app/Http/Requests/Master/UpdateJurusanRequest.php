<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJurusanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['nullable', 'string', 'max:20', Rule::unique('jurusan', 'kode')->ignore($this->route('jurusan'))],
            'bidang_keahlian' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jurusan wajib diisi.',
            'nama.max' => 'Nama jurusan maksimal 100 karakter.',
            'kode.unique' => 'Kode jurusan sudah digunakan.',
            'kode.max' => 'Kode jurusan maksimal 20 karakter.',
            'bidang_keahlian.max' => 'Bidang keahlian maksimal 100 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }
}
