<?php

namespace Tests\Feature\Dashboard;

use App\Models\Gelombang;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\LokasiPkl;
use App\Models\Perusahaan;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['nama' => 'admin']);
        Role::firstOrCreate(['id' => 2], ['nama' => 'guru']);
        Role::firstOrCreate(['id' => 3], ['nama' => 'siswa']);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role_id' => 1, 'username' => 'admin_test']);
    }

    protected function guruUser(): User
    {
        return User::factory()->create(['role_id' => 2, 'username' => 'guru_test']);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/dashboard/admin')->assertStatus(401);
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $guru = $this->guruUser();
        $this->actingAs($guru, 'sanctum')
            ->getJson('/api/dashboard/admin')
            ->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_structure(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard/admin')
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_siswa',
                'total_guru',
                'total_perusahaan',
                'siswa_by_status' => ['pending', 'active', 'completed'],
                'pengajuan_pending',
                'presensi_hari_ini' => ['hadir', 'izin', 'sakit', 'tidak_absen'],
            ]);
    }

    public function test_dashboard_counts_match_data(): void
    {
        $admin = $this->admin();

        // Seed minimal data
        $guruUser = User::factory()->create(['role_id' => 2, 'username' => 'guru1']);
        Guru::create(['user_id' => $guruUser->id, 'nip' => '198701012010011001']);

        $perusahaan = Perusahaan::create(['nama' => 'PT Test', 'alamat' => 'Jl. Test', 'kuota' => 5]);

        $jurusan = Jurusan::firstOrCreate(['kode' => 'TJKT'], ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi']);
        $gelombang = Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => 'Gelombang 1'],
            ['daftar_mulai' => '2026-11-01', 'daftar_selesai' => '2026-11-30', 'praktik_mulai' => '2027-01-05', 'praktik_selesai' => '2027-06-30']
        );

        $siswaUser = User::factory()->create(['role_id' => 3, 'username' => 'siswa1']);
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'jurusan_id' => $jurusan->id,
            'gelombang_id' => $gelombang->id,
            'nis' => '0012345',
        ]);

        LokasiPkl::create([
            'siswa_id' => $siswa->id,
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'active',
        ]);

        Cache::flush();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard/admin')
            ->assertStatus(200);

        $this->assertEquals(1, $response->json('total_siswa'));
        $this->assertEquals(1, $response->json('total_guru'));
        $this->assertEquals(1, $response->json('total_perusahaan'));
        $this->assertEquals(1, $response->json('siswa_by_status.active'));
    }

    public function test_dashboard_cached(): void
    {
        $admin = $this->admin();

        Cache::flush();

        $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard/admin')->assertStatus(200);

        // Verifikasi cache key ada
        $this->assertTrue(Cache::has('dashboard.admin'));
    }
}
