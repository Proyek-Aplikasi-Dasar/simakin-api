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
            'jam_shift_mulai' => ['sometimes', 'nullable', 'date_format:H:i'],
            'jam_shift_selesai' => ['sometimes', 'nullable', 'date_format:H:i'],
            'keterangan' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'berkas_pendukung' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
            'status_submit' => ['sometimes', 'string', 'in:success,failed'],
        ];
    }
}
