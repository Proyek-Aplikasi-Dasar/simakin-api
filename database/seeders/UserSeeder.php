<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil ID masing-masing role
        $adminRole = Role::where('nama', 'admin')->first();
        $guruRole  = Role::where('nama', 'guru')->first();
        $siswaRole = Role::where('nama', 'siswa')->first();

        // 1. Akun Admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Administrator',
                'password'          => 'admin123',
                'role_id'           => $adminRole?->id,
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun Guru
        User::firstOrCreate(
            ['email' => 'guru@example.com'],
            [
                'name'              => 'Guru Pengajar',
                'password'          => 'guru123',
                'role_id'           => $guruRole?->id,
                'email_verified_at' => now(),
            ]
        );

        // 3. Akun Siswa
        User::firstOrCreate(
            ['email' => 'siswa@example.com'],
            [
                'name'              => 'Siswa Teladan',
                'password'          => 'siswa123',
                'role_id'           => $siswaRole?->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
