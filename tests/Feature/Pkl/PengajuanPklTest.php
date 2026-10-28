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

class PengajuanPklTest extends TestCase
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

    protected function siswaUser(bool $confirmed = true, ?int $jurusanId = null): User
    {
        $jurusanId = $jurusanId ?? $this->jurusan()->id;
        $gelombang = $this->gelombang();

        $user = User::factory()->create([
            'role_id' => 3,
            'username' => 'siswa_test_'.uniqid(),
            'name' => 'Siswa Test',
            'email' => 'siswa_'.uniqid().'@test.com',
        ]);

        $siswa = Siswa::create([
            'user_id' => $user->id,
            'jurusan_id' => $jurusanId,
            'gelombang_id' => $gelombang->id,
            'nis' => $user->username,
            'jenis_kelamin' => 'L',
            'kelas' => 'XII TJKT 1',
            'telp' => '081234567890',
            'alamat' => 'Jl. Test',
            'nama_ortu' => 'Ortu Test',
            'telp_ortu' => '081298765432',
            'alamat_ortu' => 'Jl. Test',
            'profil_confirmed_at' => $confirmed ? now() : null,
        ]);

        return $user;
    }

    protected function jurusan(string $kode = 'TJKT', string $nama = 'Teknik Jaringan Komputer dan Telekomunikasi'): Jurusan
    {
        return Jurusan::firstOrCreate(['kode' => $kode], ['nama' => $nama]);
    }

    protected function gelombang(string $nama = 'Gelombang Aktif'): Gelombang
    {
        return Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => $nama],
            [
                'daftar_mulai' => now()->subDays(5)->toDateString(),
                'daftar_selesai' => now()->addDays(5)->toDateString(),
                'praktik_mulai' => now()->addMonths(1)->toDateString(),
                'praktik_selesai' => now()->addMonths(6)->toDateString(),
            ],
        );
    }

    protected function gelombangTutup(string $nama = 'Gelombang Tutup'): Gelombang
    {
        return Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => $nama],
            [
                'daftar_mulai' => now()->subDays(20)->toDateString(),
                'daftar_selesai' => now()->subDays(10)->toDateString(),
                'praktik_mulai' => now()->addMonths(1)->toDateString(),
                'praktik_selesai' => now()->addMonths(6)->toDateString(),
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

    public function test_guest_cannot_access_pengajuan(): void
    {
        $this->getJson('/api/pengajuan-pkl')->assertStatus(401);
        $this->postJson('/api/pengajuan-pkl', [])->assertStatus(401);
    }

    public function test_non_siswa_cannot_create_pengajuan(): void
    {
        $admin = $this->admin();
        $guru = $this->guruUser();

        foreach ([$admin, $guru] as $actor) {
            $this->actingAs($actor, 'sanctum')
                ->postJson('/api/pengajuan-pkl', [
                    'perusahaan_id' => 1,
                    'gelombang_id' => 1,
                ])
                ->assertStatus(403);
        }
    }

    public function test_siswa_belum_konfirmasi_tidak_bisa_mengajukan(): void
    {
        $siswa = $this->siswaUser(false);
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('code', 'PROFIL_BELUM_KONFIRMASI');
    }

    public function test_siswa_bisa_mengajukan_pkl(): void
    {
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $response = $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_gelombang_tidak_aktif_ditolak(): void
    {
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombangTutup();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'GELOMBANG_NOT_ACTIVE');
    }

    public function test_pengajuan_duplicate_ditolak(): void
    {
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(201);

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PENGAJUAN_DUPLICATE');
    }

    public function test_jurusan_tidak_sesuai_ditolak(): void
    {
        $jurusanLain = $this->jurusan('TKR', 'Teknik Kendaraan Ringan');
        $siswa = $this->siswaUser(true, $this->jurusan()->id);
        $perusahaan = $this->perusahaan(10, $jurusanLain->id);
        $gelombang = $this->gelombang();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(422);
    }

    public function test_kuota_penuh_ditolak(): void
    {
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan(0); // kuota 0
        $gelombang = $this->gelombang();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PERUSAHAAN_KUOTA_PENUH');
    }

    public function test_siswa_sudah_placed_ditolak(): void
    {
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        // Buat lokasi PKL active manual
        LokasiPkl::create([
            'siswa_id' => $siswa->siswa->id ?? Siswa::where('user_id', $siswa->id)->first()->id,
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'active',
        ]);

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'SISWA_ALREADY_PLACED');
    }

    public function test_admin_bisa_approve_dan_auto_create_lokasi_pkl(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();
        $guru = $this->guruUser();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(201);

        $pengajuanId = $resp->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/pengajuan-pkl/{$pengajuanId}/approve", [
                'guru_pembimbing_id' => $guru->guru->id ?? Guru::where('user_id', $guru->id)->first()->id,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('lokasi_pkl', [
            'siswa_id' => Siswa::where('user_id', $siswa->id)->first()->id,
            'perusahaan_id' => $perusahaan->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('notifications', [
            'type' => 'App\\Notifications\\PengajuanPklReviewedNotification',
        ]);
    }

    public function test_reject_membutuhkan_alasan(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(201);

        $pengajuanId = $resp->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/pengajuan-pkl/{$pengajuanId}/reject", [])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/pengajuan-pkl/{$pengajuanId}/reject", [
                'alasan_penolakan' => 'Kuota penuh',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_siswa_hanya_bisa_cancel_pengajuan_pending_miliknya(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])
            ->assertStatus(201);

        $pengajuanId = $resp->json('data.id');

        // Approve dulu
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/pengajuan-pkl/{$pengajuanId}/approve")
            ->assertStatus(200);

        // Cancel setelah approved → 422
        $this->actingAs($siswa, 'sanctum')
            ->deleteJson("/api/pengajuan-pkl/{$pengajuanId}")
            ->assertStatus(422);
    }

    public function test_bulk_approve(): void
    {
        $admin = $this->admin();
        $siswa1 = $this->siswaUser();
        $siswa2 = $this->siswaUser();
        $perusahaan = $this->perusahaan(10);
        $gelombang = $this->gelombang();

        $r1 = $this->actingAs($siswa1, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])->assertStatus(201)->json('data.id');

        $r2 = $this->actingAs($siswa2, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])->assertStatus(201)->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/pengajuan-pkl/bulk-approve', [
                'ids' => [$r1, $r2],
            ])
            ->assertStatus(200)
            ->assertJsonPath('approved.0', $r1);
    }

    public function test_siswa_hanya_melihat_pengajuan_miliknya(): void
    {
        $siswa = $this->siswaUser();
        $siswa2 = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])->assertStatus(201);

        $this->actingAs($siswa2, 'sanctum')
            ->getJson('/api/pengajuan-pkl')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_siswa_lain_tidak_bisa_cancel_pengajuan_orang_lain(): void
    {
        $siswa = $this->siswaUser();
        $siswa2 = $this->siswaUser();
        $perusahaan = $this->perusahaan();
        $gelombang = $this->gelombang();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/pengajuan-pkl', [
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
            ])->assertStatus(201);

        $pengajuanId = $resp->json('data.id');

        $this->actingAs($siswa2, 'sanctum')
            ->deleteJson("/api/pengajuan-pkl/{$pengajuanId}")
            ->assertStatus(403);
    }
}
