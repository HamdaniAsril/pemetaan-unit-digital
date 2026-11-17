<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'opd' => \App\Http\Middleware\HanyaOpd::class,
            'admin' => \App\Http\Middleware\HanyaAdmin::class,
        ]);
        // Di VPS aplikasi berada di belakang reverse proxy (SSH tunnel / Nginx bersama).
        $middleware->trustProxies(at: '*');
        $middleware->redirectUsersTo(fn () => \App\Http\Controllers\Auth\LoginController::beranda());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['pesan' => 'Sesi berakhir, silakan masuk lagi.'], 401);
            }
        });
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['pesan' => 'Sesi berakhir, silakan masuk lagi.'], 401);
            }
        });
    })->create();
