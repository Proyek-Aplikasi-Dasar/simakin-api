<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            GuruSeeder::class,
            JurusanSeeder::class,
            GelombangSeeder::class,
            SikapSeeder::class,
            WaktuAbsenSeeder::class,
            PerusahaanSeeder::class,
            KompetensiSeeder::class,
            SiswaSeeder::class,
            PengajuanPklSeeder::class,
            LokasiPklSeeder::class,
            AbsensiSiswaSeeder::class,
        ]);
    }
}
