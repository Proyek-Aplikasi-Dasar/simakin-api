<?php

use App\Exceptions\InvalidFileTypeException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureSiswaProfileConfirmed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'role' => EnsureRole::class,
            'profil.confirmed' => EnsureSiswaProfileConfirmed::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (InvalidFileTypeException $exception, Request $request) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode(),
            ], 422);
        });
    })->create();
