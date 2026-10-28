<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Jurusan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PerusahaanImport implements Import, WithHeadingRow
{
    use ImportsRows;

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['required', 'string'],
            'kuota' => ['nullable', 'integer', 'min:0'],
            'email' => ['nullable', 'email', 'max:255'],
            'jurusan' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareRow(array $row): array
    {
        return $this->normalizeRow($row);
    }

    protected function saveRow(array $data): void
    {
        DB::transaction(function () use ($data) {
            $perusahaan = Perusahaan::create([
                'nama' => (string) $data['nama'],
                'alamat' => (string) $data['alamat'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'pimpinan' => $data['pimpinan'] ?? null,
                'telp' => $data['telp'] ?? null,
                'email' => $data['email'] ?? null,
                'deskripsi' => $data['deskripsi'] ?? null,
                'kuota' => $data['kuota'] ?? null,
            ]);

            if (! empty($data['jurusan'])) {
                $jurusanId = $this->resolveJurusanId((string) $data['jurusan']);
                if ($jurusanId) {
                    $perusahaan->jurusan()->syncWithoutDetaching($jurusanId);
                }
            }
        });
    }

    protected function messages(): array
    {
        return [
            'nama.required' => 'Nama perusahaan wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'kuota.integer' => 'Kuota harus berupa angka.',
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
