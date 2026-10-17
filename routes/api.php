<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $r) => $r->user()->load('role'));
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::post('/register', [RegisterController::class, 'register'])
        ->middleware('role:admin');

    Route::put('/user/password', [PasswordController::class, 'update']);
});
