<?php

namespace App\Support;

/**
 * Ringkasan hasil import baris per baris.
 *
 * Format array mengikuti 003-api-endpoints.md bagian 4:
 * { "message": "...", "success": 0, "failed": 0, "errors": [{ "row": 0, "error": "..." }] }
 */
class ImportResult
{
    /**
     * Jumlah baris yang berhasil disimpan.
     */
    private int $success = 0;

    /**
     * Jumlah baris yang gagal disimpan.
     */
    private int $failed = 0;

    /**
     * Daftar error per baris.
     *
     * @var array<int, array{row: int, error: string}>
     */
    private array $errors = [];

    /**
     * Tambah jumlah baris sukses.
     */
    public function addSuccess(int $total = 1): self
    {
        $this->success += $total;

        return $this;
    }

    /**
     * Catat satu baris gagal beserta pesan errornya.
     */
    public function addError(int $row, string $error): self
    {
        $this->failed++;
        $this->errors[] = [
            'row' => $row,
            'error' => $error,
        ];

        return $this;
    }

    public function success(): int
    {
        return $this->success;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    /**
     * @return array<int, array{row: int, error: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return $this->failed > 0;
    }

    public function message(): string
    {
        return "Import berhasil: {$this->success} data. Gagal: {$this->failed} data.";
    }

    /**
     * Payload siap pakai untuk response API.
     *
     * @return array{message: string, success: int, failed: int, errors: array<int, array{row: int, error: string}>}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message(),
            'success' => $this->success,
            'failed' => $this->failed,
            'errors' => $this->errors,
        ];
    }
}
