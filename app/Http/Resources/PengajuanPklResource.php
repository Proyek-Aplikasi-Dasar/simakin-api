<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanPklResource extends JsonResource
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
                    'jurusan' => $this->siswa->jurusan ? [
                        'id' => $this->siswa->jurusan->id,
                        'nama' => $this->siswa->jurusan->nama,
                        'kode' => $this->siswa->jurusan->kode,
                    ] : null,
                ];
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
            'alasan_penolakan' => $this->alasan_penolakan,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
