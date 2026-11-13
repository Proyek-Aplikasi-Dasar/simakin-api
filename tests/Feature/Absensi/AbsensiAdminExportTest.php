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

class AbsensiAdminExportTest extends TestCase
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
            ['daftar_mulai' => now()->subDays(5)->toDateString(), 'daftar_selesai' => now()->addDays(5)->toDateString(), 'praktik_mulai' => now()->subDays(5)->toDateString(), 'praktik_selesai' => now()->addDays(5)->toDateString()],
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
            'kelas' => 'XII TJKT 1',
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

    public function test_admin_bisa_tambah_presensi_manual(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $siswa = $this->siswaUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/absensi/manual', [
                'siswa_id' => $this->siswaId($siswa),
                'tanggal' => now()->toDateString(),
                'status' => 'hadir',
                'jam_shift_mulai' => '07:00',
                'jam_shift_selesai' => '15:00',
                'jam_masuk' => '08:00',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('absensi_siswa', [
            'siswa_id' => $this->siswaId($siswa),
            'status' => 'hadir',
            'dikoreksi_oleh' => $admin->id,
        ]);

        Notification::assertSentTo($siswa, AbsensiCorrectedNotification::class);
    }

    public function test_guru_pembimbing_bisa_tambah_presensi_manual(): void
    {
        $guru = $this->guruUser();
        $siswa = $this->siswaUser();

        $lokasi = LokasiPkl::where('siswa_id', $this->siswaId($siswa))->where('status', 'active')->first();
        $lokasi->update(['guru_id' => Guru::where('user_id', $guru->id)->first()->id]);

        $this->actingAs($guru, 'sanctum')
            ->postJson('/api/absensi/manual', [
                'siswa_id' => $this->siswaId($siswa),
                'tanggal' => now()->toDateString(),
                'status' => 'izin',
                'keterangan' => 'Izin manual oleh guru',
            ])
            ->assertStatus(201);
    }

    public function test_guru_bukan_pembimbing_tidak_bisa_tambah_presensi_manual(): void
    {
        $guru = $this->guruUser();
        $siswa = $this->siswaUser();

        $this->actingAs($guru, 'sanctum')
            ->postJson('/api/absensi/manual', [
                'siswa_id' => $this->siswaId($siswa),
                'tanggal' => now()->toDateString(),
                'status' => 'hadir',
            ])
            ->assertStatus(403);
    }

    public function test_siswa_tidak_bisa_tambah_presensi_manual(): void
    {
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->postJson('/api/absensi/manual', [
                'siswa_id' => $this->siswaId($siswa),
                'tanggal' => now()->toDateString(),
                'status' => 'hadir',
            ])
            ->assertStatus(403);
    }

    public function test_tambah_presensi_manual_duplicate_ditolak(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/absensi/manual', [
                'siswa_id' => $this->siswaId($siswa),
                'tanggal' => now()->toDateString(),
                'status' => 'hadir',
            ])
            ->assertStatus(201);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/absensi/manual', [
                'siswa_id' => $this->siswaId($siswa),
                'tanggal' => now()->toDateString(),
                'status' => 'hadir',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ABSENSI_DUPLICATE');
    }

    public function test_koreksi_presensi_memperbarui_jam_shift(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaUser();

        $absensi = AbsensiSiswa::create([
            'siswa_id' => $this->siswaId($siswa),
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '08:00:00',
            'status_submit' => 'success',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/absensi/{$absensi->id}/koreksi", [
                'status' => 'hadir',
                'keterangan' => 'Dikoreksi',
                'jam_shift_mulai' => '07:30',
                'jam_shift_selesai' => '15:30',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('absensi_siswa', [
            'id' => $absensi->id,
            'jam_shift_mulai' => '07:30',
            'jam_shift_selesai' => '15:30',
        ]);
    }

    public function test_export_excel_presensi_admin(): void
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
            ->get('/api/absensi/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_export_excel_presensi_guru(): void
    {
        $guru = $this->guruUser();

        $response = $this->actingAs($guru, 'sanctum')
            ->get('/api/absensi/export');

        $response->assertStatus(200);
    }

    public function test_export_excel_presensi_siswa_forbidden(): void
    {
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->get('/api/absensi/export')
            ->assertStatus(403);
    }

    public function test_filter_search_dan_jurusan_pada_list_absensi(): void
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
            ->getJson('/api/absensi?search=Siswa')
            ->assertStatus(200);

        $jurusanId = Jurusan::where('kode', 'TJKT')->first()->id;
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/absensi?jurusan_id={$jurusanId}")
            ->assertStatus(200);
    }
}
