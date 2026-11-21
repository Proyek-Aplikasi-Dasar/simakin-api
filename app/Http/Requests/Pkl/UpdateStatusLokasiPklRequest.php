<?php

namespace App\Http\Requests\Pkl;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusLokasiPklRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,active,completed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus pending, active, completed, atau cancelled.',
        ];
    }
}
