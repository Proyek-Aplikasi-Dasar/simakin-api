<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Gelombang;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GelombangImport implements Import, WithHeadingRow
{
    use ImportsRows;

    protected function rules(): array
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

    protected function prepareRow(array $row): array
    {
        return $this->normalizeRow($row);
    }

    protected function saveRow(array $data): void
    {
        Gelombang::create([
            'tahun_ajaran' => (string) $data['tahun_ajaran'],
            'nama' => (string) $data['nama'],
            'daftar_mulai' => $data['daftar_mulai'],
            'daftar_selesai' => $data['daftar_selesai'],
            'praktik_mulai' => $data['praktik_mulai'],
            'praktik_selesai' => $data['praktik_selesai'],
        ]);
    }

    protected function messages(): array
    {
        return [
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'nama.required' => 'Nama gelombang wajib diisi.',
            'daftar_mulai.required' => 'Tanggal mulai pendaftaran wajib diisi.',
            'daftar_selesai.required' => 'Tanggal selesai pendaftaran wajib diisi.',
            'daftar_selesai.after_or_equal' => 'Tanggal selesai pendaftaran harus setelah atau sama dengan tanggal mulai.',
            'praktik_mulai.required' => 'Tanggal mulai praktik wajib diisi.',
            'praktik_selesai.required' => 'Tanggal selesai praktik wajib diisi.',
            'praktik_selesai.after_or_equal' => 'Tanggal selesai praktik harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
