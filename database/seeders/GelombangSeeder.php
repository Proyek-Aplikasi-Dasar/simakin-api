<?php

namespace Database\Seeders;

use App\Models\Gelombang;
use Illuminate\Database\Seeder;

class GelombangSeeder extends Seeder
{
    /**
     * 2 gelombang PKL tahun ajaran 2026/2027.
     */
    public function run(): void
    {
        $gelombang = [
            [
                'tahun_ajaran' => '2026/2027',
                'nama' => 'Gelombang 1',
                'daftar_mulai' => '2026-11-01',
                'daftar_selesai' => '2026-11-30',
                'praktik_mulai' => '2027-01-05',
                'praktik_selesai' => '2027-06-30',
            ],
            [
                'tahun_ajaran' => '2026/2027',
                'nama' => 'Gelombang 2',
                'daftar_mulai' => '2026-12-01',
                'daftar_selesai' => '2026-12-31',
                'praktik_mulai' => '2027-07-05',
                'praktik_selesai' => '2027-12-15',
            ],
        ];

        foreach ($gelombang as $item) {
            Gelombang::firstOrCreate(
                ['tahun_ajaran' => $item['tahun_ajaran'], 'nama' => $item['nama']],
                $item,
            );
        }
    }
}
