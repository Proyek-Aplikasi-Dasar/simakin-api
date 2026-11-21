<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaktuAbsenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'mulai' => ['required', 'date_format:H:i'],
            'selesai' => ['required', 'date_format:H:i', 'after:mulai'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mulai.required' => 'Jam mulai wajib diisi.',
            'mulai.date_format' => 'Format jam mulai harus HH:mm.',
            'selesai.required' => 'Jam selesai wajib diisi.',
            'selesai.date_format' => 'Format jam selesai harus HH:mm.',
            'selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
        ];
    }
}
