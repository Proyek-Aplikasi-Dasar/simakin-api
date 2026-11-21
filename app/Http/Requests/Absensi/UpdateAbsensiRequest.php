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

    public function messages(): array
    {
        return [
            'status.in' => 'Status harus hadir, izin, sakit, atau libur.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'foto_masuk.image' => 'File harus berupa gambar.',
            'foto_masuk.mimes' => 'Foto harus berformat jpeg, jpg, atau png.',
            'foto_masuk.max' => 'Ukuran foto maksimal 5MB.',
            'jam_shift_mulai.date_format' => 'Format jam shift mulai harus HH:mm.',
            'jam_shift_selesai.date_format' => 'Format jam shift selesai harus HH:mm.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
            'berkas_pendukung.mimes' => 'Berkas harus berformat pdf, jpeg, jpg, atau png.',
            'berkas_pendukung.max' => 'Ukuran berkas maksimal 5MB.',
            'status_submit.in' => 'Status submit harus success atau failed.',
        ];
    }
}
