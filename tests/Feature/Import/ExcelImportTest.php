<?php

namespace Tests\Feature\Import;

use App\Exceptions\InvalidFileTypeException;
use App\Imports\JurusanImport;
use App\Models\Jurusan;
use App\Support\ImportResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * File sementara yang dibuat selama test.
     *
     * @var array<int, string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    /**
     * Buat file .xlsx sementara dari array baris (tanpa fixture biner di repo).
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function makeXlsx(array $rows): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('import_', true).'.xlsx';

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->tempFiles[] = $path;

        return $path;
    }

    private function makeFile(string $contents, string $extension): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('import_', true).'.'.$extension;
        file_put_contents($path, $contents);

        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_semua_baris_valid_tersimpan(): void
    {
        $path = $this->makeXlsx([
            ['nama', 'kode'],
            ['Teknik Otomotif', 'TKR'],
            ['Desain Komunikasi Visual', 'DKV'],
        ]);

        $result = (new JurusanImport)->import($path);

        $this->assertSame(2, $result->success());
        $this->assertSame(0, $result->failed());
        $this->assertSame([], $result->errors());
        $this->assertFalse($result->hasErrors());
        $this->assertDatabaseCount('jurusan', 2);
        $this->assertDatabaseHas('jurusan', ['nama' => 'Teknik Otomotif', 'kode' => 'TKR']);
    }

    public function test_baris_header_tidak_ikut_diimport(): void
    {
        $path = $this->makeXlsx([
            ['nama', 'kode'],
            ['Teknik Elektronika', 'TEL'],
        ]);

        (new JurusanImport)->import($path);

        $this->assertSame(1, Jurusan::count());
        $this->assertDatabaseMissing('jurusan', ['nama' => 'nama']);
    }

    public function test_campuran_baris_valid_dan_invalid(): void
    {
        $path = $this->makeXlsx([
            ['nama', 'kode'],
            ['Teknik Pemesinan', 'TP'],
            ['', 'KOSONG'],
            [str_repeat('A', 101), 'PANJANG'],
            ['Teknik Pengelasan', 'WEL'],
        ]);

        $result = (new JurusanImport)->import($path);

        $this->assertSame(2, $result->success());
        $this->assertSame(2, $result->failed());
        $this->assertSame([
            ['row' => 3, 'error' => 'Nama jurusan wajib diisi.'],
            ['row' => 4, 'error' => 'Nama jurusan maksimal 100 karakter.'],
        ], $result->errors());
        $this->assertDatabaseCount('jurusan', 2);
        $this->assertDatabaseMissing('jurusan', ['kode' => 'KOSONG']);
        $this->assertDatabaseMissing('jurusan', ['kode' => 'PANJANG']);
    }

    public function test_baris_kosong_diabaikan_tanpa_dihitung_gagal(): void
    {
        $path = $this->makeXlsx([
            ['nama', 'kode'],
            ['Teknik Instalasi Tenaga Listrik', 'TITL'],
            ['', ''],
            ['Teknik Jaringan Komputer dan Telekomunikasi', 'TJKT'],
        ]);

        $result = (new JurusanImport)->import($path);

        $this->assertSame(2, $result->success());
        $this->assertSame(0, $result->failed());
        $this->assertDatabaseCount('jurusan', 2);
    }

    public function test_file_hanya_header_tidak_menghasilkan_baris(): void
    {
        $path = $this->makeXlsx([['nama', 'kode']]);

        $result = (new JurusanImport)->import($path);

        $this->assertSame(0, $result->success());
        $this->assertSame(0, $result->failed());
        $this->assertDatabaseCount('jurusan', 0);
    }

    public function test_nilai_sel_di_trim_sebelum_disimpan(): void
    {
        $path = $this->makeXlsx([
            ['nama', 'kode'],
            ['  Teknik Konstruksi Bangunan  ', '  TKB  '],
        ]);

        (new JurusanImport)->import($path);

        $this->assertDatabaseHas('jurusan', ['nama' => 'Teknik Konstruksi Bangunan', 'kode' => 'TKB']);
    }

    public function test_file_csv_dapat_diimport(): void
    {
        $path = $this->makeFile("nama,kode\nTeknik Pendingin dan Tata Udara,TPTU\n", 'csv');

        $result = (new JurusanImport)->import($path);

        $this->assertSame(1, $result->success());
        $this->assertSame(0, $result->failed());
        $this->assertDatabaseHas('jurusan', ['nama' => 'Teknik Pendingin dan Tata Udara']);
    }

    public function test_file_rusak_menghasilkan_invalid_file_type(): void
    {
        $path = $this->makeFile('ini bukan file spreadsheet sama sekali', 'xlsx');

        try {
            (new JurusanImport)->import($path);
            $this->fail('InvalidFileTypeException seharusnya dilempar.');
        } catch (InvalidFileTypeException $exception) {
            $this->assertSame('INVALID_FILE_TYPE', $exception->errorCode());
            $this->assertStringContainsString('tidak valid', $exception->getMessage());
            $this->assertNotNull($exception->getPrevious());
        }
    }

    public function test_ekstensi_tidak_dikenal_menghasilkan_invalid_file_type(): void
    {
        $path = $this->makeFile('nama,kode', 'txt');

        $this->expectException(InvalidFileTypeException::class);

        (new JurusanImport)->import($path);
    }

    public function test_import_result_menghasilkan_format_response_api(): void
    {
        $result = new ImportResult;
        $result->addSuccess(15);
        $result->addSuccess();
        $result->addError(3, 'Nama jurusan wajib diisi.');
        $result->addError(8, 'Kode jurusan maksimal 20 karakter.');

        $this->assertTrue($result->hasErrors());
        $this->assertSame([
            'message' => 'Import berhasil: 16 data. Gagal: 2 data.',
            'success' => 16,
            'failed' => 2,
            'errors' => [
                ['row' => 3, 'error' => 'Nama jurusan wajib diisi.'],
                ['row' => 8, 'error' => 'Kode jurusan maksimal 20 karakter.'],
            ],
        ], $result->toArray());
    }
}
