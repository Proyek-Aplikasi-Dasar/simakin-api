<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'keterangan' => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn () => in_array($this->input('status'), ['izin', 'sakit', 'libur'], true))],
            'jam_masuk' => ['nullable', 'date_format:H:i,H:i:s'],
            'jam_shift_mulai' => ['nullable', 'date_format:H:i,H:i:s'],
            'jam_shift_selesai' => ['nullable', 'date_format:H:i,H:i:s', 'after:jam_shift_mulai'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus hadir, izin, sakit, atau libur.',
            'keterangan.required' => 'Keterangan wajib diisi untuk status izin/sakit/libur.',
            'jam_shift_selesai.after' => 'Jam shift selesai harus setelah jam shift mulai.',
        ];
    }
}
