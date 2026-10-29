<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AbsensiSiswaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'siswa_id' => $this->siswa_id,
            'siswa' => $this->whenLoaded('siswa', function () {
                return [
                    'id' => $this->siswa->id,
                    'nis' => $this->siswa->nis,
                    'nama' => $this->siswa->user?->name,
                ];
            }),
            'tanggal' => $this->tanggal?->toDateString(),
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'foto_masuk' => $this->foto_masuk ? Storage::url($this->foto_masuk) : null,
            'jam_masuk' => $this->jam_masuk,
            'keterangan' => $this->keterangan,
            'status_submit' => $this->status_submit,
            'dikoreksi_oleh' => $this->dikoreksi_oleh,
            'korektor' => $this->whenLoaded('korektor', function () {
                return $this->korektor ? [
                    'id' => $this->korektor->id,
                    'name' => $this->korektor->name,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
