<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKompetensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'jurusan_id' => ['required', Rule::exists('jurusan', 'id')],
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50', 'unique:kompetensi,kode'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'jurusan_id.required' => 'Jurusan wajib dipilih.',
            'jurusan_id.exists' => 'Jurusan tidak ditemukan.',
            'nama.required' => 'Nama kompetensi wajib diisi.',
            'nama.max' => 'Nama kompetensi maksimal 255 karakter.',
            'kode.unique' => 'Kode kompetensi sudah digunakan.',
            'kode.max' => 'Kode kompetensi maksimal 50 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }
}
