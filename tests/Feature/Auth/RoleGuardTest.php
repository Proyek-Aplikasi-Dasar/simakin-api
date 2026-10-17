<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Route::middleware(['auth:sanctum', 'role:admin'])
            ->get('/api/_test/admin-only', fn () => response()->json(['ok' => true]));

        Route::middleware(['auth:sanctum', 'role:admin,guru'])
            ->get('/api/_test/admin-guru', fn () => response()->json(['ok' => true]));
    }

    private function userWithRole(string $nama): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nama', $nama)->value('id'),
        ]);
    }

    public function test_admin_dapat_mengakses_rute_khusus_admin(): void
    {
        $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson('/api/_test/admin-only')
            ->assertOk();
    }

    public function test_guru_ditolak_di_rute_khusus_admin(): void
    {
        $this->actingAs($this->userWithRole('guru'), 'sanctum')
            ->getJson('/api/_test/admin-only')
            ->assertStatus(403);
    }

    public function test_siswa_ditolak_di_rute_khusus_admin(): void
    {
        $this->actingAs($this->userWithRole('siswa'), 'sanctum')
            ->getJson('/api/_test/admin-only')
            ->assertStatus(403);
    }

    public function test_tamu_ditolak_di_rute_terproteksi(): void
    {
        $this->getJson('/api/_test/admin-only')->assertStatus(401);
    }

    public function test_guru_dapat_mengakses_rute_multi_role(): void
    {
        $this->actingAs($this->userWithRole('guru'), 'sanctum')
            ->getJson('/api/_test/admin-guru')
            ->assertOk();
    }

    public function test_siswa_ditolak_di_rute_multi_role_admin_guru(): void
    {
        $this->actingAs($this->userWithRole('siswa'), 'sanctum')
            ->getJson('/api/_test/admin-guru')
            ->assertStatus(403);
    }
}
