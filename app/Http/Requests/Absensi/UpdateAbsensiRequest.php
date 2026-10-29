<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:hadir,izin,sakit,libur'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'foto_masuk' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'keterangan' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status_submit' => ['sometimes', 'string', 'in:success,failed'],
        ];
    }
}
