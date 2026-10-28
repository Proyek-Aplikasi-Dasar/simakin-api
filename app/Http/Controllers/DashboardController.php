<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\LokasiPkl;
use App\Models\PengajuanPkl;
use App\Models\Perusahaan;
use App\Models\Siswa;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function admin()
    {
        $data = Cache::remember('dashboard.admin', 600, function () {
            $siswaByStatus = [
                'pending' => LokasiPkl::where('status', 'pending')->count(),
                'active' => LokasiPkl::where('status', 'active')->count(),
                'completed' => LokasiPkl::where('status', 'completed')->count(),
            ];

            // Siswa tanpa lokasi PKL dihitung sebagai 'pending'.
            $placedSiswa = LokasiPkl::distinct('siswa_id')->count('siswa_id');
            $totalSiswa = Siswa::count();
            $siswaByStatus['pending'] += max(0, $totalSiswa - $placedSiswa);

            return [
                'total_siswa' => $totalSiswa,
                'total_guru' => Guru::count(),
                'total_perusahaan' => Perusahaan::count(),
                'siswa_by_status' => $siswaByStatus,
                'pengajuan_pending' => PengajuanPkl::where('status', 'pending')->count(),
                'presensi_hari_ini' => [
                    'hadir' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'tidak_absen' => 0,
                ],
            ];
        });

        return response()->json($data);
    }
}
