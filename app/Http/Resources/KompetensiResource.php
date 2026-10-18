<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KompetensiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jurusan_id' => $this->jurusan_id,
            'nama' => $this->nama,
            'kode' => $this->kode,
            'jurusan' => $this->whenLoaded('jurusan', fn () => [
                'id' => $this->jurusan->id,
                'nama' => $this->jurusan->nama,
                'kode' => $this->jurusan->kode,
            ]),
        ];
    }
}
