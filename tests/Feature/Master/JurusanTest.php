<?php

namespace Tests\Feature\Master;

use App\Models\Jurusan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\JurusanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanTest extends TestCase
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

    public function test_admin_dapat_membuat_jurusan_baru(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/jurusan', [
                'nama' => 'Teknik Jaringan Komputer dan Telekomunikasi',
                'kode' => 'TJKT',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Jurusan berhasil disimpan.')
            ->assertJsonPath('data.nama', 'Teknik Jaringan Komputer dan Telekomunikasi')
            ->assertJsonPath('data.kode', 'TJKT');

        $this->assertDatabaseHas('jurusan', ['kode' => 'TJKT']);
    }

    public function test_admin_dapat_membuat_jurusan_tanpa_kode(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/jurusan', [
                'nama' => 'Jurusan Tanpa Kode',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.kode', null);

        $this->assertDatabaseHas('jurusan', ['nama' => 'Jurusan Tanpa Kode', 'kode' => null]);
    }

    public function test_admin_dapat_memperbarui_jurusan(): void
    {
        $jurusan = Jurusan::create(['nama' => 'Nama Lama', 'kode' => 'LAMA']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/jurusan/{$jurusan->id}", [
                'nama' => 'Nama Baru',
                'kode' => 'BARU',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Jurusan berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Nama Baru')
            ->assertJsonPath('data.kode', 'BARU');

        $this->assertDatabaseHas('jurusan', ['id' => $jurusan->id, 'nama' => 'Nama Baru']);
    }

    public function test_admin_dapat_menghapus_jurusan(): void
    {
        $jurusan = Jurusan::create(['nama' => 'Akan Dihapus', 'kode' => 'HAP']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/jurusan/{$jurusan->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Jurusan berhasil dihapus.');

        $this->assertDatabaseMissing('jurusan', ['id' => $jurusan->id]);
    }

    public function test_list_jurusan_dapat_dibuka_oleh_semua_role_login(): void
    {
        Jurusan::create(['nama' => 'Teknik Otomotif', 'kode' => 'TKR']);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/jurusan')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.kode', 'TKR');
        }
    }

    public function test_detail_jurusan_dapat_dibuka_oleh_semua_role_login(): void
    {
        $jurusan = Jurusan::create(['nama' => 'Teknik Elektronika', 'kode' => 'TEL']);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson("/api/jurusan/{$jurusan->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $jurusan->id);
        }
    }

    public function test_tamu_tidak_dapat_mengakses_jurusan(): void
    {
        $this->getJson('/api/jurusan')->assertUnauthorized();
        $this->getJson('/api/jurusan/1')->assertUnauthorized();
    }

    public function test_guru_tidak_dapat_menulis_jurusan(): void
    {
        $guru = $this->guru();
        $jurusan = Jurusan::create(['nama' => 'Dari Guru', 'kode' => 'GRU']);

        $this->actingAs($guru, 'sanctum')
            ->postJson('/api/jurusan', ['nama' => 'Guru Buat'])
            ->assertForbidden();

        $this->actingAs($guru, 'sanctum')
            ->putJson("/api/jurusan/{$jurusan->id}", ['nama' => 'Guru Ubah'])
            ->assertForbidden();

        $this->actingAs($guru, 'sanctum')
            ->deleteJson("/api/jurusan/{$jurusan->id}")
            ->assertForbidden();
    }

    public function test_siswa_tidak_dapat_menulis_jurusan(): void
    {
        $siswa = $this->siswa();
        $jurusan = Jurusan::create(['nama' => 'Dari Siswa', 'kode' => 'SIS']);

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/jurusan', ['nama' => 'Siswa Buat'])
            ->assertForbidden();

        $this->actingAs($siswa, 'sanctum')
            ->putJson("/api/jurusan/{$jurusan->id}", ['nama' => 'Siswa Ubah'])
            ->assertForbidden();

        $this->actingAs($siswa, 'sanctum')
            ->deleteJson("/api/jurusan/{$jurusan->id}")
            ->assertForbidden();
    }

    public function test_membuat_jurusan_tanpa_nama_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/jurusan', ['kode' => 'X']);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_membuat_jurusan_dengan_nama_terlalu_panjang_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/jurusan', ['nama' => str_repeat('A', 101)]);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_update_jurusan_tanpa_nama_ditolak(): void
    {
        $jurusan = Jurusan::create(['nama' => 'Sebelumnya', 'kode' => 'SBM']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/jurusan/{$jurusan->id}", ['kode' => 'SESUDAH']);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_list_jurusan_dapat_difilter_dengan_search(): void
    {
        Jurusan::create(['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi', 'kode' => 'TJKT']);
        Jurusan::create(['nama' => 'Desain Komunikasi Visual', 'kode' => 'DKV']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/jurusan?search=TJKT')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode', 'TJKT');

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/jurusan?search=Visual')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode', 'DKV');
    }

    public function test_detail_jurusan_tidak_ada_mengembalikan_404(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/jurusan/99999')
            ->assertNotFound();
    }

    public function test_seeder_membuat_10_jurusan_dan_idempotent(): void
    {
        $this->seed(JurusanSeeder::class);

        $this->assertCount(10, Jurusan::all());
        $this->assertDatabaseHas('jurusan', ['kode' => 'TJKT', 'nama' => 'Teknik Jaringan Komputer dan Telekomunikasi']);

        $this->seed(JurusanSeeder::class);

        $this->assertCount(10, Jurusan::all());
    }
}
