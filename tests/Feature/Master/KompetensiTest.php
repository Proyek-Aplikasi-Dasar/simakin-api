<?php

namespace Tests\Feature\Master;

use App\Models\Jurusan;
use App\Models\Kompetensi;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\JurusanSeeder;
use Database\Seeders\KompetensiSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KompetensiTest extends TestCase
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

    private function jurusan(array $attributes = []): Jurusan
    {
        return Jurusan::create(array_merge([
            'nama' => 'Teknik Jaringan Komputer dan Telekomunikasi',
            'kode' => 'TJKT',
        ], $attributes));
    }

    public function test_admin_dapat_membuat_kompetensi_baru(): void
    {
        $jurusan = $this->jurusan();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/kompetensi', [
                'jurusan_id' => $jurusan->id,
                'nama' => 'Instalasi Jaringan',
                'kode' => 'TJKT-04',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Kompetensi berhasil disimpan.')
            ->assertJsonPath('data.nama', 'Instalasi Jaringan')
            ->assertJsonPath('data.jurusan_id', $jurusan->id);

        $this->assertDatabaseHas('kompetensi', ['nama' => 'Instalasi Jaringan']);
    }

    public function test_admin_dapat_memperbarui_kompetensi(): void
    {
        $jurusan = $this->jurusan();
        $kompetensi = Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Nama Lama']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/kompetensi/{$kompetensi->id}", [
                'jurusan_id' => $jurusan->id,
                'nama' => 'Nama Baru',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Kompetensi berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Nama Baru');

        $this->assertDatabaseHas('kompetensi', ['id' => $kompetensi->id, 'nama' => 'Nama Baru']);
    }

    public function test_admin_dapat_menghapus_kompetensi(): void
    {
        $jurusan = $this->jurusan();
        $kompetensi = Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Akan Dihapus']);

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/kompetensi/{$kompetensi->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Kompetensi berhasil dihapus.');

        $this->assertDatabaseMissing('kompetensi', ['id' => $kompetensi->id]);
    }

    public function test_list_kompetensi_dapat_dibuka_oleh_semua_role_login(): void
    {
        $jurusan = $this->jurusan();
        Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Instalasi Jaringan']);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/kompetensi')
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.nama', 'Instalasi Jaringan');
        }
    }

    public function test_detail_kompetensi_dapat_dibuka_oleh_semua_role_login(): void
    {
        $jurusan = $this->jurusan();
        $kompetensi = Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Administrasi Server']);

        foreach ([$this->admin(), $this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->getJson("/api/kompetensi/{$kompetensi->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $kompetensi->id)
                ->assertJsonPath('data.jurusan.kode', 'TJKT');
        }
    }

    public function test_tamu_tidak_dapat_mengakses_kompetensi(): void
    {
        $this->getJson('/api/kompetensi')->assertUnauthorized();
        $this->getJson('/api/kompetensi/1')->assertUnauthorized();
    }

    public function test_guru_dan_siswa_tidak_dapat_menulis_kompetensi(): void
    {
        $jurusan = $this->jurusan();
        $kompetensi = Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Sebelumnya']);

        foreach ([$this->guru(), $this->siswa()] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/kompetensi', ['jurusan_id' => $jurusan->id, 'nama' => 'Ditolak'])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->putJson("/api/kompetensi/{$kompetensi->id}", ['jurusan_id' => $jurusan->id, 'nama' => 'Ditolak'])
                ->assertForbidden();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/kompetensi/{$kompetensi->id}")
                ->assertForbidden();
        }
    }

    public function test_membuat_kompetensi_tanpa_jurusan_id_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/kompetensi', ['nama' => 'Tanpa Jurusan']);

        $response->assertStatus(422)->assertJsonValidationErrors('jurusan_id');
    }

    public function test_membuat_kompetensi_dengan_jurusan_id_invalid_ditolak(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/kompetensi', [
                'jurusan_id' => 99999,
                'nama' => 'Jurusan Yatim',
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors('jurusan_id');
    }

    public function test_membuat_kompetensi_tanpa_nama_ditolak(): void
    {
        $jurusan = $this->jurusan();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/kompetensi', ['jurusan_id' => $jurusan->id]);

        $response->assertStatus(422)->assertJsonValidationErrors('nama');
    }

    public function test_list_kompetensi_dapat_difilter_dengan_jurusan_id(): void
    {
        $tjkt = $this->jurusan();
        $dkv = $this->jurusan(['nama' => 'Desain Komunikasi Visual', 'kode' => 'DKV']);

        Kompetensi::create(['jurusan_id' => $tjkt->id, 'nama' => 'Instalasi Jaringan']);
        Kompetensi::create(['jurusan_id' => $dkv->id, 'nama' => 'Desain Grafis Digital']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson("/api/kompetensi?jurusan_id={$tjkt->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Instalasi Jaringan');
    }

    public function test_list_kompetensi_dapat_difilter_dengan_search(): void
    {
        $jurusan = $this->jurusan();

        Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Instalasi Jaringan']);
        Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Administrasi Server']);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/kompetensi?search=Server')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Administrasi Server');
    }

    public function test_route_nested_mengembalikan_kompetensi_satu_jurusan(): void
    {
        $tjkt = $this->jurusan();
        $dkv = $this->jurusan(['nama' => 'Desain Komunikasi Visual', 'kode' => 'DKV']);

        Kompetensi::create(['jurusan_id' => $tjkt->id, 'nama' => 'Instalasi Jaringan']);
        Kompetensi::create(['jurusan_id' => $tjkt->id, 'nama' => 'Administrasi Server']);
        Kompetensi::create(['jurusan_id' => $dkv->id, 'nama' => 'Desain Grafis Digital']);

        $this->actingAs($this->siswa(), 'sanctum')
            ->getJson("/api/jurusan/{$tjkt->id}/kompetensi")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama', 'Administrasi Server')
            ->assertJsonPath('data.1.nama', 'Instalasi Jaringan');
    }

    public function test_route_nested_mengembalikan_404_bila_jurusan_tidak_ada(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/jurusan/99999/kompetensi')
            ->assertNotFound();
    }

    public function test_menghapus_jurusan_menghapus_kompetensinya_cascade(): void
    {
        $jurusan = $this->jurusan();
        $kompetensi = Kompetensi::create(['jurusan_id' => $jurusan->id, 'nama' => 'Akan Ikut Terhapus']);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/jurusan/{$jurusan->id}")
            ->assertOk();

        $this->assertDatabaseMissing('jurusan', ['id' => $jurusan->id]);
        $this->assertDatabaseMissing('kompetensi', ['id' => $kompetensi->id]);
    }

    public function test_seeder_membuat_minimal_50_kompetensi_dan_idempotent(): void
    {
        $this->seed(JurusanSeeder::class);
        $this->seed(KompetensiSeeder::class);

        $this->assertGreaterThanOrEqual(50, Kompetensi::count());
        $this->assertDatabaseHas('kompetensi', ['kode' => 'TJKT-01', 'nama' => 'Konfigurasi Jaringan Kabel Terstruktur']);

        $this->seed(KompetensiSeeder::class);

        $this->assertSame(50, Kompetensi::count());
    }
}
