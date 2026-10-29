<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:hadir,izin,sakit,libur'],
            'tanggal' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'foto_masuk' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'status_submit' => ['nullable', 'string', 'in:success,failed'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status harus hadir, izin, sakit, atau libur.',
            'foto_masuk.image' => 'File harus berupa gambar.',
            'foto_masuk.mimes' => 'Foto harus berformat jpeg, jpg, atau png.',
            'foto_masuk.max' => 'Ukuran foto maksimal 5MB.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }
}
