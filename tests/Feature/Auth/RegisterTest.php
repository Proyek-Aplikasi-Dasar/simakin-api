<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['role_id' => $this->roleId('admin')]);
    }

    public function test_admin_dapat_mendaftarkan_guru_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Guru Baru',
                'username' => 'guru.baru',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pengguna berhasil didaftarkan.')
            ->assertJsonPath('data.username', 'guru.baru');

        $this->assertDatabaseHas('users', [
            'username' => 'guru.baru',
            'role_id' => $this->roleId('guru'),
        ]);
    }

    public function test_admin_dapat_mendaftarkan_admin_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Admin Baru',
                'username' => 'admin.baru',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('admin'),
            ]);

        $response->assertCreated();
    }

    public function test_registrasi_ditolak_untuk_role_siswa(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Siswa Baru',
                'username' => 'siswa.baru',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('siswa'),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('role_id');
    }

    public function test_registrasi_ditolak_untuk_username_duplikat(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Duplikat',
                'username' => $admin->username,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_registrasi_ditolak_tanpa_username(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Tanpa Username',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_registrasi_ditolak_untuk_password_tidak_terkonfirmasi(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Guru Baru',
                'username' => 'guru.baru',
                'password' => 'password123',
                'password_confirmation' => 'berbeda123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_guru_tidak_diizinkan_mendaftarkan_user_baru(): void
    {
        $guru = User::factory()->create(['role_id' => $this->roleId('guru')]);

        $response = $this->actingAs($guru, 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Guru Lain',
                'username' => 'guru.lain',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertStatus(403);
    }

    public function test_siswa_tidak_diizinkan_mendaftarkan_user_baru(): void
    {
        $siswa = User::factory()->create(['role_id' => $this->roleId('siswa')]);

        $response = $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Guru Lain',
                'username' => 'guru.lain',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertStatus(403);
    }

    public function test_tamu_tidak_diizinkan_mendaftarkan_user_baru(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Guru Baru',
            'username' => 'guru.baru',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('guru'),
        ])->assertStatus(401);
    }
}
