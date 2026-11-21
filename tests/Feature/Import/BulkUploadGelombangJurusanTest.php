<?php

namespace Tests\Feature\Import;

use App\Models\Jurusan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BulkUploadGelombangJurusanTest extends TestCase
{
    use RefreshDatabase;

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

    protected function siswaUser(): User
    {
        return User::factory()->create(['role_id' => 3, 'username' => 'siswa_test']);
    }

    private function makeXlsx(array $rows): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('bulk_', true).'.xlsx';
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_jurusan_bulk_upload(): void
    {
        $admin = $this->admin();

        $path = $this->makeXlsx([
            ['nama', 'kode', 'bidang_keahlian'],
            ['Jurusan Bulk 1', 'JB1', 'Bidang 1'],
            ['Jurusan Bulk 2', 'JB2', 'Bidang 2'],
        ]);

        $file = new UploadedFile($path, 'jurusan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/jurusan/bulk-upload', ['file' => $file])
            ->assertStatus(200);
    }

    public function test_gelombang_bulk_upload(): void
    {
        $admin = $this->admin();

        $path = $this->makeXlsx([
            ['tahun_ajaran', 'nama', 'daftar_mulai', 'daftar_selesai', 'praktik_mulai', 'praktik_selesai'],
            ['2026/2027', 'Gelombang Bulk 1', '2026-11-01', '2026-11-30', '2027-01-05', '2027-06-30'],
        ]);

        $file = new UploadedFile($path, 'gelombang.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/gelombang/bulk-upload', ['file' => $file])
            ->assertStatus(200);
    }

    public function test_jurusan_export_admin(): void
    {
        $admin = $this->admin();
        Jurusan::firstOrCreate(['kode' => 'TJKT'], ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi']);

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/jurusan/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_gelombang_export_admin(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/gelombang/export');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }

    public function test_jurusan_export_siswa_forbidden(): void
    {
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->get('/api/jurusan/export')
            ->assertStatus(403);
    }

    public function test_gelombang_export_siswa_forbidden(): void
    {
        $siswa = $this->siswaUser();

        $this->actingAs($siswa, 'sanctum')
            ->get('/api/gelombang/export')
            ->assertStatus(403);
    }
}
