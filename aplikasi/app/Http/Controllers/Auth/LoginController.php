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

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_GAGAL)) {
            $detik = RateLimiter::availableIn($kunci);
            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.",
            ]);
        }

        if (! Auth::attempt(['username' => Str::lower($data['username']), 'password' => $data['password']])) {
            RateLimiter::hit($kunci, 60);
            throw ValidationException::withMessages(['username' => 'Username atau kata sandi salah.']);
        }

        RateLimiter::clear($kunci);
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
