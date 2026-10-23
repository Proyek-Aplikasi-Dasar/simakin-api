<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiswaResource extends JsonResource
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
            'nis' => $this->nis,
            'jenis_kelamin' => $this->jenis_kelamin,
            'kelas' => $this->kelas,
            'telp' => $this->telp,
            'alamat' => $this->alamat,
            'nama_ortu' => $this->nama_ortu,
            'telp_ortu' => $this->telp_ortu,
            'alamat_ortu' => $this->alamat_ortu,
            'profil_confirmed_at' => $this->profil_confirmed_at,
            'profil_terkonfirmasi' => $this->profil_confirmed_at !== null,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                    'role_id' => $this->user->role_id,
                ];
            }),
            'jurusan' => $this->whenLoaded('jurusan', function () {
                return [
                    'id' => $this->jurusan->id,
                    'nama' => $this->jurusan->nama,
                    'kode' => $this->jurusan->kode,
                ];
            }),
            'gelombang' => $this->whenLoaded('gelombang', function () {
                return [
                    'id' => $this->gelombang->id,
                    'nama' => $this->gelombang->nama,
                    'tahun_ajaran' => $this->gelombang->tahun_ajaran,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
