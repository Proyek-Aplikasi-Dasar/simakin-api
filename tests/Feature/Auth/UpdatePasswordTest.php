<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function user(string $password = 'lama12345'): User
    {
        return User::factory()->create([
            'password' => $password,
            'role_id' => Role::where('nama', 'guru')->value('id'),
        ]);
    }

    public function test_user_dapat_mengubah_kata_sandi(): void
    {
        $user = $this->user();
        $token = $user->createToken('android-app');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'lama12345',
                'password' => 'baru12345',
                'password_confirmation' => 'baru12345',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Kata sandi berhasil diubah.');

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_gagal_jika_kata_sandi_saat_ini_salah(): void
    {
        $user = $this->user();
        $token = $user->createToken('android-app');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'salahsekali',
                'password' => 'baru12345',
                'password_confirmation' => 'baru12345',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_gagal_jika_konfirmasi_tidak_cocok(): void
    {
        $user = $this->user();
        $token = $user->createToken('android-app');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'lama12345',
                'password' => 'baru12345',
                'password_confirmation' => 'berbeda123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_gagal_jika_kata_sandi_terlalu_pendek(): void
    {
        $user = $this->user();
        $token = $user->createToken('android-app');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'lama12345',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_tamu_tidak_dapat_mengubah_kata_sandi(): void
    {
        $this->putJson('/api/user/password', [
            'current_password' => 'lama12345',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ])->assertStatus(401);
    }

    public function test_token_lain_dicabut_setelah_ubah_kata_sandi(): void
    {
        $user = $this->user();
        $current = $user->createToken('perangkat-ini');
        $other = $user->createToken('perangkat-lain');

        $this->withHeader('Authorization', 'Bearer '.$current->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'lama12345',
                'password' => 'baru12345',
                'password_confirmation' => 'baru12345',
            ])
            ->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->accessToken->id]);
    }

    public function test_semua_token_dicabut_saat_autentikasi_bukan_token(): void
    {
        $user = $this->user();
        $user->createToken('perangkat-a');
        $user->createToken('perangkat-b');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/password', [
                'current_password' => 'lama12345',
                'password' => 'baru12345',
                'password_confirmation' => 'baru12345',
            ])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
