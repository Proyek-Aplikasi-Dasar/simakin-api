<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KompetensiController;
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

    // Write: admin only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('jurusan', JurusanController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('kompetensi', KompetensiController::class)->only(['store', 'update', 'destroy']);
    });
});
