<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\GelombangController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KompetensiController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\SikapController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\WaktuAbsenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user()->load('role'));
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('role:admin');

    Route::put('/user/password', [PasswordController::class, 'update']);

    // Read: semua role login
    Route::apiResource('jurusan', JurusanController::class)->only(['index', 'show']);
    Route::get('jurusan/{jurusan}/kompetensi', [JurusanController::class, 'kompetensi']);
    Route::apiResource('kompetensi', KompetensiController::class)->only(['index', 'show']);
    Route::apiResource('gelombang', GelombangController::class)->only(['index', 'show']);
    Route::apiResource('perusahaan', PerusahaanController::class)->only(['index', 'show']);
    Route::apiResource('sikap', SikapController::class)->only(['index', 'show']);
    // Detail guru boleh admin & guru; list hanya admin (003-api-endpoints.md §9)
    Route::middleware('role:admin,guru')->get('guru/{guru}', [GuruController::class, 'show']);
    // Siswa: list & detail boleh admin & guru (003-api-endpoints.md §10)
    Route::middleware('role:admin,guru')->get('siswa', [SiswaController::class, 'index']);
    Route::middleware('role:admin,guru')->get('siswa/{siswa}', [SiswaController::class, 'show']);
    // Waktu absen tanpa show (4 endpoint sesuai 003-api-endpoints.md §8)
    Route::apiResource('waktu-absen', WaktuAbsenController::class)->only(['index']);

    // Write: admin only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('jurusan', JurusanController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('kompetensi', KompetensiController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('gelombang', GelombangController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('perusahaan', PerusahaanController::class)->only(['store', 'update', 'destroy']);
        Route::post('perusahaan/{perusahaan}/jurusan', [PerusahaanController::class, 'attachJurusan']);
        Route::delete('perusahaan/{perusahaan}/jurusan/{jurusan}', [PerusahaanController::class, 'detachJurusan']);
        Route::apiResource('sikap', SikapController::class)->only(['store', 'update', 'destroy']);
        Route::get('guru', [GuruController::class, 'index']);
        Route::post('guru', [GuruController::class, 'store']);
        Route::post('guru/bulk-upload', [GuruController::class, 'bulkUpload']);
        Route::put('guru/{guru}', [GuruController::class, 'update']);
        Route::delete('guru/{guru}', [GuruController::class, 'destroy']);
        Route::post('siswa', [SiswaController::class, 'store']);
        Route::post('siswa/bulk-upload', [SiswaController::class, 'bulkUpload']);
        Route::put('siswa/{siswa}', [SiswaController::class, 'update']);
        Route::delete('siswa/{siswa}', [SiswaController::class, 'destroy']);
        Route::apiResource('waktu-absen', WaktuAbsenController::class)->only(['store', 'update', 'destroy']);
    });
});
