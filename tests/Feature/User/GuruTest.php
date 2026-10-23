<?php

namespace Tests\Feature\User;

use App\Exceptions\InvalidFileTypeException;
use App\Models\Guru;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class GuruTest extends TestCase
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

    /**
     * Buat guru lewat API sebagai admin.
     *
     * @return array<string, mixed>
     */
    protected function createGuru(User $admin, string $nip = '198801012020011001'): array
    {
        return $this->actingAs($admin, 'sanctum')->postJson('/api/guru', [
            'name' => 'Guru Test',
            'nip' => $nip,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201)->json();
    }

    /**
     * Buat file .xlsx sementara dari array baris.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeXlsx(array $rows): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('guru_import_', true).'.xlsx';

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->tempFiles[] = $path;

        return $path;
    }

    private function makeFile(string $contents, string $extension): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('guru_import_', true).'.'.$extension;
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }

    private function xlsxUpload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'guru.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    public function test_guest_cannot_access_guru(): void
    {
        $this->getJson('/api/guru')->assertStatus(401);
    }

    public function test_admin_can_list_guru(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'sanctum')->getJson('/api/guru')->assertStatus(200);
    }

    public function test_siswa_cannot_read_guru(): void
    {
        $this->createGuru($this->admin());

        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')->getJson('/api/guru')->assertStatus(403);
        $this->actingAs($siswa, 'sanctum')->getJson('/api/guru/1')->assertStatus(403);
    }

    public function test_guru_can_view_detail_but_cannot_list(): void
    {
        $created = $this->createGuru($this->admin());
        $guruUser = $this->guruUser();

        $this->actingAs($guruUser, 'sanctum')
            ->getJson('/api/guru/'.$created['data']['id'])
            ->assertStatus(200)
            ->assertJsonPath('data.nip', '198801012020011001');

        $this->actingAs($guruUser, 'sanctum')->getJson('/api/guru')->assertStatus(403);
    }

    public function test_admin_can_create_guru_with_username_equal_to_nip(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/guru', [
            'name' => 'Guru Test',
            'nip' => '198801012020011001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nip', '198801012020011001')
            ->assertJsonPath('data.user.username', '198801012020011001');

        $user = User::where('username', '198801012020011001')->first();

        $this->assertNotNull($user);
        $this->assertSame(2, $user->role_id);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('guru', ['nip' => '198801012020011001', 'user_id' => $user->id]);
    }

    public function test_create_guru_rejects_username_different_from_nip(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/guru', [
            'name' => 'Guru Test',
            'username' => 'username-lain',
            'nip' => '198801012020011001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('username');
    }

    public function test_create_guru_duplicate_nip_fails(): void
    {
        $admin = $this->admin();
        $this->createGuru($admin);

        $this->actingAs($admin, 'sanctum')->postJson('/api/guru', [
            'name' => 'Guru Test 2',
            'nip' => '198801012020011001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('nip');
    }

    public function test_create_guru_rejects_nip_used_as_username_by_another_user(): void
    {
        $admin = $this->admin();

        User::factory()->create(['role_id' => 2, 'username' => '198801012020011001']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/guru', [
            'name' => 'Guru Test',
            'nip' => '198801012020011001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('nip');
    }

    public function test_guru_cannot_create_guru(): void
    {
        $guru = $this->guruUser();

        $this->actingAs($guru, 'sanctum')->postJson('/api/guru', [
            'name' => 'Guru Test',
            'nip' => '198801012020011002',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(403);
    }

    public function test_admin_can_update_guru(): void
    {
        $admin = $this->admin();
        $created = $this->createGuru($admin);
        $guruId = $created['data']['id'];

        $this->actingAs($admin, 'sanctum')->putJson("/api/guru/{$guruId}", [
            'name' => 'Guru Nama Baru',
        ])->assertStatus(200)->assertJsonPath('data.user.name', 'Guru Nama Baru');

        $this->assertDatabaseHas('users', ['id' => $created['data']['user_id'], 'name' => 'Guru Nama Baru']);
    }

    public function test_update_guru_without_password_keeps_password(): void
    {
        $admin = $this->admin();
        $created = $this->createGuru($admin);
        $guruId = $created['data']['id'];
        $user = User::findOrFail($created['data']['user_id']);
        $passwordBefore = $user->password;

        $this->actingAs($admin, 'sanctum')->putJson("/api/guru/{$guruId}", [
            'name' => 'Nama Baru',
        ])->assertStatus(200);

        $this->assertSame($passwordBefore, $user->fresh()->password);
    }

    public function test_update_nip_syncs_username(): void
    {
        $admin = $this->admin();
        $created = $this->createGuru($admin);
        $guruId = $created['data']['id'];

        $this->actingAs($admin, 'sanctum')->putJson("/api/guru/{$guruId}", [
            'nip' => '199001012020011009',
        ])->assertStatus(200)->assertJsonPath('data.user.username', '199001012020011009');

        $this->assertDatabaseHas('users', ['id' => $created['data']['user_id'], 'username' => '199001012020011009']);
        $this->assertDatabaseHas('guru', ['id' => $guruId, 'nip' => '199001012020011009']);
    }

    public function test_update_guru_duplicate_nip_fails(): void
    {
        $admin = $this->admin();
        $this->createGuru($admin, '198801012020011001');
        $second = $this->createGuru($admin, '198801012020011002');

        $this->actingAs($admin, 'sanctum')->putJson('/api/guru/'.$second['data']['id'], [
            'nip' => '198801012020011001',
        ])->assertStatus(422)->assertJsonValidationErrors('nip');
    }

    public function test_admin_can_delete_guru_and_user(): void
    {
        $admin = $this->admin();
        $created = $this->createGuru($admin);
        $guruId = $created['data']['id'];
        $userId = $created['data']['user_id'];

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/guru/{$guruId}")->assertStatus(200);

        $this->assertDatabaseMissing('guru', ['id' => $guruId]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_admin_can_bulk_upload_guru(): void
    {
        $admin = $this->admin();
        $path = $this->makeXlsx([
            ['nip', 'nama'],
            ['198801012020011001', 'Guru Import 1'],
            ['198801012020011002', 'Guru Import 2'],
        ]);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/guru/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', 2)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseCount('guru', 2);
        $this->assertDatabaseHas('users', ['username' => '198801012020011001', 'role_id' => 2]);
        $this->assertTrue(Hash::check('password123', User::where('username', '198801012020011001')->first()->password));
    }

    public function test_bulk_upload_reports_invalid_rows(): void
    {
        $admin = $this->admin();
        $this->createGuru($admin, '198801012020011001');

        $path = $this->makeXlsx([
            ['nip', 'nama'],
            ['198801012020011003', 'Guru Valid'],
            ['198801012020011004', ''],
            ['198801012020011001', 'Guru Duplikat'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/guru/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertJsonPath('success', 1)
            ->assertJsonPath('failed', 2)
            ->assertJsonCount(2, 'errors');

        $this->assertSame([3, 4], array_column($response->json('errors'), 'row'));
        $this->assertDatabaseHas('guru', ['nip' => '198801012020011003']);
        $this->assertDatabaseMissing('guru', ['nip' => '198801012020011004']);
    }

    public function test_bulk_upload_rejects_non_excel_file(): void
    {
        $admin = $this->admin();
        $path = $this->makeFile('bukan spreadsheet', 'txt');

        $this->actingAs($admin, 'sanctum')
            ->post('/api/guru/bulk-upload', [
                'file' => new UploadedFile($path, 'guru.txt', 'text/plain', null, true),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_invalid_file_type_exception_renders_422_with_code(): void
    {
        Route::get('/api/__test/invalid-file', function () {
            throw InvalidFileTypeException::forFile('rusak.xlsx');
        });

        $this->getJson('/api/__test/invalid-file')
            ->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_FILE_TYPE');
    }

    public function test_guru_cannot_bulk_upload(): void
    {
        $guru = $this->guruUser();
        $path = $this->makeXlsx([['nip', 'nama'], ['198801012020011005', 'Guru Import']]);

        $this->actingAs($guru, 'sanctum')
            ->post('/api/guru/bulk-upload', ['file' => $this->xlsxUpload($path)], ['Accept' => 'application/json'])
            ->assertStatus(403);
    }

    public function test_show_missing_guru_returns_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->getJson('/api/guru/999')->assertStatus(404);
    }
}
