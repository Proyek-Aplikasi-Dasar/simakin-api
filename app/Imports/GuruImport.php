<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GuruImport implements Import, WithHeadingRow
{
    use ImportsRows;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'nip' => [
                'required',
                'string',
                'max:30',
                Rule::unique('guru', 'nip'),
                // NIP dipakai sebagai username login guru.
                Rule::unique('users', 'username'),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'telp' => ['nullable', 'string', 'max:20'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'kuota_bimbingan' => ['nullable', 'integer', 'min:0'],
            'alamat' => ['nullable', 'string'],
        ];
    }

    /**
     * Normalisasi NIP agar sel numerik tidak menjadi notasi ilmiah.
     *
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    protected function prepareRow(array $row): array
    {
        $data = $this->normalizeRow($row);

        if (isset($data['nip']) && (is_int($data['nip']) || is_float($data['nip']))) {
            $data['nip'] = sprintf('%.0f', $data['nip']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveRow(array $data): void
    {
        $nip = (string) $data['nip'];
        $nama = (string) $data['nama'];

        DB::transaction(function () use ($data, $nip, $nama) {
            $user = User::create([
                'name' => $nama,
                'username' => $nip,
                'email' => null,
                'password' => 'password123',
                'role_id' => 2,
            ]);

            $jurusanId = null;
            if (isset($data['jurusan']) && $data['jurusan'] !== null && $data['jurusan'] !== '') {
                $jurusan = \App\Models\Jurusan::query()
                    ->whereRaw('LOWER(kode) = ?', [strtolower(trim($data['jurusan']))])
                    ->orWhereRaw('LOWER(nama) = ?', [strtolower(trim($data['jurusan']))])
                    ->first();
                $jurusanId = $jurusan?->id;
            }

            Guru::create([
                'user_id' => $user->id,
                'nip' => $nip,
                'telp' => $data['telp'] ?? null,
                'jurusan_id' => $jurusanId,
                'kuota_bimbingan' => $data['kuota_bimbingan'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nip.required' => 'NIP wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
        ];
    }
}
