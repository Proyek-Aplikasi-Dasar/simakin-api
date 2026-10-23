<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Opsional untuk kompatibilitas body dokumentasi; nilainya harus sama dengan NIS.
            'username' => ['nullable', 'string', 'max:20', 'same:nis'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique('siswa', 'nis'),
                // NIS dipakai sebagai username login, jadi harus unik di users juga.
                Rule::unique('users', 'username'),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'jurusan_id' => ['nullable', 'integer', Rule::exists('jurusan', 'id')],
            'gelombang_id' => ['nullable', 'integer', Rule::exists('gelombang', 'id')],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'kelas' => ['nullable', 'string', 'max:20'],
            'telp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'nama_ortu' => ['nullable', 'string', 'max:255'],
            'telp_ortu' => ['nullable', 'string', 'max:20'],
            'alamat_ortu' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'username.same' => 'Username harus sama dengan NIS.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
        ];
    }
}
