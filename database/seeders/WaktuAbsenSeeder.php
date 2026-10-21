<?php

namespace Database\Seeders;

use App\Models\WaktuAbsen;
use Illuminate\Database\Seeder;

class WaktuAbsenSeeder extends Seeder
{
    /**
     * Jam kerja normal PKL.
     */
    public function run(): void
    {
        WaktuAbsen::firstOrCreate(
            ['mulai' => '06:00:00', 'selesai' => '22:00:00'],
            ['keterangan' => 'Jam kerja normal'],
        );
    }
}
