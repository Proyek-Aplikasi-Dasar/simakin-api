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
        ];
    }
}
