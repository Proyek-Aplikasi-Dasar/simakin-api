<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->whereIn('nama', ['admin', 'guru']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.exists' => 'Role harus admin atau guru. Akun siswa dibuat melalui menu data siswa.',
        ];
    }
}
