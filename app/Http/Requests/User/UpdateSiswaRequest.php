<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiswaRequest extends FormRequest
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
        $siswa = $this->route('siswa');
        $siswaId = $siswa?->id;
        $userId = $siswa?->user_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            // Opsional; jika dikirim harus sama dengan NIS.
            'username' => ['nullable', 'string', 'max:20', 'same:nis'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'nis' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('siswa', 'nis')->ignore($siswaId),
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'jurusan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('jurusan', 'id')],
            'gelombang_id' => ['sometimes', 'nullable', 'integer', Rule::exists('gelombang', 'id')],
            'jenis_kelamin' => ['sometimes', 'nullable', Rule::in(['L', 'P'])],
            'kelas' => ['sometimes', 'nullable', 'string', 'max:20'],
            'telp' => ['sometimes', 'nullable', 'string', 'max:20'],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'nama_ortu' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telp_ortu' => ['sometimes', 'nullable', 'string', 'max:20'],
            'alamat_ortu' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
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
