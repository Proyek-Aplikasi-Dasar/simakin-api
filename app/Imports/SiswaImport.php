<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsRows;
use App\Models\Gelombang;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SiswaImport implements Import, WithHeadingRow
{
    use ImportsRows;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique('siswa', 'nis'),
                // NIS dipakai sebagai username login siswa.
                Rule::unique('users', 'username'),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'kelas' => ['nullable', 'string', 'max:20'],
            'jurusan' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== null && $this->resolveJurusanId((string) $value) === null) {
                        $fail('Jurusan tidak ditemukan.');
                    }
                },
            ],
            'gelombang' => ['nullable', 'string', 'max:100', Rule::exists('gelombang', 'nama')],
            'telp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'nama_ortu' => ['nullable', 'string', 'max:255'],
            'telp_ortu' => ['nullable', 'string', 'max:20'],
            'alamat_ortu' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Normalisasi nilai sel sebelum validasi.
     *
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    protected function prepareRow(array $row): array
    {
        $data = $this->normalizeRow($row);

        // Cegah NIS numerik berubah menjadi notasi ilmiah.
        if (isset($data['nis']) && (is_int($data['nis']) || is_float($data['nis']))) {
            $data['nis'] = sprintf('%.0f', $data['nis']);
        }

        if (isset($data['jenis_kelamin']) && is_string($data['jenis_kelamin'])) {
            $data['jenis_kelamin'] = match (strtolower($data['jenis_kelamin'])) {
                'l', 'laki-laki', 'laki laki', 'pria', 'male' => 'L',
                'p', 'perempuan', 'wanita', 'female' => 'P',
                default => $data['jenis_kelamin'],
            };
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveRow(array $data): void
    {
        $nis = (string) $data['nis'];
        $nama = (string) $data['nama'];

        DB::transaction(function () use ($data, $nis, $nama) {
            $user = User::create([
                'name' => $nama,
                'username' => $nis,
                'email' => $data['email'] ?? null,
                'password' => 'password123',
                'role_id' => 3,
            ]);

            Siswa::create([
                'user_id' => $user->id,
                'jurusan_id' => isset($data['jurusan']) ? $this->resolveJurusanId((string) $data['jurusan']) : null,
                'gelombang_id' => isset($data['gelombang'])
                    ? Gelombang::where('nama', $data['gelombang'])->value('id')
                    : null,
                'nis' => $nis,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'kelas' => $data['kelas'] ?? null,
                'telp' => $data['telp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'nama_ortu' => $data['nama_ortu'] ?? null,
                'telp_ortu' => $data['telp_ortu'] ?? null,
                'alamat_ortu' => $data['alamat_ortu'] ?? null,
            ]);
        });
    }

    /**
     * Cari jurusan berdasarkan kode atau nama (case-insensitive).
     */
    private function resolveJurusanId(string $value): ?int
    {
        $normalized = strtolower(trim($value));

        $jurusan = Jurusan::query()
            ->whereRaw('LOWER(kode) = ?', [$normalized])
            ->orWhereRaw('LOWER(nama) = ?', [$normalized])
            ->first();

        return $jurusan?->id;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
            'gelombang.exists' => 'Gelombang tidak ditemukan.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
        ];
    }
}
