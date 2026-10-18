<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    /**
     * 10 program keahlian SMKN Negeri 1 Magelang (menu Akademik, smknegeri1magelang.sch.id).
     */
    public function run(): void
    {
        $jurusan = [
            ['kode' => 'DPIB', 'nama' => 'Desain Permodelan dan Informasi Bangunan'],
            ['kode' => 'TKB', 'nama' => 'Teknik Konstruksi Bangunan'],
            ['kode' => 'TEL', 'nama' => 'Teknik Elektronika'],
            ['kode' => 'TITL', 'nama' => 'Teknik Instalasi Tenaga Listrik'],
            ['kode' => 'TPTU', 'nama' => 'Teknik Pendingin dan Tata Udara'],
            ['kode' => 'TP', 'nama' => 'Teknik Pemesinan'],
            ['kode' => 'TKR', 'nama' => 'Teknik Otomotif'],
            ['kode' => 'WEL', 'nama' => 'Teknik Pengelasan'],
            ['kode' => 'TJKT', 'nama' => 'Teknik Jaringan Komputer dan Telekomunikasi'],
            ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual'],
        ];

        foreach ($jurusan as $item) {
            Jurusan::firstOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
