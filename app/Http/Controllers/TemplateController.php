<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    private const TEMPLATES = [
        'guru' => 'template-guru.xlsx',
        'siswa' => 'template-siswa.xlsx',
        'perusahaan' => 'template-perusahaan.xlsx',
        'kompetensi' => 'template-kompetensi.xlsx',
    ];

    public function download(string $type): BinaryFileResponse|JsonResponse
    {
        if (! array_key_exists($type, self::TEMPLATES)) {
            return response()->json(['message' => 'Template tidak ditemukan.'], 404);
        }

        $filename = self::TEMPLATES[$type];
        $path = public_path('templates/'.$filename);

        if (! File::exists($path)) {
            return response()->json(['message' => 'File template tidak ditemukan.'], 404);
        }

        return response()->download(
            $path,
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }
}
