<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class KoreksiAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:hadir,izin,sakit,libur'],
            'keterangan' => ['required', 'string', 'max:1000'],
            'jam_masuk' => ['nullable', 'date_format:H:i,H:i:s'],
        ];
    }
}
