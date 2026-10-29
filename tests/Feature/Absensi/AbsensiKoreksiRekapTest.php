<?php

namespace Tests\Feature\Absensi;

use App\Models\AbsensiSiswa;
use App\Models\Gelombang;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\LokasiPkl;
use App\Models\Perusahaan;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Notifications\AbsensiCorrectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AbsensiKoreksiRekapTest extends TestCase
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
        $jurusan = Jurusan::firstOrCreate(['kode' => 'TJKT'], ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi']);
        $gelombang = Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => 'Gelombang Aktif'],
            ['daftar_mulai' => '2026-11-01', 'daftar_selesai' => '2026-11-30', 'praktik_mulai' => '2027-01-05', 'praktik_selesai' => '2027-06-30'],
        );

        $user = User::factory()->create([
            'role_id' => 3,
            'username' => 'siswa_test_' . uniqid(),
            'name' => 'Siswa Test',
            'email' => 'siswa_' . uniqid() . '@test.com',
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
            'profil_confirmed_at' => now(),
        ]);

        $perusahaan = Perusahaan::create(['nama' => 'PT Test', 'alamat' => 'Jl. Test', 'kuota' => 10]);
        $perusahaan->jurusan()->attach($jurusan->id);
        LokasiPkl::create([
            'siswa_id' => $siswa->id,
            'perusahaan_id' => $perusahaan->id,
            'gelombang_id' => $gelombang->id,
            'status' => 'active',
        ]);

        return $user;
    }

    private function siswaId(User $user): int
    {
        return Siswa::where('user_id', $user->id)->first()->id;
    }

    private function guruId(User $user): int
    {
        return Guru::where('user_id', $user->id)->first()->id;
    }

    public function test_admin_bisa_koreksi_presensi(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $siswa = $this->siswaUser();

        $absensi = AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:30:00',
            'status_submit' => 'success',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/absensi/{$absensi->id}/koreksi", [
                'status' => 'sakit',
                'keterangan' => 'Dikoreksi oleh admin',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'sakit');

        $this->assertDatabaseHas('absensi_siswa', [
            'id' => $absensi->id,
            'status' => 'sakit',
            'dikoreksi_oleh' => $admin->id,
        ]);

        Notification::assertSentTo($siswa, AbsensiCorrectedNotification::class);
    }

    public function test_guru_bisa_koreksi_siswa_bimbingannya(): void
    {
        Notification::fake();

        $guru = $this->guruUser();
        $siswa = $this->siswaUser();

        // Assign guru sebagai pembimbing di lokasi PKL active
        $lokasi = LokasiPkl::where('siswa_id', $this->siswaId($siswa))->where('status', 'active')->first();
        $lokasi->update(['guru_id' => $this->guruId($guru)]);

        $absensi = AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:30:00',
            'status_submit' => 'success',
        ]);

        $this->actingAs($guru, 'sanctum')
            ->postJson("/api/absensi/{$absensi->id}/koreksi", [
                'status' => 'izin',
                'keterangan' => 'Dikoreksi oleh guru',
            ])
            ->assertStatus(200);
    }

    public function test_guru_bukan_pembimbing_ditolak(): void
    {
        $guru = $this->guruUser();
        $siswa = $this->siswaUser();

        $absensi = AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:30:00',
            'status_submit' => 'success',
        ]);

        $this->actingAs($guru, 'sanctum')
            ->postJson("/api/absensi/{$absensi->id}/koreksi", [
                'status' => 'izin',
                'keterangan' => 'Dikoreksi',
            ])
            ->assertStatus(403);
    }

    public function test_rekap_absensi_akurat(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();

        AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:00:00',
            'status_submit' => 'success',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/absensi/rekap')
            ->assertStatus(200);

        // Verifikasi data ada
        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $this->siswaId($siswa),
            'status' => 'hadir',
        ]);
    }

    public function test_periode_siswa(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();

        AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:00:00',
            'status_submit' => 'success',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/absensi/siswa/{$this->siswaId($siswa)}/periode?bulan=" . now()->month . '&tahun=' . now()->year)
            ->assertStatus(200)
            ->assertJsonStructure(['siswa', 'bulan', 'tahun', 'rekap', 'data']);
    }

    public function test_siswa_hanya_bisa_lihat_periode_miliknya(): void
    {
        $siswa = $this->siswaUser();
        $siswa2 = $this->siswaUser();

        $this->actingAs($siswa2, 'sanctum')
            ->getJson("/api/absensi/siswa/{$this->siswaId($siswa)}/periode")
            ->assertStatus(403);
    }

    public function test_siswa_bimbingan_menampilkan_presensi_hari_ini(): void
    {
        $guru = $this->guruUser();
        $siswa = $this->siswaUser();

        $lokasi = LokasiPkl::where('siswa_id', $this->siswaId($siswa))->where('status', 'active')->first();
        $lokasi->update(['guru_id' => $this->guruId($guru)]);

        AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '07:58:00',
            'status_submit' => 'success',
        ]);

        $response = $this->actingAs($guru, 'sanctum')
            ->getJson("/api/guru/{$this->guruId($guru)}/siswa-bimbingan")
            ->assertStatus(200);

        $this->assertNotNull($response->json('data.0.presensi_hari_ini'));
        $this->assertEquals('hadir', $response->json('data.0.presensi_hari_ini.status'));
    }

    public function test_dashboard_admin_menampilkan_presensi_hari_ini(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();

        AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:00:00',
            'status_submit' => 'success',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/dashboard/admin')
            ->assertStatus(200);

        $this->assertEquals(1, $response->json('presensi_hari_ini.hadir'));
    }
}
