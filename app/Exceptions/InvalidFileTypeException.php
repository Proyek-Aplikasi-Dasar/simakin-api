<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Dilempar ketika file import tidak bisa dibaca sebagai spreadsheet.
 *
 * Nanti dipetakan menjadi response 422 dengan code INVALID_FILE_TYPE
 * saat endpoint bulk-upload dibuat (Sprint 2).
 */
class InvalidFileTypeException extends RuntimeException
{
    public const CODE = 'INVALID_FILE_TYPE';

    public static function forFile(string $path, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Format file "%s" tidak valid. Unggah file Excel (.xlsx) atau CSV.', basename($path)),
            0,
            $previous,
        );
    }

    public function errorCode(): string
    {
        return self::CODE;
    }
}
