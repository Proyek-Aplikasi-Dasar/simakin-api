<?php

namespace Tests\Feature\User;

use App\Models\Gelombang;
use App\Models\Jurusan;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OnboardingSiswaTest extends TestCase
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

    protected function jurusan(): Jurusan
    {
        return Jurusan::firstOrCreate(
            ['kode' => 'TJKT'],
            ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi'],
        );
    }

    protected function gelombang(): Gelombang
    {
        return Gelombang::firstOrCreate(
            ['tahun_ajaran' => '2026/2027', 'nama' => 'Gelombang 1'],
            [
                'daftar_mulai' => '2026-11-01',
                'daftar_selesai' => '2026-11-30',
                'praktik_mulai' => '2027-01-05',
                'praktik_selesai' => '2027-06-30',
            ],
        );
    }

    /**
     * Buat user siswa + profil siswa (default: semua field wajib terisi
     * kecuali jurusan_id & gelombang_id, sehingga profil belum lengkap).
     *
     * @param  array<string, mixed>  $siswaOverrides
     * @param  array<string, mixed>  $userOverrides
     * @return array{user: User, siswa: Siswa}
     */
    protected function makeSiswa(array $siswaOverrides = [], array $userOverrides = []): array
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Siswa Onboarding',
            'username' => '0012345',
            'email' => 'siswa@example.com',
            'role_id' => 3,
        ], $userOverrides));

        $siswa = Siswa::create(array_merge([
            'user_id' => $user->id,
            'nis' => $user->username,
            'jenis_kelamin' => 'L',
            'kelas' => 'XII TJKT 1',
            'telp' => '081234567890',
            'alamat' => 'Jl. Pemuda No. 1, Magelang',
            'nama_ortu' => 'Bapak Siswa',
            'telp_ortu' => '081298765432',
            'alamat_ortu' => 'Jl. Pemuda No. 1, Magelang',
        ], $siswaOverrides));

        return ['user' => $user, 'siswa' => $siswa];
    }

    /**
     * Profil siswa lengkap (semua field wajib terisi).
     *
     * @param  array<string, mixed>  $siswaOverrides
     * @param  array<string, mixed>  $userOverrides
     * @return array{user: User, siswa: Siswa}
     */
    protected function completeSiswa(array $siswaOverrides = [], array $userOverrides = []): array
    {
        return $this->makeSiswa(array_merge([
            'jurusan_id' => $this->jurusan()->id,
            'gelombang_id' => $this->gelombang()->id,
        ], $siswaOverrides), $userOverrides);
    }

    /**
     * Profil siswa lengkap dan sudah dikonfirmasi.
     *
     * @return array{user: User, siswa: Siswa}
     */
    protected function confirmedSiswa(): array
    {
        $data = $this->completeSiswa();
        $data['siswa']->update(['profil_confirmed_at' => now()]);

        return $data;
    }

    public function test_guest_cannot_access_onboarding(): void
    {
        $this->getJson('/api/siswa/me')->assertStatus(401);
        $this->putJson('/api/siswa/me/profil', ['name' => 'X'])->assertStatus(401);
        $this->putJson('/api/siswa/me/konfirmasi')->assertStatus(401);
    }

    public function test_non_siswa_cannot_access_onboarding(): void
    {
        foreach ([$this->admin(), $this->guruUser()] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/siswa/me')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->putJson('/api/siswa/me/profil', ['name' => 'X'])->assertStatus(403);
            $this->actingAs($user, 'sanctum')->putJson('/api/siswa/me/konfirmasi')->assertStatus(403);
        }
    }

    public function test_me_returns_incomplete_and_unconfirmed_profile(): void
    {
        $data = $this->makeSiswa();

        $this->actingAs($data['user'], 'sanctum')
            ->getJson('/api/siswa/me')
            ->assertStatus(200)
            ->assertJsonPath('data.user.username', '0012345')
            ->assertJsonPath('data.siswa.nis', '0012345')
            ->assertJsonPath('data.akademik.jurusan', null)
            ->assertJsonPath('data.akademik.gelombang', null)
            ->assertJsonPath('data.profil_confirmed_at', null)
            ->assertJsonPath('data.profil_terkonfirmasi', false)
            ->assertJsonPath('data.profil_lengkap', false);
    }

    public function test_me_returns_complete_profile_with_akademik(): void
    {
        $data = $this->completeSiswa();

        $this->actingAs($data['user'], 'sanctum')
            ->getJson('/api/siswa/me')
            ->assertStatus(200)
            ->assertJsonPath('data.akademik.jurusan.kode', 'TJKT')
            ->assertJsonPath('data.akademik.gelombang.nama', 'Gelombang 1')
            ->assertJsonPath('data.akademik.gelombang.tahun_ajaran', '2026/2027')
            ->assertJsonPath('data.profil_lengkap', true)
            ->assertJsonPath('data.profil_terkonfirmasi', false);
    }

    public function test_siswa_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'username' => 'siswa_tanpa_profil']);

        $this->actingAs($user, 'sanctum')->getJson('/api/siswa/me')->assertStatus(404);
    }

    public function test_siswa_can_partially_update_profile(): void
    {
        $data = $this->makeSiswa();

        $this->actingAs($data['user'], 'sanctum')->putJson('/api/siswa/me/profil', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'telp' => '081200000000',
            'nama_ortu' => 'Ibu Baru',
        ])->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Nama Baru')
            ->assertJsonPath('data.user.email', 'baru@example.com')
            ->assertJsonPath('data.siswa.telp', '081200000000')
            ->assertJsonPath('data.siswa.nama_ortu', 'Ibu Baru');

        $this->assertDatabaseHas('users', ['id' => $data['user']->id, 'name' => 'Nama Baru', 'email' => 'baru@example.com']);
        $this->assertDatabaseHas('siswa', ['id' => $data['siswa']->id, 'telp' => '081200000000']);
    }

    public function test_update_profil_ignores_nis_and_username(): void
    {
        $data = $this->makeSiswa();

        $this->actingAs($data['user'], 'sanctum')->putJson('/api/siswa/me/profil', [
            'name' => 'Nama Baru',
            'nis' => '9999999',
            'username' => '9999999',
        ])->assertStatus(200)
            ->assertJsonPath('data.siswa.nis', '0012345')
            ->assertJsonPath('data.user.username', '0012345');

        $this->assertDatabaseHas('siswa', ['id' => $data['siswa']->id, 'nis' => '0012345']);
        $this->assertDatabaseHas('users', ['id' => $data['user']->id, 'username' => '0012345']);
    }

    public function test_update_profil_rejects_email_used_by_another_user(): void
    {
        $data = $this->makeSiswa();

        User::factory()->create([
            'role_id' => 2,
            'username' => 'guru_lain',
            'email' => 'dipakai@example.com',
        ]);

        $this->actingAs($data['user'], 'sanctum')->putJson('/api/siswa/me/profil', [
            'email' => 'dipakai@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_siswa_cannot_update_profile_after_confirmation(): void
    {
        $data = $this->confirmedSiswa();

        $this->actingAs($data['user'], 'sanctum')->putJson('/api/siswa/me/profil', [
            'name' => 'Nama Setelah Konfirmasi',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'PROFIL_SUDAH_DIKONFIRMASI');

        $this->assertDatabaseHas('users', ['id' => $data['user']->id, 'name' => 'Siswa Onboarding']);
    }

    public function test_konfirmasi_rejects_incomplete_profile(): void
    {
        $data = $this->makeSiswa([], ['email' => null]);

        $this->actingAs($data['user'], 'sanctum')
            ->putJson('/api/siswa/me/konfirmasi')
            ->assertStatus(422)
            ->assertJsonPath('code', 'PROFIL_BELUM_LENGKAP')
            ->assertJsonStructure([
                'message',
                'code',
                'errors' => ['email', 'jurusan_id', 'gelombang_id'],
            ]);

        $this->assertDatabaseHas('siswa', ['id' => $data['siswa']->id, 'profil_confirmed_at' => null]);
    }

    public function test_konfirmasi_complete_profile_sets_timestamp(): void
    {
        $data = $this->completeSiswa();

        $this->actingAs($data['user'], 'sanctum')
            ->putJson('/api/siswa/me/konfirmasi')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Profil berhasil dikonfirmasi.')
            ->assertJsonPath('data.profil_terkonfirmasi', true)
            ->assertJsonPath('data.profil_lengkap', true);

        $this->assertNotNull($data['siswa']->fresh()->profil_confirmed_at);
    }

    public function test_konfirmasi_twice_is_idempotent(): void
    {
        $data = $this->confirmedSiswa();
        $before = $data['siswa']->fresh()->profil_confirmed_at;

        $this->actingAs($data['user'], 'sanctum')
            ->putJson('/api/siswa/me/konfirmasi')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Profil sudah dikonfirmasi sebelumnya.');

        $this->assertTrue($before->equalTo($data['siswa']->fresh()->profil_confirmed_at));
    }

    public function test_admin_can_update_siswa_after_confirmation_without_reset(): void
    {
        $data = $this->confirmedSiswa();
        $before = $data['siswa']->fresh()->profil_confirmed_at;

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson('/api/siswa/'.$data['siswa']->id, ['name' => 'Diubah Admin'])
            ->assertStatus(200)
            ->assertJsonPath('data.profil_terkonfirmasi', true);

        $this->assertDatabaseHas('users', ['id' => $data['user']->id, 'name' => 'Diubah Admin']);
        $this->assertTrue($before->equalTo($data['siswa']->fresh()->profil_confirmed_at));
    }

    public function test_middleware_blocks_unconfirmed_siswa(): void
    {
        Route::middleware(['auth:sanctum', 'profil.confirmed'])
            ->get('/api/__test/profil-gate', fn () => response()->json(['ok' => true]));

        $data = $this->makeSiswa();

        $this->actingAs($data['user'], 'sanctum')
            ->getJson('/api/__test/profil-gate')
            ->assertStatus(403)
            ->assertJsonPath('code', 'PROFIL_BELUM_KONFIRMASI');
    }

    public function test_middleware_allows_confirmed_siswa(): void
    {
        Route::middleware(['auth:sanctum', 'profil.confirmed'])
            ->get('/api/__test/profil-gate', fn () => response()->json(['ok' => true]));

        $data = $this->confirmedSiswa();

        $this->actingAs($data['user'], 'sanctum')
            ->getJson('/api/__test/profil-gate')
            ->assertStatus(200)
            ->assertJsonPath('ok', true);
    }

    public function test_middleware_allows_non_siswa(): void
    {
        Route::middleware(['auth:sanctum', 'profil.confirmed'])
            ->get('/api/__test/profil-gate', fn () => response()->json(['ok' => true]));

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/__test/profil-gate')
            ->assertStatus(200);
    }
}
