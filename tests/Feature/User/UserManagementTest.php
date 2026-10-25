<?php

namespace Tests\Feature\User;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['nama' => 'admin']);
        Role::firstOrCreate(['id' => 2], ['nama' => 'guru']);
        Role::firstOrCreate(['id' => 3], ['nama' => 'siswa']);
    }

    protected function admin(string $username = 'admin_test'): User
    {
        return User::factory()->create([
            'role_id' => 1,
            'username' => $username,
            'name' => 'Administrator',
        ]);
    }

    protected function guruUser(): User
    {
        return User::factory()->create([
            'role_id' => 2,
            'username' => '198701012010011001',
            'name' => 'Guru Test',
        ]);
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'username' => 'siswa_test']);

        $this->getJson('/api/users')->assertStatus(401);
        $this->getJson("/api/users/{$user->id}")->assertStatus(401);
        $this->putJson("/api/users/{$user->id}", ['name' => 'X'])->assertStatus(401);
        $this->putJson("/api/users/{$user->id}/password", ['password' => 'password123', 'password_confirmation' => 'password123'])->assertStatus(401);
        $this->deleteJson("/api/users/{$user->id}")->assertStatus(401);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $admin = $this->admin();
        $guru = $this->guruUser();
        $siswa = User::factory()->create(['role_id' => 3, 'username' => 'siswa_test']);

        foreach ([$guru, $siswa] as $actor) {
            $this->actingAs($actor, 'sanctum')->getJson('/api/users')->assertStatus(403);
            $this->actingAs($actor, 'sanctum')->getJson("/api/users/{$admin->id}")->assertStatus(403);
            $this->actingAs($actor, 'sanctum')->putJson("/api/users/{$siswa->id}", ['name' => 'X'])->assertStatus(403);
            $this->actingAs($actor, 'sanctum')->putJson("/api/users/{$siswa->id}/password", ['password' => 'password123', 'password_confirmation' => 'password123'])->assertStatus(403);
            $this->actingAs($actor, 'sanctum')->deleteJson("/api/users/{$siswa->id}")->assertStatus(403);
        }
    }

    public function test_admin_can_list_users_with_filter(): void
    {
        $admin = $this->admin();
        $this->guruUser();
        User::factory()->create(['role_id' => 3, 'username' => '0012345', 'name' => 'Siswa List']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users?role_id=3')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.username', '0012345');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users?search=Siswa')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_show_user_with_relations(): void
    {
        $admin = $this->admin();
        $user = $this->guruUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role.nama', 'guru')
            ->assertJsonPath('data.username', '198701012010011001');
    }

    public function test_admin_can_update_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'role_id' => 2,
            'username' => '198701012010011005',
            'name' => 'Guru Lama',
            'email' => null,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$user->id}", [
                'name' => 'Guru Baru',
                'username' => '198701012010011006',
                'email' => 'guru_baru@example.com',
                'role_id' => 2,
            ])
            ->assertStatus(200)
            ->assertJsonPath('message', 'User berhasil diperbarui.')
            ->assertJsonPath('data.name', 'Guru Baru')
            ->assertJsonPath('data.username', '198701012010011006')
            ->assertJsonPath('data.email', 'guru_baru@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Guru Baru',
            'username' => '198701012010011006',
            'email' => 'guru_baru@example.com',
        ]);
    }

    public function test_update_user_rejects_duplicate_username(): void
    {
        $admin = $this->admin();
        $userA = User::factory()->create(['role_id' => 2, 'username' => '198701012010011001']);
        $userB = User::factory()->create(['role_id' => 2, 'username' => '198701012010011002']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$userB->id}", ['username' => $userA->username])
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    public function test_admin_can_reset_password_and_revoke_tokens(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create([
            'role_id' => 2,
            'username' => '198701012010011003',
            'password' => 'oldpassword',
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$user->id}/password", [
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Kata sandi berhasil direset.');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Token lama tidak berlaku (dipastikan lewat revoke)
        $this->assertNotEmpty($token);
    }

    public function test_reset_password_validation_fails(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role_id' => 2, 'username' => '198701012010011004']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/users/{$user->id}/password", ['password' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('code', 'CANNOT_DELETE_SELF');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_soft_delete_user_and_revoke_tokens(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role_id' => 3, 'username' => '0012999']);
        $user->createToken('mobile-app');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/users/{$user->id}")
            ->assertStatus(200)
            ->assertJsonPath('message', 'User berhasil dihapus.');

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/users/{$user->id}")
            ->assertStatus(404);
    }
}
