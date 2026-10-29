<?php

namespace Tests\Feature\Absensi;

use App\Models\AbsensiSiswa;
use App\Models\Gelombang;
use App\Models\Jurusan;
use App\Models\LokasiPkl;
use App\Models\Perusahaan;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbsensiCheckinTest extends TestCase
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

    protected function siswaUser(bool $confirmed = true, bool $placed = true): User
    {
        $jurusan = Jurusan::firstOrCreate(['kode' => 'TJKT'], ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi']);
        $gelombang = Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => 'Gelombang Aktif'],
            [
                'daftar_mulai' => now()->subDays(5)->toDateString(),
                'daftar_selesai' => now()->addDays(5)->toDateString(),
                'praktik_mulai' => now()->subDays(5)->toDateString(),
                'praktik_selesai' => now()->addDays(5)->toDateString(),
            ],
        );

        $user = User::factory()->create([
            'role_id' => 3,
            'username' => 'siswa_test_'.uniqid(),
            'name' => 'Siswa Test',
            'email' => 'siswa_'.uniqid().'@test.com',
        ]);

        $siswa = Siswa::create([
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
            'profil_confirmed_at' => $confirmed ? now() : null,
        ]);

        if ($placed) {
            $perusahaan = Perusahaan::create(['nama' => 'PT Test', 'alamat' => 'Jl. Test', 'kuota' => 10]);
            $perusahaan->jurusan()->attach($jurusan->id);
            LokasiPkl::create([
                'siswa_id' => $siswa->id,
                'perusahaan_id' => $perusahaan->id,
                'gelombang_id' => $gelombang->id,
                'status' => 'active',
            ]);
        }

        return $user;
    }

    private function siswaId(User $user): int
    {
        return Siswa::where('user_id', $user->id)->first()->id;
    }

    private function fakeFoto(): UploadedFile
    {
        return UploadedFile::fake()->image('foto.jpg', 100, 100)->size(100);
    }

    public function test_guest_cannot_checkin(): void
    {
        Storage::fake('public');
        $this->postJson('/api/absensi', [])->assertStatus(401);
    }

    public function test_siswa_belum_konfirmasi_tidak_bisa_checkin(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser(false);

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(403);
    }

    public function test_siswa_tanpa_lokasi_aktif_ditolak(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser(true, false);

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'GELOMBANG_NOT_ACTIVE');
    }

    public function test_checkin_hadir_dengan_foto_berhasil(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $response = $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'latitude' => -7.40123456,
                'longitude' => 110.40123456,
                'foto_masuk' => $this->fakeFoto(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('next_action', '/jurnal/create');

        Storage::disk('public')->assertExists(
            AbsensiSiswa::first()->foto_masuk
        );
    }

    public function test_checkin_hadir_tanpa_foto_ditolak(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
            ])
            ->assertStatus(422);
    }

    public function test_checkin_izin_tanpa_keterangan_ditolak(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'izin',
            ])
            ->assertStatus(422);
    }

    public function test_checkin_izin_dengan_keterangan_berhasil(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'izin',
                'keterangan' => 'Izin keperluan keluarga',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_duplicate_presensi_ditolak(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(201);

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ABSENSI_DUPLICATE');
    }

    public function test_upload_foto_retry(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        // Buat absensi partial (failed, tanpa foto)
        $resp = $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'status_submit' => 'failed',
            ])
            ->assertStatus(201);

        $absensiId = $resp->json('data.id');

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi/upload-foto', [
                'absensi_id' => $absensiId,
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('absensi_siswa', [
            'id' => $absensiId,
            'status_submit' => 'success',
        ]);
    }

    public function test_edit_presensi_failed_berhasil(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'status_submit' => 'failed',
            ])
            ->assertStatus(201);

        $absensiId = $resp->json('data.id');

        $this->actingAs($siswa, 'sanctum')
            ->putJson("/api/absensi/{$absensiId}", [
                'status' => 'izin',
                'keterangan' => 'Izin mendadak',
            ])
            ->assertStatus(200);
    }

    public function test_edit_presensi_success_ditolak(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(201);

        $absensiId = $resp->json('data.id');

        $this->actingAs($siswa, 'sanctum')
            ->putJson("/api/absensi/{$absensiId}", [
                'status' => 'izin',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ABSENSI_EDIT_BLOCKED');
    }

    public function test_siswa_bisa_batalkan_presensi_miliknya(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();

        $resp = $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(201);

        $absensiId = $resp->json('data.id');

        $this->actingAs($siswa, 'sanctum')
            ->deleteJson("/api/absensi/{$absensiId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('absensi_siswa', ['id' => $absensiId]);
    }

    public function test_list_presensi_siswa_hanya_miliknya(): void
    {
        Storage::fake('public');
        $siswa = $this->siswaUser();
        $siswa2 = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->post('/api/absensi', [
                'status' => 'hadir',
                'foto_masuk' => $this->fakeFoto(),
            ])
            ->assertStatus(201);

        $this->actingAs($siswa2, 'sanctum')
            ->getJson('/api/absensi')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
