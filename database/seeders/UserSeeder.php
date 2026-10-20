<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID masing-masing role
        $adminRole = Role::where('nama', 'admin')->first();
        $guruRole = Role::where('nama', 'guru')->first();
        $siswaRole = Role::where('nama', 'siswa')->first();

        // 1. Akun Admin
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'password' => 'admin123',
                'role_id' => $adminRole?->id,
            ]
        );

        // 2. Akun Guru
        User::firstOrCreate(
            ['username' => 'guru'],
            [
                'name' => 'Guru Pengajar',
                'password' => 'guru123',
                'role_id' => $guruRole?->id,
            ]
        );

        // 3. Akun Siswa
        User::firstOrCreate(
            ['username' => 'siswa'],
            [
                'name' => 'Siswa Teladan',
                'password' => 'siswa123',
                'role_id' => $siswaRole?->id,
            ]
        );
    }
}
