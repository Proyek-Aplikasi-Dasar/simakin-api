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
                $lokasiAktif = $this->siswa->lokasiPkl?->where('status', 'active')->first();

                return [
                    'id' => $this->siswa->id,
                    'nis' => $this->siswa->nis,
                    'nama' => $this->siswa->user?->name,
                    'kelas' => $this->siswa->kelas,
                    'jurusan' => $this->siswa->jurusan ? [
                        'id' => $this->siswa->jurusan->id,
                        'nama' => $this->siswa->jurusan->nama,
                        'kode' => $this->siswa->jurusan->kode,
                    ] : null,
                    'perusahaan' => $lokasiAktif?->perusahaan?->nama,
                ];
            }),
            'tanggal' => $this->tanggal?->toDateString(),
            'status' => $this->status,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'foto_masuk' => $this->foto_masuk ? Storage::url($this->foto_masuk) : null,
            'jam_masuk' => $this->jam_masuk,
            'jam_shift_mulai' => $this->jam_shift_mulai,
            'jam_shift_selesai' => $this->jam_shift_selesai,
            'keterangan' => $this->keterangan,
            'berkas_pendukung' => $this->berkas_pendukung ? Storage::url($this->berkas_pendukung) : null,
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
