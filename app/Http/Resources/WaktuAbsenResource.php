<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaktuAbsenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Normalisasi ke H:i agar konsisten antar driver DB (MySQL TIME = H:i:s)
            'mulai' => substr((string) $this->mulai, 0, 5),
            'selesai' => substr((string) $this->selesai, 0, 5),
            'keterangan' => $this->keterangan,
        ];
    }
}
