<?php

namespace Database\Seeders;

use App\Models\Sikap;
use Illuminate\Database\Seeder;

class SikapSeeder extends Seeder
{
    /**
     * 10 sikap penilaian PKL.
     */
    public function run(): void
    {
        $sikap = [
            ['nama' => 'Disiplin', 'deskripsi' => 'Tepat waktu hadir dan menyelesaikan tugas sesuai aturan tempat PKL.'],
            ['nama' => 'Tanggung Jawab', 'deskripsi' => 'Menyelesaikan tugas yang diberikan dengan penuh rasa tanggung jawab.'],
            ['nama' => 'Kerjasama', 'deskripsi' => 'Bekerja sama dan berkomunikasi baik dengan rekan kerja.'],
            ['nama' => 'Inisiatif', 'deskripsi' => 'Bertindak proaktif tanpa menunggu perintah.'],
            ['nama' => 'Kejujuran', 'deskripsi' => 'Jujur dalam perkataan dan perbuatan di lingkungan kerja.'],
            ['nama' => 'Ketelitian', 'deskripsi' => 'Mengerjakan tugas dengan cermat dan teliti.'],
            ['nama' => 'Kreativitas', 'deskripsi' => 'Menghasilkan gagasan atau karya baru yang bermanfaat.'],
            ['nama' => 'Kemandirian', 'deskripsi' => 'Mampu bekerja dan mengambil keputusan sendiri secara wajar.'],
            ['nama' => 'Percaya Diri', 'deskripsi' => 'Yakin pada kemampuan diri saat berkomunikasi dan bekerja.'],
            ['nama' => 'Kepemimpinan', 'deskripsi' => 'Mampu memimpin dan mengarahkan kelompok dengan baik.'],
        ];

        foreach ($sikap as $item) {
            Sikap::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
