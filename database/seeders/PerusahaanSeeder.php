<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;

class PerusahaanSeeder extends Seeder
{
    /**
     * 12 perusahaan sample industri Magelang / sekitar Bowokan (kuota 5–15, koordinat realistis).
     * Urutan berjalan setelah JurusanSeeder.
     */
    public function run(): void
    {
        $idPerJurusan = Jurusan::pluck('id', 'kode');

        $perusahaan = [
            [
                'nama' => 'PT Aneka Cipta Magelang',
                'alamat' => 'Jl. Diponegoro No. 45, Magelang',
                'latitude' => -7.47050000,
                'longitude' => 110.21770000,
                'pimpinan' => 'Ir. Bambang Sutrisno',
                'telp' => '0293412345',
                'email' => 'hrd@anekacipta.co.id',
                'deskripsi' => 'Perusahaan manufaktik komponen otomotif di pusat kota Magelang.',
                'kuota' => 8,
                'jurusan' => ['TKR', 'TP'],
            ],
            [
                'nama' => 'CV Karya Bangun Sejahtera',
                'alamat' => 'Jl. Ahmad Yani No. 12, Magelang',
                'latitude' => -7.47360000,
                'longitude' => 110.22030000,
                'pimpinan' => 'Agus Setiawan, S.T.',
                'telp' => '0293423456',
                'email' => 'karyabangun@gmail.com',
                'deskripsi' => 'Kontraktor bangunan menengah, mengerjakan proyek rumah dan sekolah.',
                'kuota' => 6,
                'jurusan' => ['DPIB', 'TKB'],
            ],
            [
                'nama' => 'PT Tirta Magelang Prima',
                'alamat' => 'Jl. Magelang–Yogyakarta KM 8, Muntilan',
                'latitude' => -7.54910000,
                'longitude' => 110.24650000,
                'pimpinan' => 'Drs. H. Slamet Riyadi',
                'telp' => '0293581234',
                'email' => 'info@tirtamagelang.co.id',
                'deskripsi' => 'Industri air minum dalam kemasan (AMDK) terbesar di wilayah Magelang.',
                'kuota' => 10,
                'jurusan' => ['TEL', 'TITL'],
            ],
            [
                'nama' => 'CV Aruna Teknik Elektronika',
                'alamat' => 'Jl. Pemuda No. 78, Magelang',
                'latitude' => -7.46890000,
                'longitude' => 110.22310000,
                'pimpinan' => 'Rudi Hartono',
                'telp' => '0293434567',
                'email' => 'aruna.teknik@yahoo.com',
                'deskripsi' => 'Bengkel servis dan perakitan peralatan elektronika industri.',
                'kuota' => 5,
                'jurusan' => ['TEL'],
            ],
            [
                'nama' => 'PT Sinar Baru Tekstil',
                'alamat' => 'Jl. Jend. Sudirman No. 156, Magelang',
                'latitude' => -7.48120000,
                'longitude' => 110.21450000,
                'pimpinan' => 'Ir. Hartati Wijaya',
                'telp' => '0293445678',
                'email' => 'hrd@sinarbaru-textile.co.id',
                'deskripsi' => 'Industri garmen dan konveksi dengan pasar ekspor.',
                'kuota' => 15,
                'jurusan' => ['TP'],
            ],
            [
                'nama' => 'CV Bumi Merapi Agro',
                'alamat' => 'Jl. Raya Sawangan No. 3, Magelang',
                'latitude' => -7.40210000,
                'longitude' => 110.26540000,
                'pimpinan' => 'Ir. Bambang Purwanto',
                'telp' => '0293512345',
                'email' => 'bumimerapi@gmail.com',
                'deskripsi' => 'Agroindustri hasil bumi & pengemasan produk pertanian Merapi.',
                'kuota' => 7,
                'jurusan' => ['TP'],
            ],
            [
                'nama' => 'PT Global Auto Nusantara',
                'alamat' => 'Jl. Magelang–Solo KM 5, Magelang',
                'latitude' => -7.50340000,
                'longitude' => 110.28910000,
                'pimpinan' => 'Andi Prasetyo',
                'telp' => '0293456789',
                'email' => 'service@globalauto.co.id',
                'deskripsi' => 'Dealer & bengkel resmi kendaraan penumpang.',
                'kuota' => 12,
                'jurusan' => ['TKR'],
            ],
            [
                'nama' => 'CV Cahaya Las Mandiri',
                'alamat' => 'Jl. Tegalrejo No. 22, Magelang',
                'latitude' => -7.45120000,
                'longitude' => 110.23870000,
                'pimpinan' => 'Slamet Riyanto',
                'telp' => '0293467890',
                'email' => 'cahayalas@gmail.com',
                'deskripsi' => 'Fabricasi baja ringan, kanopi, dan konstruksi las struktural.',
                'kuota' => 6,
                'jurusan' => ['WEL'],
            ],
            [
                'nama' => 'PT Graha Cipta Desain',
                'alamat' => 'Jl. Pemuda No. 45, Magelang',
                'latitude' => -7.47010000,
                'longitude' => 110.22190000,
                'pimpinan' => 'Ir. Siti Nurhaliza',
                'telp' => '0293478901',
                'email' => 'studio@grahacipta.co.id',
                'deskripsi' => 'Konsultan arsitektur & interior residensial komersial.',
                'kuota' => 5,
                'jurusan' => ['DPIB', 'DKV'],
            ],
            [
                'nama' => 'CV Media Kreatif Magelang',
                'alamat' => 'Jl. Panjang No. 9, Magelang',
                'latitude' => -7.46550000,
                'longitude' => 110.21980000,
                'pimpinan' => 'Bagus Setiawan',
                'telp' => '0293489012',
                'email' => 'hello@mediakreatif.id',
                'deskripsi' => 'Agensi kreatif: desain grafis, branding, dan produksi konten digital.',
                'kuota' => 8,
                'jurusan' => ['DKV'],
            ],
            [
                'nama' => 'PT Nusantara Cold Chain',
                'alamat' => 'Jl. Raya Borobudur KM 3, Magelang',
                'latitude' => -7.60640000,
                'longitude' => 110.19370000,
                'pimpinan' => 'Ir. Hendra Gunawan',
                'telp' => '0293593456',
                'email' => 'ops@nusantaracold.co.id',
                'deskripsi' => 'Logistik rantai dingin & layanan pendingin komersial.',
                'kuota' => 9,
                'jurusan' => ['TPTU'],
            ],
            [
                'nama' => 'CV Prima Komputer Solusi',
                'alamat' => 'Jl. Ahmad Yani No. 88, Magelang',
                'latitude' => -7.47480000,
                'longitude' => 110.21890000,
                'pimpinan' => 'Yoga Pratama, S.Kom.',
                'telp' => '0293490123',
                'email' => 'support@primakomputer.co.id',
                'deskripsi' => 'IT solution: perakitan PC, jaringan, dan layanan internet.',
                'kuota' => 7,
                'jurusan' => ['TJKT'],
            ],
        ];

        foreach ($perusahaan as $item) {
            $jurusanKode = $item['jurusan'];
            unset($item['jurusan']);

            $perusahaanModel = Perusahaan::firstOrCreate(['nama' => $item['nama']], $item);
            $perusahaanModel->jurusan()->sync(
                array_values($idPerJurusan->only($jurusanKode)->all()),
            );
        }
    }
}
