<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerusahaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'pimpinan' => $this->pimpinan,
            'telp' => $this->telp,
            'email' => $this->email,
            'deskripsi' => $this->deskripsi,
            'kuota' => $this->kuota,
            'is_active' => $this->is_active,
            'radius_presensi' => $this->radius_presensi,
            'pembimbing_industri' => $this->pembimbing_industri,
            'bidang_usaha' => $this->bidang_usaha,
            'jurusan' => $this->whenLoaded('jurusan', fn () => $this->jurusan->map(fn ($jurusan) => [
                'id' => $jurusan->id,
                'nama' => $jurusan->nama,
                'kode' => $jurusan->kode,
            ])),
        ];
    }
}
