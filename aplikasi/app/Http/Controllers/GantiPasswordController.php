<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GantiPasswordController extends Controller
{
    public function edit(): View
    {
        return view('akun.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.current_password' => 'Kata sandi lama salah.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Ulangan kata sandi tidak sama.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Kata sandi berhasil diganti.');
    }
}
