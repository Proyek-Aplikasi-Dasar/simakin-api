<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuruResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nip' => $this->nip,
            'telp' => $this->telp,
            'jurusan_id' => $this->jurusan_id,
            'kuota_bimbingan' => $this->kuota_bimbingan,
            'alamat' => $this->alamat,
            'is_active' => $this->is_active,
            'jurusan' => $this->whenLoaded('jurusan', function () {
                return [
                    'id' => $this->jurusan->id,
                    'nama' => $this->jurusan->nama,
                    'kode' => $this->jurusan->kode,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                    'role_id' => $this->user->role_id,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
