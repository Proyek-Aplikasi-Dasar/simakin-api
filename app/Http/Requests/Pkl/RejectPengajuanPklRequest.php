<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;

class RejectPengajuanPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'alasan_penolakan' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
