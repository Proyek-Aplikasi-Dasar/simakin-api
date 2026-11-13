<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'siswa_id' => ['required', 'integer', Rule::exists('siswa', 'id')],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'string', 'in:hadir,izin,sakit,libur'],
            'jam_masuk' => ['nullable', 'date_format:H:i,H:i:s'],
            'jam_shift_mulai' => ['nullable', 'date_format:H:i,H:i:s'],
            'jam_shift_selesai' => ['nullable', 'date_format:H:i,H:i:s', 'after:jam_shift_mulai'],
            'keterangan' => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn () => in_array($this->input('status'), ['izin', 'sakit', 'libur'], true))],
            'berkas_pendukung' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'siswa_id.required' => 'Siswa wajib dipilih.',
            'siswa_id.exists' => 'Siswa tidak ditemukan.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status harus hadir, izin, sakit, atau libur.',
            'keterangan.required' => 'Keterangan wajib diisi untuk status izin/sakit/libur.',
            'jam_shift_selesai.after' => 'Jam shift selesai harus setelah jam shift mulai.',
        ];
    }
}
