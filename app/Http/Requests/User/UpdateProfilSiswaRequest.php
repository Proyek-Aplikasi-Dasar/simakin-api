<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfilSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Hanya field profil yang boleh diubah siswa. `nis`/`username` sengaja
     * tidak ada di rules sehingga diabaikan jika dikirim.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'jenis_kelamin' => ['sometimes', 'nullable', Rule::in(['L', 'P'])],
            'telp' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'kelas' => ['sometimes', 'nullable', 'string', 'max:20'],
            'jurusan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('jurusan', 'id')],
            'gelombang_id' => ['sometimes', 'nullable', 'integer', Rule::exists('gelombang', 'id')],
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
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
        ];
    }
}
