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
            'jurusan' => $this->whenLoaded('jurusan', fn () => $this->jurusan->map(fn ($jurusan) => [
                'id' => $jurusan->id,
                'nama' => $jurusan->nama,
                'kode' => $jurusan->kode,
            ])),
        ];
    }
}
