<?php

use App\Models\User;
use App\Pemetaan\Versi;
use Illuminate\Support\Facades\File;

afterEach(fn () => File::delete(base_path('versi.txt')));

test('versi dibaca dari versi.txt yang ditulis skrip deploy', function () {
    File::put(base_path('versi.txt'), "a889167 2026-11-20T18:40:25+07:00\n");

    expect(Versi::teks())->toBe('Versi a889167 · 20 Nov 2026 18:40');
});

test('tanpa versi.txt (pengembangan lokal) tetap aman', function () {
    File::delete(base_path('versi.txt'));

    expect(Versi::teks())->toBe('Versi pengembangan');
});

test('versi tampil di panel admin', function () {
    File::put(base_path('versi.txt'), "b5ab2f3 2026-11-22T10:00:00+07:00\n");

    $this->actingAs(User::factory()->admin()->create(['name' => 'admin', 'username' => 'admin']))->get('/admin')
        ->assertOk()
        // Urutan di kaki menu samping: versi, lalu nama akun, lalu tombol keluar.
        ->assertSeeInOrder(['kaki-admin', 'Versi b5ab2f3', 'admin', 'Administrator', 'action="'.route('logout').'"'], false);
});
