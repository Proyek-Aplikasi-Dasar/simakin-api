<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSiswa;
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

            $today = now()->toDateString();
            $presensiCounts = AbsensiSiswa::whereDate('tanggal', $today)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $hadir = (int) ($presensiCounts['hadir'] ?? 0);
            $izin = (int) ($presensiCounts['izin'] ?? 0);
            $sakit = (int) ($presensiCounts['sakit'] ?? 0);
            $libur = (int) ($presensiCounts['libur'] ?? 0);

            // Siswa aktif yang belum absen hari ini.
            $activeSiswa = LokasiPkl::where('status', 'active')->distinct('siswa_id')->count('siswa_id');
            $sudahAbsen = $hadir + $izin + $sakit + $libur;
            $tidakAbsen = max(0, $activeSiswa - $sudahAbsen);

            return [
                'total_siswa' => $totalSiswa,
                'total_guru' => Guru::count(),
                'total_perusahaan' => Perusahaan::count(),
                'siswa_by_status' => $siswaByStatus,
                'pengajuan_pending' => PengajuanPkl::where('status', 'pending')->count(),
                'presensi_hari_ini' => [
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'tidak_absen' => $tidakAbsen,
                ],
            ];
        });

        return response()->json($data);
    }
}
