<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuruSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gurus = [
            ['name' => 'Guru Pembimbing 1', 'nip' => '198701012010011001', 'mata_pelajaran' => 'Produktif'],
            ['name' => 'Guru Pembimbing 2', 'nip' => '198702012010012002', 'mata_pelajaran' => 'Produktif'],
            ['name' => 'Guru Pembimbing 3', 'nip' => '198703012010013003', 'mata_pelajaran' => 'Bahasa Indonesia'],
            ['name' => 'Guru Pembimbing 4', 'nip' => '198704012010014004', 'mata_pelajaran' => 'Matematika'],
            ['name' => 'Guru Pembimbing 5', 'nip' => '198705012010015005', 'mata_pelajaran' => 'Kewirausahaan'],
        ];

        foreach ($gurus as $data) {
            $user = User::firstOrCreate(
                ['username' => $data['nip']],
                [
                    'name' => $data['name'],
                    'email' => null,
                    'password' => Hash::make('password123'),
                    'role_id' => 2,
                ]
            );

            if (! $user->guru) {
                Guru::create([
                    'user_id' => $user->id,
                    'nip' => $data['nip'],
                    'mata_pelajaran' => $data['mata_pelajaran'],
                ]);
            }
        }

        // Lampirkan profil guru ke user demo 'guru' jika ada
        $demoGuru = User::where('username', 'guru')->first();
        if ($demoGuru && ! $demoGuru->guru) {
            Guru::firstOrCreate(
                ['user_id' => $demoGuru->id],
                [
                    'nip' => null,
                    'mata_pelajaran' => null,
                ]
            );
        }
    }
}
