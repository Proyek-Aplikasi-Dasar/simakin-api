<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Jurusan;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Contoh implementasi fondasi import Excel (belum diekspos sebagai endpoint).
 *
 * Kolom: nama (wajib), kode (opsional). Baris pertama dianggap header.
 */
class JurusanImport implements Import, WithHeadingRow
{
    use ImportsRows;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveRow(array $data): void
    {
        Jurusan::create($data);
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nama.required' => 'Nama jurusan wajib diisi.',
            'nama.max' => 'Nama jurusan maksimal 100 karakter.',
            'kode.max' => 'Kode jurusan maksimal 20 karakter.',
        ];
    }
}
