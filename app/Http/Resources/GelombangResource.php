<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GelombangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tahun_ajaran' => $this->tahun_ajaran,
            'nama' => $this->nama,
            'daftar_mulai' => $this->daftar_mulai->toDateString(),
            'daftar_selesai' => $this->daftar_selesai->toDateString(),
            'praktik_mulai' => $this->praktik_mulai->toDateString(),
            'praktik_selesai' => $this->praktik_selesai->toDateString(),
        ];
    }
}
