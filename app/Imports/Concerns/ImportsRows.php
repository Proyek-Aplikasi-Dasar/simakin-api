<?php

namespace App\Imports\Concerns;

use App\Exceptions\InvalidFileTypeException;
use App\Support\ImportResult;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Kontrak dasar import Excel: baca baris, validasi per baris, kumpulkan error.
 *
 * Class pemakai wajib:
 * - implements Maatwebsite\Excel\Concerns\Import dan WithHeadingRow (baris pertama = header)
 * - menyediakan rules() dan saveRow()
 *
 * Contoh: App\Imports\JurusanImport.
 */
trait ImportsRows
{
    /**
     * Aturan validasi untuk satu baris.
     *
     * @return array<string, array<int, string>>
     */
    abstract protected function rules(): array;

    /**
     * Simpan satu baris yang sudah lolos validasi.
     *
     * @param  array<string, mixed>  $data
     */
    abstract protected function saveRow(array $data): void;

    /**
     * Baca file, validasi tiap baris, lalu simpan baris yang valid.
     */
    public function import(string $path): ImportResult
    {
        $rows = $this->readRows($path);

        $result = new ImportResult;
        $headingRow = method_exists($this, 'headingRow') ? (int) $this->headingRow() : 1;

        foreach ($rows as $index => $row) {
            // Nomor baris mengikuti Excel (header baris 1, data mulai baris 2).
            $rowNumber = $index + $headingRow + 1;
            $data = $this->prepareRow($row);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $validator = Validator::make($data, $this->rules(), $this->messages(), $this->attributes());

            if ($validator->fails()) {
                $result->addError($rowNumber, (string) $validator->errors()->first());

                continue;
            }

            try {
                $this->saveRow($validator->validated());
                $result->addSuccess();
            } catch (QueryException) {
                // Satu baris gagal tidak boleh menggagalkan seluruh import.
                $result->addError($rowNumber, 'Data gagal disimpan karena duplikat atau tidak valid.');
            }
        }

        return $result;
    }

    /**
     * Baca sheet pertama sebagai array of rows (heading sudah jadi key).
     *
     * @return array<int, array<array-key, mixed>>
     *
     * @throws InvalidFileTypeException
     */
    protected function readRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, $this->allowedExtensions(), true)) {
            throw InvalidFileTypeException::forFile($path);
        }

        try {
            return Excel::toArray($this, $path)[0] ?? [];
        } catch (Throwable $exception) {
            throw InvalidFileTypeException::forFile($path, $exception);
        }
    }

    /**
     * Ekstensi file yang diterima (bisa di-override per class import).
     *
     * @return array<int, string>
     */
    protected function allowedExtensions(): array
    {
        return ['xlsx', 'xls', 'csv'];
    }

    /**
     * Rapikan nilai sel: trim spasi dan ubah string kosong menjadi null.
     *
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    protected function prepareRow(array $row): array
    {
        return $this->normalizeRow($row);
    }

    /**
     * Implementasi normalisasi dasar, bisa dipakai ulang oleh class import yang override prepareRow().
     *
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $data = [];

        foreach ($row as $key => $value) {
            $data[$key] = is_string($value) ? trim($value) : $value;

            if ($data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /**
     * Baris dianggap kosong jika seluruh kolomnya null.
     *
     * @param  array<array-key, mixed>  $data
     */
    protected function isEmptyRow(array $data): bool
    {
        return collect($data)->every(fn ($value): bool => $value === null);
    }

    /**
     * Pesan validasi kustom per class import.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Nama atribut ramah manusia untuk pesan validasi.
     *
     * @return array<string, string>
     */
    protected function attributes(): array
    {
        return [];
    }
}
