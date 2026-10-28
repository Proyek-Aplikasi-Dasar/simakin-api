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
        ];
    }
}
