<?php

namespace Database\Seeders;

use App\Models\Gelombang;
use App\Models\Guru;
use App\Models\LokasiPkl;
use App\Models\Perusahaan;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class LokasiPklSeeder extends Seeder
{
    public function run(): void
    {
        $gelombang = Gelombang::first();
        if (! $gelombang) {
            return;
        }

        $siswaList = Siswa::take(2)->get();
        $perusahaanList = Perusahaan::take(2)->get();
        $guru = Guru::first();

        if ($siswaList->count() < 2 || $perusahaanList->count() < 2) {
            return;
        }

        // Lokasi PKL aktif untuk siswa pertama
        LokasiPkl::firstOrCreate(
            ['siswa_id' => $siswaList->get(0)->id, 'gelombang_id' => $gelombang->id, 'status' => 'active'],
            [
                'guru_id' => $guru?->id,
                'perusahaan_id' => $perusahaanList->get(0)->id,
                'status' => 'active',
            ]
        );

        // Lokasi PKL pending untuk siswa kedua
        LokasiPkl::firstOrCreate(
            ['siswa_id' => $siswaList->get(1)->id, 'gelombang_id' => $gelombang->id, 'status' => 'pending'],
            [
                'guru_id' => null,
                'perusahaan_id' => $perusahaanList->get(1)->id,
                'status' => 'pending',
            ]
        );
    }
}
