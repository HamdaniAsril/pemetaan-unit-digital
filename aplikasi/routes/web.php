<?php

use App\Http\Controllers\Admin\UnduhController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\IsiController;
use App\Http\Controllers\WebhookGithubController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? LoginController::beranda() : '/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'opd'])->group(function () {
    Route::get('/isi', [IsiController::class, 'index'])->name('isi');
    Route::post('/isi/jawaban', [IsiController::class, 'jawaban'])->middleware('throttle:240,1');
    Route::post('/isi/kirim', [IsiController::class, 'kirim']);
});

Route::middleware(['auth', 'admin'])->prefix('admin/unduh')->group(function () {
    Route::get('/rekap', [UnduhController::class, 'rekap'])->name('unduh.rekap');
    Route::get('/backup', [UnduhController::class, 'backup'])->name('unduh.backup');
});

// Webhook GitHub (server CloudPanel). Tanpa sesi/CSRF: keasliannya dicek lewat tanda tangan HMAC.
Route::post('/hooks/github', WebhookGithubController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->middleware('throttle:30,1');
