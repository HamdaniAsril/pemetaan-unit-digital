<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GantiPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? LoginController::beranda() : '/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/akun/password', [GantiPasswordController::class, 'edit'])->name('akun.password');
    Route::post('/akun/password', [GantiPasswordController::class, 'update']);
});
