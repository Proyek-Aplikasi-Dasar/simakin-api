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
            ['name' => 'Guru Pembimbing 1', 'nip' => '198701012010011001'],
            ['name' => 'Guru Pembimbing 2', 'nip' => '198702012010012002'],
            ['name' => 'Guru Pembimbing 3', 'nip' => '198703012010013003'],
            ['name' => 'Guru Pembimbing 4', 'nip' => '198704012010014004'],
            ['name' => 'Guru Pembimbing 5', 'nip' => '198705012010015005'],
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
                ]
            );
        }
    }
}
