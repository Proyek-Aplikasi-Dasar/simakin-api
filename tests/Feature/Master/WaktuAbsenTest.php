<?php

namespace Tests\Feature\Master;

use App\Models\Role;
use App\Models\User;
use App\Models\WaktuAbsen;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WaktuAbsenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaktuAbsenTest extends TestCase
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
            'mulai' => '08:00',
            'selesai' => '16:00',
            'keterangan' => 'Jam kerja normal',
        ], $overrides);
    }

    public function test_admin_dapat_membuat_waktu_absen_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/waktu-absen', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('message', 'Waktu absen berhasil disimpan.')
            ->assertJsonPath('data.mulai', '08:00')
            ->assertJsonPath('data.selesai', '16:00');

        $this->assertDatabaseHas('waktu_absen', ['keterangan' => 'Jam kerja normal']);
    }

    public function test_admin_dapat_memperbarui_waktu_absen(): void
    {
        $waktuAbsen = WaktuAbsen::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/waktu-absen/{$waktuAbsen->id}", $this->payload([
                'mulai' => '07:30',
                'selesai' => '15:30',
            ]));

        $response->assertOk()
            ->assertJsonPath('message', 'Waktu absen berhasil diperbarui.')
            ->assertJsonPath('data.mulai', '07:30')
            ->assertJsonPath('data.selesai', '15:30');

        $this->assertDatabaseHas('waktu_absen', ['id' => $waktuAbsen->id, 'mulai' => '07:30']);
    }

    public function test_admin_dapat_menghapus_waktu_absen(): void
    {
        $waktuAbsen = WaktuAbsen::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/waktu-absen/{$waktuAbsen->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Waktu absen berhasil dihapus.');

        $this->assertDatabaseMissing('waktu_absen', ['id' => $waktuAbsen->id]);
    }

    public function test_list_waktu_absen_dapat_dibuka_oleh_semua_role_login(): void
    {
        WaktuAbsen::create($this->payload());

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/waktu-absen')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.mulai', '08:00');
        }
    }

    public function test_tamu_tidak_dapat_mengakses_waktu_absen(): void
    {
        $this->getJson('/api/waktu-absen')->assertUnauthorized();
    }

    public function test_guru_dan_siswa_tidak_dapat_menulis_waktu_absen(): void
    {
        $waktuAbsen = WaktuAbsen::create($this->payload());

        foreach ([$this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/waktu-absen', $this->payload(['mulai' => '09:00', 'selesai' => '17:00']))
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->putJson("/api/waktu-absen/{$waktuAbsen->id}", $this->payload())
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/waktu-absen/{$waktuAbsen->id}")
                ->assertForbidden();
        }
    }

    public function test_membuat_waktu_absen_tanpa_field_wajib_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/waktu-absen', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mulai', 'selesai']);
    }

    public function test_membuat_waktu_absen_dengan_format_jam_invalid_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/waktu-absen', $this->payload(['mulai' => '08.00']));

        $response->assertStatus(422)->assertJsonValidationErrors('mulai');
    }

    public function test_membuat_waktu_absen_dengan_selesai_sama_dengan_mulai_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/waktu-absen', $this->payload(['selesai' => '08:00']));

        $response->assertStatus(422)->assertJsonValidationErrors('selesai');
    }

    public function test_membuat_waktu_absen_dengan_selesai_sebelum_mulai_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/waktu-absen', $this->payload([
                'mulai' => '16:00',
                'selesai' => '08:00',
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('selesai');
    }

    public function test_update_waktu_absen_dengan_urutan_jam_terbalik_ditolak(): void
    {
        $waktuAbsen = WaktuAbsen::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/waktu-absen/{$waktuAbsen->id}", $this->payload([
                'mulai' => '16:00',
                'selesai' => '08:00',
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('selesai');
    }

    public function test_tidak_ada_route_detail_waktu_absen(): void
    {
        $waktuAbsen = WaktuAbsen::create($this->payload());

        // URI dipakai update/destroy, tetapi GET sendiri tidak ada → 405 Method Not Allowed
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/waktu-absen/{$waktuAbsen->id}")
            ->assertStatus(405);
    }

    public function test_seeder_membuat_jam_kerja_normal_dan_idempotent(): void
    {
        $this->seed(WaktuAbsenSeeder::class);

        $this->assertCount(1, WaktuAbsen::all());
        $this->assertDatabaseHas('waktu_absen', [
            'mulai' => '08:00:00',
            'selesai' => '16:00:00',
            'keterangan' => 'Jam kerja normal',
        ]);

        $this->seed(WaktuAbsenSeeder::class);

        $this->assertCount(1, WaktuAbsen::all());
    }
}
