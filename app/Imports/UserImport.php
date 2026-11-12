<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements Import, WithHeadingRow
{
    use ImportsRows;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nip_nis' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username'),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'telp' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(['admin', 'guru', 'siswa'])],
        ];
    }

    /**
     * Normalisasi NIP/NIS agar sel numerik tidak menjadi notasi ilmiah.
     *
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    protected function prepareRow(array $row): array
    {
        $data = $this->normalizeRow($row);

        if (isset($data['nip_nis']) && (is_int($data['nip_nis']) || is_float($data['nip_nis']))) {
            $data['nip_nis'] = sprintf('%.0f', $data['nip_nis']);
        }

        if (isset($data['role']) && is_string($data['role'])) {
            $data['role'] = strtolower(trim($data['role']));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveRow(array $data): void
    {
        $role = Role::where('nama', strtolower(trim($data['role'])))->first();

        if (! $role) {
            throw new \RuntimeException('Role tidak ditemukan: ' . $data['role']);
        }

        $nipNis = (string) $data['nip_nis'];

        DB::transaction(function () use ($data, $role, $nipNis) {
            User::create([
                'name' => (string) $data['nama'],
                'username' => $nipNis,
                'email' => $data['email'] ?? null,
                'password' => $nipNis,
                'role_id' => $role->id,
            ]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nip_nis.required' => 'NIP/NIS wajib diisi.',
            'nip_nis.unique' => 'Username sudah terdaftar.',
            'role.required' => 'Peran Pengguna wajib diisi.',
            'role.in' => 'Peran harus admin, guru, atau siswa.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'telp.max' => 'Nomor telepon maksimal 20 karakter.',
        ];
    }
}
