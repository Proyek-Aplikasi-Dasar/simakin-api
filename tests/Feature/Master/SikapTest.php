<?php

namespace Tests\Feature\Master;

use App\Models\Role;
use App\Models\Sikap;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SikapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SikapTest extends TestCase
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

    private function guru(): User
    {
        return User::factory()->create(['role_id' => $this->roleId('guru')]);
    }

    private function siswa(): User
    {
        return User::factory()->create(['role_id' => $this->roleId('siswa')]);
    }

    public function test_admin_dapat_membuat_sikap_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/sikap', [
                'nama' => 'Disiplin',
                'deskripsi' => 'Tepat waktu hadir dan menyelesaikan tugas.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Sikap berhasil disimpan.')
            ->assertJsonPath('data.nama', 'Disiplin');

        $this->assertDatabaseHas('sikap', ['nama' => 'Disiplin']);
    }

    public function test_admin_dapat_membuat_sikap_tanpa_deskripsi(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/sikap', ['nama' => 'Tanpa Deskripsi']);

        $response->assertCreated()
            ->assertJsonPath('data.deskripsi', null);
    }

    public function test_admin_dapat_memperbarui_sikap(): void
    {
        $sikap = Sikap::create(['nama' => 'Nama Lama']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/sikap/{$sikap->id}", [
                'nama' => 'Nama Baru',
                'deskripsi' => 'Deskripsi baru.',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Sikap berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Nama Baru');

        $this->assertDatabaseHas('sikap', ['id' => $sikap->id, 'nama' => 'Nama Baru']);
    }

    public function test_admin_dapat_menghapus_sikap(): void
    {
        $sikap = Sikap::create(['nama' => 'Akan Dihapus']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/sikap/{$sikap->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Sikap berhasil dihapus.');

        $this->assertDatabaseMissing('sikap', ['id' => $sikap->id]);
    }

    public function test_list_sikap_dapat_dibuka_oleh_semua_role_login(): void
    {
        Sikap::create(['nama' => 'Disiplin']);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/sikap')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.nama', 'Disiplin');
        }
    }

    public function test_detail_sikap_dapat_dibuka_oleh_semua_role_login(): void
    {
        $sikap = Sikap::create(['nama' => 'Kerjasama']);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson("/api/sikap/{$sikap->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $sikap->id);
        }
    }

    public function test_tamu_tidak_dapat_mengakses_sikap(): void
    {
        $this->getJson('/api/sikap')->assertUnauthorized();
        $this->getJson('/api/sikap/1')->assertUnauthorized();
    }

    public function test_guru_dan_siswa_tidak_dapat_menulis_sikap(): void
    {
        $sikap = Sikap::create(['nama' => 'Sebelumnya']);

        foreach ([$this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/sikap', ['nama' => 'Ditolak'])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->putJson("/api/sikap/{$sikap->id}", ['nama' => 'Ditolak'])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/sikap/{$sikap->id}")
                ->assertForbidden();
        }
    }

    public function test_membuat_sikap_tanpa_nama_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/sikap', ['deskripsi' => 'Tanpa nama']);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_membuat_sikap_dengan_nama_terlalu_panjang_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/sikap', ['nama' => str_repeat('A', 101)]);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_list_sikap_dapat_difilter_dengan_search(): void
    {
        Sikap::create(['nama' => 'Disiplin']);
        Sikap::create(['nama' => 'Kreativitas']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/sikap?search=Kreat')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Kreativitas');
    }

    public function test_detail_sikap_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/sikap/99999')
            ->assertNotFound();
    }

    public function test_seeder_membuat_10_sikap_dan_idempotent(): void
    {
        $this->seed(SikapSeeder::class);

        $this->assertCount(10, Sikap::all());
        $this->assertDatabaseHas('sikap', ['nama' => 'Disiplin']);

        $this->seed(SikapSeeder::class);

        $this->assertCount(10, Sikap::all());
    }
}
