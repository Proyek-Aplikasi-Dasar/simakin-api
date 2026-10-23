<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuruRequest extends FormRequest
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
        $guru = $this->route('guru');
        $guruId = $guru?->id;
        $userId = $guru?->user_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            // Opsional; jika dikirim harus sama dengan NIP.
            'username' => ['nullable', 'string', 'max:50', 'same:nip'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'nip' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('guru', 'nip')->ignore($guruId),
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'mata_pelajaran' => ['nullable', 'string', 'max:100'],
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
            'nip.required' => 'NIP wajib diisi.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'username.same' => 'Username harus sama dengan NIP.',
        ];
    }
}
