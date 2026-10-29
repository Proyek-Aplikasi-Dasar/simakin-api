<?php

namespace Database\Seeders;

use App\Models\AbsensiSiswa;
use App\Models\LokasiPkl;
use Illuminate\Database\Seeder;

class AbsensiSiswaSeeder extends Seeder
{
    public function run(): void
    {
        $lokasiList = LokasiPkl::where('status', 'active')->get();

        foreach ($lokasiList as $lokasi) {
            $siswa = $lokasi->siswa;
            if (! $siswa) {
                continue;
            }

            for ($i = 0; $i < 3; $i++) {
                $tanggal = now()->subDays($i)->toDateString();

                AbsensiSiswa::firstOrCreate(
                    ['siswa_id' => $siswa->id, 'tanggal' => $tanggal],
                    [
                        'status' => 'hadir',
                        'jam_masuk' => '07:5'.$i.':00',
                        'latitude' => -7.40123456,
                        'longitude' => 110.40123456,
                        'status_submit' => 'success',
                    ]
                );
            }
        }
    }
}
