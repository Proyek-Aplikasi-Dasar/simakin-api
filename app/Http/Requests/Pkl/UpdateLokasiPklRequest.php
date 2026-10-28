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
        ];
    }
}
