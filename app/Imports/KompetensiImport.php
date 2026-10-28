<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Jurusan;
use App\Models\Kompetensi;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KompetensiImport implements Import, WithHeadingRow
{
    use ImportsRows;

    protected function rules(): array
    {
        return [
            'jurusan' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, \Closure $fail) {
                if ($this->resolveJurusanId((string) $value) === null) {
                    $fail('Jurusan tidak ditemukan.');
                }
            }],
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50', Rule::unique('kompetensi', 'kode')],
        ];
    }

    protected function prepareRow(array $row): array
    {
        return $this->normalizeRow($row);
    }

    protected function saveRow(array $data): void
    {
        $jurusanId = $this->resolveJurusanId((string) $data['jurusan']);
        Kompetensi::create([
            'jurusan_id' => $jurusanId,
            'nama' => (string) $data['nama'],
            'kode' => $data['kode'] ?? null,
        ]);
    }

    protected function messages(): array
    {
        return [
            'jurusan.required' => 'Jurusan wajib diisi.',
            'nama.required' => 'Nama kompetensi wajib diisi.',
            'kode.unique' => 'Kode kompetensi sudah terdaftar.',
        ];
    }

    private function resolveJurusanId(string $value): ?int
    {
        $normalized = strtolower(trim($value));

        $jurusan = Jurusan::query()
            ->whereRaw('LOWER(kode) = ?', [$normalized])
            ->orWhereRaw('LOWER(nama) = ?', [$normalized])
            ->first();

        return $jurusan?->id;
    }
}
