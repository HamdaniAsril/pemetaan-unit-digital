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
        // Hanya proxy di jaringan lokal/Docker (Nginx bersama, port mapping) yang dipercaya untuk X-Forwarded-*.
        $middleware->trustProxies(at: ['127.0.0.1', '172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16']);
        $middleware->redirectUsersTo(fn () => \App\Http\Controllers\Auth\LoginController::beranda());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['pesan' => 'Sesi berakhir, silakan masuk lagi.'], 401);
            }
        });
        // Token CSRF kedaluwarsa (sesi habis) diubah Laravel menjadi HttpException 419.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['pesan' => 'Sesi berakhir, silakan masuk lagi.'], 401);
            }

            return redirect('/login')->withErrors(['username' => 'Sesi berakhir, silakan masuk lagi.']);
        });
    })->create();
