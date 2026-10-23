<?php

namespace Tests\Feature\User;

use App\Models\Gelombang;
use App\Models\Jurusan;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SiswaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * File sementara yang dibuat selama test.
     *
     * @var array<int, string>
     */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['nama' => 'admin']);
        Role::firstOrCreate(['id' => 2], ['nama' => 'guru']);
        Role::firstOrCreate(['id' => 3], ['nama' => 'siswa']);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    protected function admin(): User
    {
        return User::factory()->create(['role_id' => 1, 'username' => 'admin_test']);
    }

    protected function guruUser(): User
    {
        return User::factory()->create(['role_id' => 2, 'username' => 'guru_test']);
    }

    protected function siswaUser(): User
    {
        return User::factory()->create(['role_id' => 3, 'username' => 'siswa_test']);
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

    /**
     * Buat siswa lewat API sebagai admin.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function createSiswa(User $admin, array $overrides = []): array
    {
        $payload = array_merge([
            'name' => 'Siswa Test',
            'nis' => '0012345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);

        return $this->actingAs($admin, 'sanctum')
            ->postJson('/api/siswa', $payload)
            ->assertStatus(201)
            ->json();
    }

    /**
     * Buat file .xlsx sementara dari array baris.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeXlsx(array $rows): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('siswa_import_', true).'.xlsx';

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->tempFiles[] = $path;

        return $path;
    }

    private function makeFile(string $contents, string $extension): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('siswa_import_', true).'.'.$extension;
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }

    private function xlsxUpload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'siswa.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    public function test_guest_cannot_access_siswa(): void
    {
        $this->getJson('/api/siswa')->assertStatus(401);
    }

    public function test_admin_can_list_siswa(): void
    {
        $admin = $this->admin();
        $this->createSiswa($admin);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/siswa')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nis', '0012345');
    }

    public function test_guru_can_list_and_view_siswa(): void
    {
        $created = $this->createSiswa($this->admin());
        $guru = $this->guruUser();

        $this->actingAs($guru, 'sanctum')
            ->getJson('/api/siswa')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->actingAs($guru, 'sanctum')
            ->getJson('/api/siswa/'.$created['data']['id'])
            ->assertStatus(200)
            ->assertJsonPath('data.nis', '0012345');
    }

    public function test_siswa_cannot_read_siswa(): void
    {
        $created = $this->createSiswa($this->admin());
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')->getJson('/api/siswa')->assertStatus(403);
        $this->actingAs($siswa, 'sanctum')->getJson('/api/siswa/'.$created['data']['id'])->assertStatus(403);
    }

    public function test_admin_can_create_siswa_with_username_equal_to_nis(): void
    {
        $admin = $this->admin();
        $jurusan = $this->jurusan();
        $gelombang = $this->gelombang();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test',
            'nis' => '0012345',
            'email' => 'siswa@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'jurusan_id' => $jurusan->id,
            'gelombang_id' => $gelombang->id,
            'jenis_kelamin' => 'L',
            'kelas' => 'XII TJKT 1',
            'telp' => '081234567890',
            'alamat' => 'Jl. Pemuda No. 1, Magelang',
            'nama_ortu' => 'Bapak Siswa',
            'telp_ortu' => '081298765432',
            'alamat_ortu' => 'Jl. Pemuda No. 1, Magelang',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nis', '0012345')
            ->assertJsonPath('data.user.username', '0012345')
            ->assertJsonPath('data.user.email', 'siswa@example.com')
            ->assertJsonPath('data.user.role_id', 3)
            ->assertJsonPath('data.jurusan.id', $jurusan->id)
            ->assertJsonPath('data.gelombang.id', $gelombang->id);

        $user = User::where('username', '0012345')->first();

        $this->assertNotNull($user);
        $this->assertSame(3, $user->role_id);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('siswa', [
            'user_id' => $user->id,
            'nis' => '0012345',
            'jenis_kelamin' => 'L',
            'kelas' => 'XII TJKT 1',
            'jurusan_id' => $jurusan->id,
            'gelombang_id' => $gelombang->id,
            'profil_confirmed_at' => null,
        ]);
    }

    public function test_create_siswa_rejects_username_different_from_nis(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test',
            'username' => 'username-lain',
            'nis' => '0012345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_create_siswa_duplicate_nis_fails(): void
    {
        $admin = $this->admin();
        $this->createSiswa($admin);

        $this->actingAs($admin, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test 2',
            'nis' => '0012345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('nis');
    }

    public function test_create_siswa_rejects_nis_used_as_username_by_another_user(): void
    {
        $admin = $this->admin();

        User::factory()->create(['role_id' => 2, 'username' => '0012345']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test',
            'nis' => '0012345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('nis');
    }

    public function test_create_siswa_duplicate_email_fails(): void
    {
        $admin = $this->admin();
        $this->createSiswa($admin, ['email' => 'sama@example.com']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test 2',
            'nis' => '0012346',
            'email' => 'sama@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_create_siswa_rejects_unknown_jurusan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test',
            'nis' => '0012345',
            'jurusan_id' => 999,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('jurusan_id');
    }

    public function test_guru_cannot_create_siswa(): void
    {
        $guru = $this->guruUser();

        $this->actingAs($guru, 'sanctum')->postJson('/api/siswa', [
            'name' => 'Siswa Test',
            'nis' => '0012345',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(403);
    }

    public function test_admin_can_update_siswa(): void
    {
        $admin = $this->admin();
        $created = $this->createSiswa($admin);
        $siswaId = $created['data']['id'];

        $this->actingAs($admin, 'sanctum')->putJson("/api/siswa/{$siswaId}", [
            'name' => 'Siswa Nama Baru',
            'kelas' => 'XII TJKT 2',
        ])->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Siswa Nama Baru')
            ->assertJsonPath('data.kelas', 'XII TJKT 2');

        $this->assertDatabaseHas('users', ['id' => $created['data']['user_id'], 'name' => 'Siswa Nama Baru']);
        $this->assertDatabaseHas('siswa', ['id' => $siswaId, 'kelas' => 'XII TJKT 2']);
    }

    public function test_update_nis_syncs_username(): void
    {
        $admin = $this->admin();
        $created = $this->createSiswa($admin);
        $siswaId = $created['data']['id'];

        $this->actingAs($admin, 'sanctum')->putJson("/api/siswa/{$siswaId}", [
            'nis' => '0012399',
        ])->assertStatus(200)->assertJsonPath('data.user.username', '0012399');

        $this->assertDatabaseHas('users', ['id' => $created['data']['user_id'], 'username' => '0012399']);
        $this->assertDatabaseHas('siswa', ['id' => $siswaId, 'nis' => '0012399']);
    }

    public function test_update_siswa_without_password_keeps_password(): void
    {
        $admin = $this->admin();
        $created = $this->createSiswa($admin);
        $siswaId = $created['data']['id'];
        $user = User::findOrFail($created['data']['user_id']);
        $passwordBefore = $user->password;

        $this->actingAs($admin, 'sanctum')->putJson("/api/siswa/{$siswaId}", [
            'name' => 'Nama Baru',
        ])->assertStatus(200);

        $this->assertSame($passwordBefore, $user->fresh()->password);
    }

    public function test_update_siswa_does_not_reset_profil_confirmed_at(): void
    {
        $admin = $this->admin();
        $created = $this->createSiswa($admin);
        $siswaId = $created['data']['id'];

        $siswa = Siswa::findOrFail($siswaId);
        $siswa->update(['profil_confirmed_at' => now()]);
        $confirmedAt = $siswa->fresh()->profil_confirmed_at;

        $this->actingAs($admin, 'sanctum')->putJson("/api/siswa/{$siswaId}", [
            'name' => 'Nama Baru',
        ])->assertStatus(200)->assertJsonPath('data.profil_terkonfirmasi', true);

        $this->assertTrue($confirmedAt->equalTo($siswa->fresh()->profil_confirmed_at));
    }

    public function test_update_siswa_duplicate_nis_fails(): void
    {
        $admin = $this->admin();
        $this->createSiswa($admin, ['nis' => '0012345']);
        $second = $this->createSiswa($admin, ['nis' => '0012346']);

        $this->actingAs($admin, 'sanctum')->putJson('/api/siswa/'.$second['data']['id'], [
            'nis' => '0012345',
        ])->assertStatus(422)->assertJsonValidationErrors('nis');
    }

    public function test_admin_can_delete_siswa_soft_deletes_and_keeps_user(): void
    {
        $admin = $this->admin();
        $created = $this->createSiswa($admin);
        $siswaId = $created['data']['id'];
        $userId = $created['data']['user_id'];

        $user = User::findOrFail($userId);
        $user->createToken('android-app');

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/siswa/{$siswaId}")->assertStatus(200);

        $this->assertSoftDeleted('siswa', ['id' => $siswaId]);
        $this->assertDatabaseHas('users', ['id' => $userId]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->actingAs($admin, 'sanctum')->getJson("/api/siswa/{$siswaId}")->assertStatus(404);
    }

    public function test_admin_can_bulk_upload_siswa(): void
    {
        $admin = $this->admin();
        $path = $this->makeXlsx([
            ['nis', 'nama', 'jenis_kelamin', 'kelas'],
            ['0012350', 'Siswa Import 1', 'L', 'XII TJKT 1'],
            ['0012351', 'Siswa Import 2', 'Perempuan', 'XII TJKT 2'],
        ]);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/siswa/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', 2)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseCount('siswa', 2);
        $this->assertDatabaseHas('users', ['username' => '0012350', 'role_id' => 3]);
        $this->assertTrue(Hash::check('password123', User::where('username', '0012350')->first()->password));
        $this->assertDatabaseHas('siswa', ['nis' => '0012351', 'jenis_kelamin' => 'P']);
    }

    public function test_bulk_upload_resolves_jurusan_and_gelombang(): void
    {
        $admin = $this->admin();
        $jurusan = $this->jurusan();
        $gelombang = $this->gelombang('Gelombang 2');

        $path = $this->makeXlsx([
            ['nis', 'nama', 'jurusan', 'gelombang'],
            ['0012352', 'Siswa Import', 'TJKT', 'Gelombang 2'],
        ]);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/siswa/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', 1)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseHas('siswa', [
            'nis' => '0012352',
            'jurusan_id' => $jurusan->id,
            'gelombang_id' => $gelombang->id,
        ]);
    }

    public function test_bulk_upload_reports_invalid_rows(): void
    {
        $admin = $this->admin();
        $this->createSiswa($admin, ['nis' => '0012353']);
        $this->jurusan();

        $path = $this->makeXlsx([
            ['nis', 'nama', 'jurusan'],
            ['0012354', 'Siswa Valid', 'TJKT'],
            ['0012355', '', 'TJKT'],
            ['0012353', 'Siswa Duplikat', 'TJKT'],
            ['0012356', 'Siswa Jurusan Salah', 'Jurusan Tidak Ada'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/siswa/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', 1)
            ->assertJsonPath('failed', 3)
            ->assertJsonCount(3, 'errors');

        $this->assertSame([3, 4, 5], array_column($response->json('errors'), 'row'));
        $this->assertDatabaseHas('siswa', ['nis' => '0012354']);
        $this->assertDatabaseMissing('siswa', ['nis' => '0012356']);
    }

    public function test_bulk_upload_rejects_non_excel_file(): void
    {
        $admin = $this->admin();
        $path = $this->makeFile('bukan spreadsheet', 'txt');

        $this->actingAs($admin, 'sanctum')
            ->post('/api/siswa/bulk-upload', [
                'file' => new UploadedFile($path, 'siswa.txt', 'text/plain', null, true),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_guru_cannot_bulk_upload_siswa(): void
    {
        $guru = $this->guruUser();
        $path = $this->makeXlsx([['nis', 'nama'], ['0012357', 'Siswa Import']]);

        $this->actingAs($guru, 'sanctum')
            ->post('/api/siswa/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_admin_can_filter_siswa(): void
    {
        $admin = $this->admin();
        $jurusanA = $this->jurusan('TJKT', 'Teknik Jaringan Komputer dan Telekomunikasi');
        $jurusanB = $this->jurusan('DKV', 'Desain Komunikasi Visual');

        $this->createSiswa($admin, ['name' => 'Siswa A', 'nis' => '0012360', 'jurusan_id' => $jurusanA->id]);
        $this->createSiswa($admin, ['name' => 'Siswa B', 'nis' => '0012361', 'jurusan_id' => $jurusanB->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/siswa?jurusan_id='.$jurusanA->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nis', '0012360');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/siswa?search=0012361')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.name', 'Siswa B');
    }

    public function test_show_missing_siswa_returns_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->getJson('/api/siswa/999')->assertStatus(404);
    }
}
