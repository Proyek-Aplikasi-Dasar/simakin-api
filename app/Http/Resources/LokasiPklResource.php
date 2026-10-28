<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LokasiPklResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'siswa' => $this->whenLoaded('siswa', function () {
                return [
                    'id' => $this->siswa->id,
                    'nis' => $this->siswa->nis,
                    'nama' => $this->siswa->user?->name,
                ];
            }),
            'guru' => $this->whenLoaded('guru', function () {
                return $this->guru ? [
                    'id' => $this->guru->id,
                    'nip' => $this->guru->nip,
                    'nama' => $this->guru->user?->name,
                ] : null;
            }),
            'perusahaan' => $this->whenLoaded('perusahaan', function () {
                return [
                    'id' => $this->perusahaan->id,
                    'nama' => $this->perusahaan->nama,
                ];
            }),
            'gelombang' => $this->whenLoaded('gelombang', function () {
                return [
                    'id' => $this->gelombang->id,
                    'nama' => $this->gelombang->nama,
                    'tahun_ajaran' => $this->gelombang->tahun_ajaran,
                ];
            }),
            'status' => $this->status,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
