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
                'email' => 'guru.baru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pengguna berhasil didaftarkan.')
            ->assertJsonPath('data.email', 'guru.baru@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'guru.baru@example.com',
            'role_id' => $this->roleId('guru'),
        ]);
    }

    public function test_admin_dapat_mendaftarkan_admin_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Admin Baru',
                'email' => 'admin.baru@example.com',
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
                'email' => 'siswa.baru@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('siswa'),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('role_id');
    }

    public function test_registrasi_ditolak_untuk_email_duplikat(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Duplikat',
                'email' => $admin->email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role_id' => $this->roleId('guru'),
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_registrasi_ditolak_untuk_password_tidak_terkonfirmasi(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/register', [
                'name' => 'Guru Baru',
                'email' => 'guru.baru@example.com',
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
                'email' => 'guru.lain@example.com',
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
                'email' => 'guru.lain@example.com',
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
            'email' => 'guru.baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $this->roleId('guru'),
        ])->assertStatus(401);
    }
}
