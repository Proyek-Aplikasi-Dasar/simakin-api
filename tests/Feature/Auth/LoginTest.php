<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function roleId(string $nama): int
    {
        return Role::where('nama', $nama)->value('id');
    }

    public function test_siswa_dapat_login_via_mobile_dan_mendapat_token(): void
    {
        User::factory()->create([
            'username' => 'siswa',
            'password' => 'siswa123',
            'role_id' => $this->roleId('siswa'),
        ]);

        $response = $this->withHeaders(['X-Client' => 'mobile'])
            ->postJson('/api/login', [
                'username' => 'siswa',
                'password' => 'siswa123',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'name', 'username', 'role_id', 'role'], 'token']);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        User::factory()->create([
            'username' => 'siswa',
            'password' => 'siswa123',
            'role_id' => $this->roleId('siswa'),
        ]);

        $response = $this->withHeaders(['X-Client' => 'mobile'])
            ->postJson('/api/login', [
                'username' => 'siswa',
                'password' => 'salah',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_login_gagal_dengan_username_tidak_terdaftar(): void
    {
        $response = $this->withHeaders(['X-Client' => 'mobile'])
            ->postJson('/api/login', [
                'username' => 'tidakada',
                'password' => 'apapun',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_login_dengan_field_email_tidak_diterima_lagi(): void
    {
        User::factory()->create([
            'username' => 'siswa',
            'email' => 'siswa@example.com',
            'password' => 'siswa123',
            'role_id' => $this->roleId('siswa'),
        ]);

        $response = $this->withHeaders(['X-Client' => 'mobile'])
            ->postJson('/api/login', [
                'email' => 'siswa@example.com',
                'password' => 'siswa123',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_login_gagal_dengan_field_kosong(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_siswa_tidak_dapat_login_via_web(): void
    {
        User::factory()->create([
            'username' => 'siswa',
            'password' => 'siswa123',
            'role_id' => $this->roleId('siswa'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'siswa',
            'password' => 'siswa123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_user_terautentikasi_dapat_mengakses_endpoint_user(): void
    {
        $user = User::factory()->create(['role_id' => $this->roleId('guru')]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertOk()->assertJsonPath('id', $user->id);
    }

    public function test_akses_tanpa_login_ditolak(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_logout_menghapus_token_mobile(): void
    {
        $user = User::factory()->create([
            'username' => 'siswa',
            'password' => 'siswa123',
            'role_id' => $this->roleId('siswa'),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeaders([
            'X-Client' => 'mobile',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
