<?php

namespace Tests\Feature\Pkl;

use App\Models\Gelombang;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\LokasiPkl;
use App\Models\Perusahaan;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasiPklTest extends TestCase
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
        $user = User::factory()->create(['role_id' => 2, 'username' => 'guru_test']);
        Guru::firstOrCreate(['user_id' => $user->id], ['nip' => '198701012010011001']);

        return $user;
    }

    protected function siswaUser(): User
    {
        $jurusan = $this->jurusan();
        $gelombang = $this->gelombang();

        $user = User::factory()->create([
            'role_id' => 3,
            'username' => 'siswa_test_'.uniqid(),
            'name' => 'Siswa Test',
            'email' => 'siswa_'.uniqid().'@test.com',
        ]);

        Siswa::create([
            'user_id' => $user->id,
            'jurusan_id' => $jurusan->id,
            'gelombang_id' => $gelombang->id,
            'nis' => $user->username,
            'jenis_kelamin' => 'L',
            'kelas' => 'XII TJKT 1',
            'telp' => '081234567890',
            'alamat' => 'Jl. Test',
            'nama_ortu' => 'Ortu Test',
            'telp_ortu' => '081298765432',
            'alamat_ortu' => 'Jl. Test',
            'profil_confirmed_at' => now(),
        ]);

        return $user;
    }

    protected function jurusan(string $kode = 'TJKT', string $nama = 'Teknik Jaringan Komputer dan Telekomunikasi'): Jurusan
    {
        return Jurusan::firstOrCreate(['kode' => $kode], ['nama' => $nama]);
    }

    protected function gelombang(string $nama = 'Gelombang 1'): Gelombang
    {
        return Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => $nama],
            [
                'daftar_mulai' => '2026-11-01',
                'daftar_selesai' => '2026-11-30',
                'praktik_mulai' => '2027-01-05',
                'praktik_selesai' => '2027-06-30',
            ],
        );
    }

    protected function perusahaan(int $kuota = 10, ?int $jurusanId = null): Perusahaan
    {
        $jurusanId = $jurusanId ?? $this->jurusan()->id;
        $perusahaan = Perusahaan::create([
            'nama' => 'PT Test '.uniqid(),
            'alamat' => 'Jl. Test',
            'kuota' => $kuota,
        ]);
        $perusahaan->jurusan()->attach($jurusanId);

        return $perusahaan;
    }

    private function siswaId(User $user): int
    {
        return Siswa::where('user_id', $user->id)->first()->id;
    }

    private function guruId(User $user): int
    {
        return Guru::where('user_id', $user->id)->first()->id;
    }

    public function test_guest_cannot_access_lokasi_pkl(): void
    {
        $this->getJson('/api/lokasi-pkl')->assertStatus(401);
        $this->postJson('/api/lokasi-pkl', [])->assertStatus(401);
    }

    public function test_non_admin_cannot_write_lokasi_pkl(): void
    {
        $guru = $this->guruUser();
        $siswa = $this->siswaUser();

        foreach ([$guru, $siswa] as $actor) {
            $this->actingAs($actor, 'sanctum')
                ->postJson('/api/lokasi-pkl', [])
                ->assertStatus(403);
        }
    }

    public function test_admin_can_create_lokasi_pkl(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();
        $guru = $this->guruUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/lokasi-pkl', [
                'siswa_id' => $this->siswaId($siswa),
                'perusahaan_id' => $perusahaan->id,
                'guru_id' => $this->guruId($guru),
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_siswa_sudah_placed_ditolak(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        LokasiPkl::create([
            'siswa_id' => $this->siswaId($siswa),
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/lokasi-pkl', [
                'siswa_id' => $this->siswaId($siswa),
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SISWA_ALREADY_PLACED');
    }

    public function test_status_transition_valid(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $lokasi = LokasiPkl::create([
            'siswa_id' => $this->siswaId($siswa),
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/lokasi-pkl/{$lokasi->id}/status", ['status' => 'active'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/lokasi-pkl/{$lokasi->id}/status", ['status' => 'completed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_status_transition_invalid(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $lokasi = LokasiPkl::create([
            'siswa_id' => $this->siswaId($siswa),
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'completed',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/lokasi-pkl/{$lokasi->id}/status", ['status' => 'active'])
            ->assertStatus(422);
    }

    public function test_admin_bisa_update_dan_delete_lokasi_pkl(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $lokasi = LokasiPkl::create([
            'siswa_id' => $this->siswaId($siswa),
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/lokasi-pkl/{$lokasi->id}", ['catatan' => 'Catatan baru'])
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/lokasi-pkl/{$lokasi->id}")
            ->assertStatus(200);
    }

    public function test_siswa_hanya_bisa_lihat_lokasi_pkl_miliknya(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $siswa2 = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $lokasi = LokasiPkl::create([
            'siswa_id' => $this->siswaId($siswa),
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'active',
        ]);

        $this->actingAs($siswa, 'sanctum')
            ->getJson("/api/siswa/{$this->siswaId($siswa)}/lokasi-pkl")
            ->assertStatus(200);

        $this->actingAs($siswa2, 'sanctum')
            ->getJson("/api/siswa/{$this->siswaId($siswa)}/lokasi-pkl")
            ->assertStatus(403);
    }

    public function test_guru_siswa_bimbingan(): void
    {
        $admin = $this->admin();
        $guru = $this->guruUser();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        LokasiPkl::create([
            'siswa_id' => $this->siswaId($siswa),
            'guru_id' => $this->guruId($guru),
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'active',
        ]);

        $this->actingAs($guru, 'sanctum')
            ->getJson("/api/guru/{$this->guruId($guru)}/siswa-bimbingan")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_kuota_tersedia_perusahaan(): void
    {
        $admin = $this->admin();
        $perusahaanPenuh = $this->perusahaan(0);
        $perusahaanKosong = $this->perusahaan(10);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/perusahaan?kuota_tersedia=1')
            ->assertStatus(200);
    }
}
