<?php

namespace Tests\Feature\Master;

use App\Models\Jurusan;
use App\Models\Perusahaan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\JurusanSeeder;
use Database\Seeders\PerusahaanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerusahaanTest extends TestCase
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

    private function jurusan(string $kode = 'TJKT'): Jurusan
    {
        return Jurusan::firstOrCreate(['kode' => $kode], ['nama' => "Jurusan {$kode}"]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'PT Uji Kolektif',
            'alamat' => 'Jl. Uji No. 1, Magelang',
            'latitude' => -7.49160000,
            'longitude' => 110.21800000,
            'pimpinan' => 'Budi Uji',
            'telp' => '0293123456',
            'email' => 'hrd@ujuji.example.com',
            'deskripsi' => 'Perusahaan uji feature test.',
            'kuota' => 10,
        ], $overrides);
    }

    public function test_admin_dapat_membuat_perusahaan_baru_dengan_jurusan(): void
    {
        $tjkt = $this->jurusan('TJKT');
        $dkv = $this->jurusan('DKV');

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/perusahaan', $this->payload([
                'jurusan_ids' => [$tjkt->id, $dkv->id],
            ]));

        $response->assertCreated()
            ->assertJsonPath('message', 'Perusahaan berhasil disimpan.')
            ->assertJsonPath('data.nama', 'PT Uji Kolektif')
            ->assertJsonCount(2, 'data.jurusan');

        $this->assertDatabaseHas('perusahaan', ['nama' => 'PT Uji Kolektif']);
        $this->assertDatabaseHas('perusahaan_jurusan', ['jurusan_id' => $tjkt->id]);
    }

    public function test_admin_dapat_membuat_perusahaan_tanpa_jurusan(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/perusahaan', $this->payload());

        $response->assertCreated()
            ->assertJsonCount(0, 'data.jurusan');
    }

    public function test_admin_dapat_memperbarui_perusahaan_dengan_sync_jurusan(): void
    {
        $tjkt = $this->jurusan('TJKT');
        $dkv = $this->jurusan('DKV');
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id]);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/perusahaan/{$perusahaan->id}", $this->payload([
                'nama' => 'PT Uji Updated',
                'jurusan_ids' => [$dkv->id],
            ]));

        $response->assertOk()
            ->assertJsonPath('message', 'Perusahaan berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'PT Uji Updated')
            ->assertJsonCount(1, 'data.jurusan')
            ->assertJsonPath('data.jurusan.0.kode', 'DKV');

        $this->assertDatabaseMissing('perusahaan_jurusan', ['perusahaan_id' => $perusahaan->id, 'jurusan_id' => $tjkt->id]);
        $this->assertDatabaseHas('perusahaan_jurusan', ['perusahaan_id' => $perusahaan->id, 'jurusan_id' => $dkv->id]);
    }

    public function test_admin_dapat_menghapus_perusahaan(): void
    {
        $perusahaan = Perusahaan::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/perusahaan/{$perusahaan->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Perusahaan berhasil dihapus.');

        $this->assertDatabaseMissing('perusahaan', ['id' => $perusahaan->id]);
    }

    public function test_list_perusahaan_dapat_dibuka_oleh_semua_role_login(): void
    {
        Perusahaan::create($this->payload());

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/perusahaan')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.nama', 'PT Uji Kolektif');
        }
    }

    public function test_detail_perusahaan_dapat_dibuka_oleh_semua_role_login(): void
    {
        $tjkt = $this->jurusan();
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id]);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson("/api/perusahaan/{$perusahaan->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $perusahaan->id)
                ->assertJsonPath('data.jurusan.0.kode', 'TJKT');
        }
    }

    public function test_tamu_tidak_dapat_mengakses_perusahaan(): void
    {
        $this->getJson('/api/perusahaan')->assertUnauthorized();
        $this->getJson('/api/perusahaan/1')->assertUnauthorized();
    }

    public function test_guru_dan_siswa_tidak_dapat_menulis_perusahaan(): void
    {
        $perusahaan = Perusahaan::create($this->payload());

        foreach ([$this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/perusahaan', $this->payload(['nama' => 'Ditolak']))
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->putJson("/api/perusahaan/{$perusahaan->id}", $this->payload(['nama' => 'Ditolak']))
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/perusahaan/{$perusahaan->id}")
                ->assertForbidden();
        }
    }

    public function test_admin_dapat_melampirkan_jurusan_ke_perusahaan(): void
    {
        $tjkt = $this->jurusan('TJKT');
        $dkv = $this->jurusan('DKV');
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id]);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/perusahaan/{$perusahaan->id}/jurusan", ['jurusan_id' => $dkv->id]);

        $response->assertOk()
            ->assertJsonPath('message', 'Jurusan berhasil ditambahkan ke perusahaan.')
            ->assertJsonCount(2, 'data.jurusan');

        $this->assertDatabaseHas('perusahaan_jurusan', [
            'perusahaan_id' => $perusahaan->id,
            'jurusan_id' => $dkv->id,
        ]);
    }

    public function test_attach_jurusan_yang_sudah_terpasang_tidak_menduplikasi(): void
    {
        $tjkt = $this->jurusan();
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/perusahaan/{$perusahaan->id}/jurusan", ['jurusan_id' => $tjkt->id])
            ->assertOk()
            ->assertJsonCount(1, 'data.jurusan');

        $this->assertSame(1, $perusahaan->jurusan()->count());
    }

    public function test_attach_jurusan_dengan_id_invalid_ditolak(): void
    {
        $perusahaan = Perusahaan::create($this->payload());

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/perusahaan/{$perusahaan->id}/jurusan", ['jurusan_id' => 99999]);

        $response->assertStatus(422)->assertJsonValidationErrors('jurusan_id');
    }

    public function test_admin_dapat_melepaskan_jurusan_dari_perusahaan(): void
    {
        $tjkt = $this->jurusan('TJKT');
        $dkv = $this->jurusan('DKV');
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id, $dkv->id]);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/perusahaan/{$perusahaan->id}/jurusan/{$tjkt->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Jurusan berhasil dilepas dari perusahaan.');

        $this->assertDatabaseMissing('perusahaan_jurusan', [
            'perusahaan_id' => $perusahaan->id,
            'jurusan_id' => $tjkt->id,
        ]);
        $this->assertDatabaseHas('perusahaan_jurusan', [
            'perusahaan_id' => $perusahaan->id,
            'jurusan_id' => $dkv->id,
        ]);
    }

    public function test_detach_jurusan_yang_tidak_terpasang_mengembalikan_404(): void
    {
        $tjkt = $this->jurusan('TJKT');
        $dkv = $this->jurusan('DKV');
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/perusahaan/{$perusahaan->id}/jurusan/{$dkv->id}")
            ->assertNotFound();
    }

    public function test_detach_jurusan_tidak_ada_mengembalikan_404(): void
    {
        $perusahaan = Perusahaan::create($this->payload());

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/perusahaan/{$perusahaan->id}/jurusan/99999")
            ->assertNotFound();
    }

    public function test_attach_dan_detach_ditolak_untuk_role_non_admin(): void
    {
        $tjkt = $this->jurusan();
        $perusahaan = Perusahaan::create($this->payload());
        $perusahaan->jurusan()->sync([$tjkt->id]);

        foreach ([$this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/perusahaan/{$perusahaan->id}/jurusan", ['jurusan_id' => $tjkt->id])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/perusahaan/{$perusahaan->id}/jurusan/{$tjkt->id}")
                ->assertForbidden();
        }
    }

    public function test_membuat_perusahaan_tanpa_nama_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/perusahaan', ['alamat' => 'Magelang']);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_membuat_perusahaan_tanpa_alamat_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/perusahaan', ['nama' => 'Tanpa Alamat']);

        $response->assertStatus(422)->assertJsonValidationErrors('alamat');
    }

    public function test_membuat_perusahaan_dengan_jurusan_id_invalid_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/perusahaan', $this->payload([
                'jurusan_ids' => [99999],
            ]));

        $response->assertStatus(422)->assertJsonValidationErrors('jurusan_ids.0');
    }

    public function test_list_perusahaan_dapat_difilter_dengan_jurusan_id(): void
    {
        $tjkt = $this->jurusan('TJKT');
        $dkv = $this->jurusan('DKV');

        $a = Perusahaan::create($this->payload(['nama' => 'PT Menerima TJKT']));
        $a->jurusan()->sync([$tjkt->id]);

        $b = Perusahaan::create($this->payload(['nama' => 'PT Menerima DKV']));
        $b->jurusan()->sync([$dkv->id]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/perusahaan?jurusan_id={$tjkt->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'PT Menerima TJKT');
    }

    public function test_list_perusahaan_dapat_difilter_dengan_search(): void
    {
        Perusahaan::create($this->payload(['nama' => 'PT Magelang Jaya', 'alamat' => 'Jl. Magelang']));
        Perusahaan::create($this->payload(['nama' => 'CV Solo Abadi', 'alamat' => 'Jl. Solo']));

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/perusahaan?search=Magelang')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'PT Magelang Jaya');
    }

    public function test_detail_perusahaan_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/perusahaan/99999')
            ->assertNotFound();
    }

    public function test_seeder_membuat_12_perusahaan_dengan_relasi_dan_idempotent(): void
    {
        $this->seed(JurusanSeeder::class);
        $this->seed(PerusahaanSeeder::class);

        $this->assertCount(12, Perusahaan::all());
        $this->assertSame(16, DB::table('perusahaan_jurusan')->count());
        $this->assertTrue(Perusahaan::all()->every(fn ($p) => $p->jurusan()->count() > 0));

        $this->seed(PerusahaanSeeder::class);

        $this->assertCount(12, Perusahaan::all());
    }
}
