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

class BulkUploadPklTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['nama' => 'admin']);
        Role::firstOrCreate(['id' => 2], ['nama' => 'guru']);
        Role::firstOrCreate(['id' => 3], ['nama' => 'siswa']);

        Jurusan::firstOrCreate(['kode' => 'TJKT'], ['nama' => 'Teknik Jaringan Komputer dan Telekomunikasi']);
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

    public function test_kompetensi_bulk_upload(): void
    {
        $admin = $this->admin();

        $path = $this->makeXlsx([
            ['jurusan', 'nama', 'kode'],
            ['TJKT', 'Kompetensi Bulk 1', 'KB1'],
            ['TJKT', 'Kompetensi Bulk 2', 'KB2'],
        ]);

        $file = new UploadedFile($path, 'kompetensi.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/kompetensi/bulk-upload', ['file' => $file])
            ->assertStatus(200);
    }

    public function test_perusahaan_bulk_upload(): void
    {
        $admin = $this->admin();

        $path = $this->makeXlsx([
            ['nama', 'alamat', 'kuota', 'jurusan'],
            ['PT Bulk Test', 'Jl. Bulk', 5, 'TJKT'],
        ]);

        $file = new UploadedFile($path, 'perusahaan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/perusahaan/bulk-upload', ['file' => $file])
            ->assertStatus(200);
    }
}
