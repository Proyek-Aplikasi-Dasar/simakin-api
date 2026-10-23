<?php

namespace Database\Seeders;

use App\Models\Gelombang;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 20 siswa tersebar di 10 jurusan dan 2 gelombang. Dua siswa pertama
     * sengaja belum mengonfirmasi profil agar alur onboarding bisa diuji.
     */
    public function run(): void
    {
        $siswa = [
            ['name' => 'Ahmad Fauzi', 'jenis_kelamin' => 'L'],
            ['name' => 'Siti Nurhaliza', 'jenis_kelamin' => 'P'],
            ['name' => 'Bagus Pratama', 'jenis_kelamin' => 'L'],
            ['name' => 'Dewi Lestari', 'jenis_kelamin' => 'P'],
            ['name' => 'Eko Saputra', 'jenis_kelamin' => 'L'],
            ['name' => 'Fitri Handayani', 'jenis_kelamin' => 'P'],
            ['name' => 'Galih Ramadhan', 'jenis_kelamin' => 'L'],
            ['name' => 'Hana Safitri', 'jenis_kelamin' => 'P'],
            ['name' => 'Ilham Maulana', 'jenis_kelamin' => 'L'],
            ['name' => 'Jihan Aulia', 'jenis_kelamin' => 'P'],
            ['name' => 'Kurniawan Adi', 'jenis_kelamin' => 'L'],
            ['name' => 'Laura Melati', 'jenis_kelamin' => 'P'],
            ['name' => 'Miftahul Huda', 'jenis_kelamin' => 'L'],
            ['name' => 'Nabila Putri', 'jenis_kelamin' => 'P'],
            ['name' => 'Oscar Yoga', 'jenis_kelamin' => 'L'],
            ['name' => 'Putri Ayu', 'jenis_kelamin' => 'P'],
            ['name' => 'Rizky Firmansyah', 'jenis_kelamin' => 'L'],
            ['name' => 'Salsabila Rahma', 'jenis_kelamin' => 'P'],
            ['name' => 'Taufik Hidayat', 'jenis_kelamin' => 'L'],
            ['name' => 'Ulfa Nuraini', 'jenis_kelamin' => 'P'],
        ];

        $jurusan = Jurusan::orderBy('id')->get();
        $gelombang = Gelombang::orderBy('id')->get();

        foreach ($siswa as $index => $data) {
            // NIS 0012345 - 0012364 (selaras dengan contoh di dokumentasi).
            $nis = sprintf('%07d', 12345 + $index);

            $jurusanItem = $jurusan->isEmpty() ? null : $jurusan[$index % $jurusan->count()];
            $gelombangItem = $gelombang->isEmpty() ? null : $gelombang[intdiv($index, 10) % $gelombang->count()];

            // Dua siswa pertama belum konfirmasi: satu kosong, satu terisi sebagian.
            $terkonfirmasi = $index >= 2;

            $user = User::firstOrCreate(
                ['username' => $nis],
                [
                    'name' => $data['name'],
                    'email' => $terkonfirmasi ? "siswa{$nis}@smkn1magelang.sch.id" : null,
                    'password' => Hash::make('password123'),
                    'role_id' => 3,
                ]
            );

            $profil = [
                'nis' => $nis,
                'profil_confirmed_at' => $terkonfirmasi ? now() : null,
            ];

            if ($index === 1) {
                // Profil terisi sebagian: cukup untuk menguji update parsial,
                // tetapi belum lengkap untuk dikonfirmasi.
                $profil += [
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'kelas' => $jurusanItem ? "XII {$jurusanItem->kode} 1" : null,
                    'telp' => '0812'.sprintf('%07d', $index + 1),
                    'jurusan_id' => $jurusanItem?->id,
                    'gelombang_id' => $gelombangItem?->id,
                ];
            } elseif ($terkonfirmasi) {
                $profil += [
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'kelas' => $jurusanItem ? "XII {$jurusanItem->kode} 1" : null,
                    'telp' => '0812'.sprintf('%07d', $index + 1),
                    'alamat' => 'Jl. Pemuda No. '.($index + 1).', Magelang',
                    'jurusan_id' => $jurusanItem?->id,
                    'gelombang_id' => $gelombangItem?->id,
                    'nama_ortu' => 'Bapak '.$data['name'],
                    'telp_ortu' => '0813'.sprintf('%07d', $index + 1),
                    'alamat_ortu' => 'Jl. Pemuda No. '.($index + 1).', Magelang',
                ];
            }

            Siswa::firstOrCreate(['user_id' => $user->id], $profil);
        }

        // Lampirkan profil siswa ke user demo 'siswa' (belum konfirmasi)
        // agar alur onboarding mobile bisa dicoba langsung.
        $demoSiswa = User::where('username', 'siswa')->first();

        if ($demoSiswa && ! $demoSiswa->siswa) {
            Siswa::firstOrCreate(
                ['user_id' => $demoSiswa->id],
                ['nis' => $demoSiswa->username],
            );
        }
    }
}
