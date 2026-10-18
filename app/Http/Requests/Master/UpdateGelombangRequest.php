<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGelombangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:100'],
            'daftar_mulai' => ['required', 'date'],
            'daftar_selesai' => ['required', 'date', 'after_or_equal:daftar_mulai'],
            'praktik_mulai' => ['required', 'date'],
            'praktik_selesai' => ['required', 'date', 'after_or_equal:praktik_mulai'],
        ];
    }
}
