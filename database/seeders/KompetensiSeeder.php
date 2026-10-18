<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kompetensi;
use Illuminate\Database\Seeder;

class KompetensiSeeder extends Seeder
{
    /**
     * Minimal 5 kompetensi per jurusan (50+ baris) — urutan berjalan setelah JurusanSeeder.
     */
    public function run(): void
    {
        $kompetensiPerJurusan = [
            'DPIB' => [
                'Gambar Kerja Bangunan',
                'Permodelan 3D Bangunan',
                'Penghitungan Volume & RAB',
                'Penggambaran Detail Struktur dengan CAD',
                'Pengawasan Pelaksanaan Gambar Kerja',
            ],
            'TKB' => [
                'Pekerjaan Pondasi',
                'Pekerjaan Struktur Beton',
                'Pekerjaan Dinding & Plesteran',
                'Pekerjaan Atap & Penyelesaian',
                'Pengawasan Mutu Pekerjaan Bangunan',
            ],
            'TEL' => [
                'Instalasi Rangkaian Elektronika',
                'Perakitan Perangkat Elektronika',
                'Perbaikan Peralatan Elektronika',
                'Pemeliharaan Peralatan Elektronika',
                'Pengujian Komponen & Rangkaian Elektronika',
            ],
            'TITL' => [
                'Instalasi Jaringan Listrik Gedung',
                'Pemasangan Peralatan Panel Distribusi',
                'Pemeliharaan Instalasi Tenaga Listrik',
                'Pengukuran & Proteksi Instalasi Listrik',
                'Instalasi & Perawatan Motor Listrik',
            ],
            'TPTU' => [
                'Instalasi Pendingin Ruangan',
                'Perbaikan Kulkas & Freezer',
                'Pemeliharaan AC Sentral',
                'Pengujian Sistem Refrigerasi',
                'Instalasi Sistem Tata Udara',
            ],
            'TP' => [
                'Pengoperasian Mesin Bubut',
                'Pengoperasian Mesin Frais',
                'Pembuatan Program Mesin CNC',
                'Permesinan Benda Kerja Presisi',
                'Pengukuran Metrologi Industri',
            ],
            'TKR' => [
                'Perawatan Berkala Mesin Kendaraan',
                'Perbaikan Sistem Transmisi',
                'Perbaikan Sistem Rem',
                'Perbaikan Kelistrikan Kendaraan',
                'Perawatan Sasis & Suspensi',
            ],
            'WEL' => [
                'Pengelasan SMAW',
                'Pengelasan GMAW/MIG',
                'Pengelasan GTAW/TIG',
                'Fabricasi & Bending Plat',
                'Inspeksi Mutu Pengelasan',
            ],
            'TJKT' => [
                'Konfigurasi Jaringan Kabel Terstruktur',
                'Perakitan & Perbaikan Komputer',
                'Administrasi Server',
                'Instalasi Jaringan',
                'Troubleshooting Jaringan',
            ],
            'DKV' => [
                'Desain Grafis Digital',
                'Ilustrasi & Komik',
                'Fotografi',
                'Layout & Publishing',
                'Animasi 2D',
            ],
        ];

        Jurusan::query()->orderBy('id')->get()->each(function (Jurusan $jurusan) use ($kompetensiPerJurusan) {
            foreach ($kompetensiPerJurusan[$jurusan->kode] ?? [] as $index => $nama) {
                Kompetensi::firstOrCreate(
                    ['jurusan_id' => $jurusan->id, 'kode' => sprintf('%s-%02d', $jurusan->kode, $index + 1)],
                    ['nama' => $nama],
                );
            }
        });
    }
}
