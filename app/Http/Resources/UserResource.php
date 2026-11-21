<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'status' => $this->deleted_at === null ? 'Aktif' : 'Nonaktif',
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('role', fn() => [
                'id' => $this->role->id,
                'nama' => $this->role->nama,
            ]),
            'guru' => $this->whenLoaded('guru', fn() => [
                'id' => $this->guru->id,
                'nip' => $this->guru->nip,
            ]),
            'siswa' => $this->whenLoaded('siswa', fn() => [
                'id' => $this->siswa->id,
                'nis' => $this->siswa->nis,
                'profil_confirmed_at' => $this->siswa->profil_confirmed_at,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
