<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovePengajuanPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'guru_pembimbing_id' => ['nullable', 'integer', Rule::exists('guru', 'id')],
        ];
    }
}
