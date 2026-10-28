<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GelombangController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KompetensiController;
use App\Http\Controllers\LokasiPklController;
use App\Http\Controllers\PengajuanPklController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\SikapController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaktuAbsenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $r) => $r->user()->load('role'));
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

    // Onboarding siswa — wajib didaftarkan SEBELUM route siswa/{siswa}
    Route::middleware('role:siswa')->group(function () {
        Route::get('siswa/me', [SiswaController::class, 'me']);
        Route::put('siswa/me/profil', [SiswaController::class, 'updateProfil']);
        Route::put('siswa/me/konfirmasi', [SiswaController::class, 'konfirmasi']);
    });

    // Pengajuan PKL (siswa transaksional, gate profil)
    Route::middleware(['role:siswa', 'profil.confirmed'])->group(function () {
        Route::post('pengajuan-pkl', [PengajuanPklController::class, 'store']);
        Route::delete('pengajuan-pkl/{pengajuan_pkl}', [PengajuanPklController::class, 'destroy']);
    });

    Route::middleware('role:admin,guru')->get('siswa/{siswa}', [SiswaController::class, 'show']);

    // Lokasi PKL siswa (admin, guru, siswa miliknya)
    Route::middleware('role:admin,guru,siswa')->get('siswa/{siswa}/lokasi-pkl', [SiswaController::class, 'lokasiPkl']);

    // Siswa bimbingan guru
    Route::middleware('role:admin,guru')->get('guru/{guru}/siswa-bimbingan', [GuruController::class, 'siswaBimbingan']);

    // Pengajuan PKL (read)
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('pengajuan-pkl', [PengajuanPklController::class, 'index']);
        Route::get('pengajuan-pkl/{pengajuan_pkl}', [PengajuanPklController::class, 'show']);
    });

    // Lokasi PKL (read)
    Route::middleware('role:admin,guru')->group(function () {
        Route::get('lokasi-pkl', [LokasiPklController::class, 'index']);
        Route::get('lokasi-pkl/{lokasi_pkl}', [LokasiPklController::class, 'show']);
    });
    // Waktu absen tanpa show (4 endpoint sesuai 003-api-endpoints.md §8)
    Route::apiResource('waktu-absen', WaktuAbsenController::class)->only(['index']);

    // Pengajuan PKL (admin/guru write) — bulk-approve SEBELUM {pengajuan_pkl}
    Route::middleware('role:admin,guru')->group(function () {
        Route::put('pengajuan-pkl/bulk-approve', [PengajuanPklController::class, 'bulkApprove']);
        Route::put('pengajuan-pkl/{pengajuan_pkl}/approve', [PengajuanPklController::class, 'approve']);
        Route::put('pengajuan-pkl/{pengajuan_pkl}/reject', [PengajuanPklController::class, 'reject']);
    });

    // Lokasi PKL (write admin)
    Route::middleware('role:admin')->group(function () {
        Route::post('lokasi-pkl', [LokasiPklController::class, 'store']);
        Route::put('lokasi-pkl/{lokasi_pkl}', [LokasiPklController::class, 'update']);
        Route::put('lokasi-pkl/{lokasi_pkl}/status', [LokasiPklController::class, 'updateStatus']);
        Route::delete('lokasi-pkl/{lokasi_pkl}', [LokasiPklController::class, 'destroy']);
    });

    // Bulk upload (admin only) - carry-over Sprint 1/2
    Route::middleware('role:admin')->group(function () {
        Route::post('kompetensi/bulk-upload', [KompetensiController::class, 'bulkUpload']);
        Route::post('perusahaan/bulk-upload', [PerusahaanController::class, 'bulkUpload']);
    });

    // Dashboard (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('dashboard/admin', [DashboardController::class, 'admin']);
    });

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

        // User Management
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::put('users/{user}/password', [UserController::class, 'resetPassword']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);

        // Template Excel untuk bulk upload
        Route::get('templates/{type}', [TemplateController::class, 'download']);
    });
});
