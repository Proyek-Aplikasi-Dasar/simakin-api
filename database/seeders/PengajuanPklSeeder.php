<?php

namespace Database\Seeders;

use App\Models\Gelombang;
use App\Models\PengajuanPkl;
use App\Models\Perusahaan;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class PengajuanPklSeeder extends Seeder
{
    public function run(): void
    {
        $gelombang = Gelombang::first();
        if (! $gelombang) {
            return;
        }

        $siswaList = Siswa::with('jurusan')->take(3)->get();
        $perusahaanList = Perusahaan::with('jurusan')->take(3)->get();

        if ($siswaList->isEmpty() || $perusahaanList->isEmpty()) {
            return;
        }

        // Pending
        $siswa = $siswaList->get(0);
        $perusahaan = $perusahaanList->get(0);
        if ($siswa && $perusahaan && $perusahaan->jurusan->contains('id', $siswa->jurusan_id)) {
            PengajuanPkl::firstOrCreate(
                ['siswa_id' => $siswa->id, 'gelombang_id' => $gelombang->id, 'status' => 'pending'],
                ['perusahaan_id' => $perusahaan->id, 'status' => 'pending']
            );
        }

        // Approved
        $siswa2 = $siswaList->get(1);
        $perusahaan2 = $perusahaanList->get(1);
        if ($siswa2 && $perusahaan2 && $perusahaan2->jurusan->contains('id', $siswa2->jurusan_id)) {
            PengajuanPkl::firstOrCreate(
                ['siswa_id' => $siswa2->id, 'gelombang_id' => $gelombang->id, 'status' => 'approved'],
                ['perusahaan_id' => $perusahaan2->id, 'status' => 'approved', 'verified_at' => now()]
            );
        }

        // Rejected
        $siswa3 = $siswaList->get(2);
        $perusahaan3 = $perusahaanList->get(2);
        if ($siswa3 && $perusahaan3 && $perusahaan3->jurusan->contains('id', $siswa3->jurusan_id)) {
            PengajuanPkl::firstOrCreate(
                ['siswa_id' => $siswa3->id, 'gelombang_id' => $gelombang->id, 'status' => 'rejected'],
                ['perusahaan_id' => $perusahaan3->id, 'status' => 'rejected', 'alasan_penolakan' => 'Kuota sudah penuh', 'verified_at' => now()]
            );
        }
    }
}
