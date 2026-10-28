<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengajuanPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        return [
            'perusahaan_id' => ['required', 'integer', Rule::exists('perusahaan', 'id')],
            'gelombang_id' => ['required', 'integer', Rule::exists('gelombang', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'perusahaan_id.required' => 'Perusahaan wajib dipilih.',
            'perusahaan_id.exists' => 'Perusahaan tidak ditemukan.',
            'gelombang_id.required' => 'Gelombang wajib dipilih.',
            'gelombang_id.exists' => 'Gelombang tidak ditemukan.',
        ];
    }
}
