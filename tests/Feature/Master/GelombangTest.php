<?php

namespace Tests\Feature\Master;

use App\Models\Gelombang;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\GelombangSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GelombangTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'tahun_ajaran' => '2026/2027',
            'nama' => 'Gelombang 1',
            'daftar_mulai' => '2026-11-01',
            'daftar_selesai' => '2026-11-30',
            'praktik_mulai' => '2027-01-05',
            'praktik_selesai' => '2027-06-30',
        ], $overrides);
    }

    public function test_admin_dapat_membuat_gelombang_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/gelombang', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('message', 'Gelombang berhasil disimpan.')
            ->assertJsonPath('data.nama', 'Gelombang 1')
            ->assertJsonPath('data.daftar_mulai', '2026-11-01')
            ->assertJsonPath('data.praktik_selesai', '2027-06-30');

        $this->assertDatabaseHas('gelombang', ['nama' => 'Gelombang 1']);
    }

    public function test_admin_dapat_memperbarui_gelombang(): void
    {
        $gelombang = Gelombang::create($this->payload(['nama' => 'Nama Lama']));

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/gelombang/{$gelombang->id}", $this->payload([
                'nama' => 'Nama Baru',
                'daftar_selesai' => '2026-12-15',
            ]));

        $response->assertOk()
            ->assertJsonPath('message', 'Gelombang berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Nama Baru')
            ->assertJsonPath('data.daftar_selesai', '2026-12-15');

        $this->assertDatabaseHas('gelombang', ['id' => $gelombang->id, 'nama' => 'Nama Baru']);
    }

    public function test_admin_dapat_menghapus_gelombang(): void
    {
        $gelombang = Gelombang::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/gelombang/{$gelombang->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Gelombang berhasil dihapus.');

        $this->assertDatabaseMissing('gelombang', ['id' => $gelombang->id]);
    }

    public function test_list_gelombang_dapat_dibuka_oleh_semua_role_login(): void
    {
        Gelombang::create($this->payload());

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/gelombang')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.nama', 'Gelombang 1');
        }
    }

    public function test_detail_gelombang_dapat_dibuka_oleh_semua_role_login(): void
    {
        $gelombang = Gelombang::create($this->payload());

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson("/api/gelombang/{$gelombang->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $gelombang->id);
        }
    }

    public function test_tamu_tidak_dapat_mengakses_gelombang(): void
    {
        $this->getJson('/api/gelombang')->assertUnauthorized();
        $this->getJson('/api/gelombang/1')->assertUnauthorized();
    }

    public function test_guru_dan_siswa_tidak_dapat_menulis_gelombang(): void
    {
        $gelombang = Gelombang::create($this->payload());

        foreach ([$this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/gelombang', $this->payload(['nama' => 'Ditolak']))
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->putJson("/api/gelombang/{$gelombang->id}", $this->payload(['nama' => 'Ditolak']))
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/gelombang/{$gelombang->id}")
                ->assertForbidden();
        }
    }

    public function test_membuat_gelombang_tanpa_field_wajib_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/gelombang', ['nama' => 'Tanpa Tanggal']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tahun_ajaran', 'daftar_mulai', 'daftar_selesai', 'praktik_mulai', 'praktik_selesai']);
    }

    public function test_membuat_gelombang_dengan_daftar_selesai_sebelum_daftar_mulai_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/gelombang', $this->payload([
                'daftar_mulai' => '2026-11-30',
                'daftar_selesai' => '2026-11-01',
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('daftar_selesai');
    }

    public function test_membuat_gelombang_dengan_praktik_selesai_sebelum_praktik_mulai_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/gelombang', $this->payload([
                'praktik_mulai' => '2027-06-30',
                'praktik_selesai' => '2027-01-05',
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('praktik_selesai');
    }

    public function test_update_gelombang_dengan_tanggal_terbalik_ditolak(): void
    {
        $gelombang = Gelombang::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/gelombang/{$gelombang->id}", $this->payload([
                'daftar_mulai' => '2026-12-31',
                'daftar_selesai' => '2026-12-01',
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('daftar_selesai');
    }

    public function test_list_gelombang_dapat_difilter_dengan_search(): void
    {
        Gelombang::create($this->payload());
        Gelombang::create($this->payload([
            'tahun_ajaran' => '2027/2028',
            'nama' => 'Gelombang 3',
            'daftar_mulai' => '2027-11-01',
            'daftar_selesai' => '2027-11-30',
            'praktik_mulai' => '2028-01-05',
            'praktik_selesai' => '2028-06-30',
        ]));

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/gelombang?search=2027/2028')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Gelombang 3');
    }

    public function test_detail_gelombang_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/gelombang/99999')
            ->assertNotFound();
    }

    public function test_seeder_membuat_2_gelombang_dan_idempotent(): void
    {
        $this->seed(GelombangSeeder::class);

        $this->assertCount(2, Gelombang::all());
        $this->assertDatabaseHas('gelombang', [
            'tahun_ajaran' => '2026/2027',
            'nama' => 'Gelombang 1',
        ]);

        $this->seed(GelombangSeeder::class);

        $this->assertCount(2, Gelombang::all());
    }
}
