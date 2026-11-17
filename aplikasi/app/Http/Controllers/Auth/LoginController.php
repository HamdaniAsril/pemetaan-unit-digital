<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAKS_GAGAL = 5;

    /** Batas per username saja, supaya ganti-ganti IP tidak membuka tebakan tanpa batas. */
    private const MAKS_GAGAL_USERNAME = 10;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
        ]);
        $kunci = Str::lower($data['username']).'|'.$request->ip();
        $kunciUsername = 'username|'.Str::lower($data['username']);

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_GAGAL) || RateLimiter::tooManyAttempts($kunciUsername, self::MAKS_GAGAL_USERNAME)) {
            $detik = max(RateLimiter::availableIn($kunci), RateLimiter::availableIn($kunciUsername));
            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.",
            ]);
        }

        if (! Auth::attempt(['username' => Str::lower($data['username']), 'password' => $data['password']])) {
            RateLimiter::hit($kunci, 60);
            RateLimiter::hit($kunciUsername, 600);
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi salah.']);
        }

        RateLimiter::clear($kunci);
        RateLimiter::clear($kunciUsername);
        $request->session()->regenerate();

        return redirect()->intended(self::beranda());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public static function beranda(): string
    {
        return Auth::user()?->isAdmin() ? '/admin' : '/isi';
    }
}
