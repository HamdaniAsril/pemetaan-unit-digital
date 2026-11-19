<?php

use App\Models\Opd;
use App\Models\User;

beforeEach(function () {
    $this->opd = Opd::factory()->create();
    $this->userOpd = User::factory()->untukOpd($this->opd)->create(['username' => 'bapenda']);
    $this->admin = User::factory()->admin()->create(['username' => 'admin']);
});

test('login opd diarahkan ke /isi, admin ke /admin', function () {
    $this->post('/login', ['username' => 'bapenda', 'password' => 'password'])->assertRedirect('/isi');
    $this->assertAuthenticatedAs($this->userOpd);
    $this->post('/logout');

    $this->post('/login', ['username' => 'admin', 'password' => 'password'])->assertRedirect('/admin');
});

test('halaman login tampil dan beranda mengarahkan tamu ke login', function () {
    $this->get('/login')->assertOk()->assertSee('Pemetaan Unit Digital')->assertSee('Username');
    $this->get('/')->assertRedirect('/login');
});

test('login salah menampilkan pesan dan tidak masuk', function () {
    $this->from('/login')->post('/login', ['username' => 'bapenda', 'password' => 'salah'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['username' => 'Username atau kata sandi salah.']);
    $this->assertGuest();
});

test('login dibatasi setelah 5 kali gagal', function () {
    foreach (range(1, 5) as $_) {
        $this->post('/login', ['username' => 'bapenda', 'password' => 'salah']);
    }
    $this->post('/login', ['username' => 'bapenda', 'password' => 'password'])
        ->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('ganti password butuh password lama yang benar', function () {
    $this->actingAs($this->userOpd)
        ->post('/akun/password', ['password_lama' => 'salah', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1'])
        ->assertSessionHasErrors('password_lama');

    $this->actingAs($this->userOpd)
        ->post('/akun/password', ['password_lama' => 'password', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1'])
        ->assertSessionHasNoErrors();

    expect(\Illuminate\Support\Facades\Hash::check('barubaru1', $this->userOpd->fresh()->password))->toBeTrue();
});

test('ganti password minimal 8 karakter', function () {
    $this->actingAs($this->userOpd)
        ->post('/akun/password', ['password_lama' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
        ->assertSessionHasErrors('password');
});

test('login dibatasi per username walau IP berganti', function () {
    foreach (range(1, 10) as $i) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.9.0.{$i}"])
            ->post('/login', ['username' => 'bapenda', 'password' => 'salah']);
    }
    $this->withServerVariables(['REMOTE_ADDR' => '10.9.0.99'])
        ->post('/login', ['username' => 'bapenda', 'password' => 'password'])
        ->assertSessionHasErrors('username');
    $this->assertGuest();
});

test('header X-Forwarded-For dari luar proxy tidak dipercaya', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
        ->withHeader('X-Forwarded-For', '1.2.3.4')
        ->get('/login');

    expect(request()->ip())->toBe('203.0.113.5');
});

test('sesi kedaluwarsa (419) pada form biasa diarahkan ke login dengan pesan', function () {
    \Illuminate\Support\Facades\Route::post('/_uji419', fn () => abort(419))->middleware('web');
    $this->post('/_uji419')->assertRedirect('/login')->assertSessionHasErrors(['username' => 'Sesi berakhir, silakan masuk lagi.']);
    $this->postJson('/_uji419')->assertUnauthorized()->assertJsonPath('pesan', 'Sesi berakhir, silakan masuk lagi.');
});

test('buat admin menyimpan username huruf kecil', function () {
    $this->artisan('pemetaan:buat-admin', ['username' => 'AdminBaru'])
        ->expectsQuestion('Kata sandi (minimal 8 karakter)', 'rahasia123')
        ->expectsQuestion('Ulangi kata sandi', 'rahasia123')
        ->assertSuccessful();

    $this->post('/login', ['username' => 'adminbaru', 'password' => 'rahasia123'])->assertRedirect('/admin');
});

test('OPD yang sebelumnya membuka /admin tetap diarahkan ke /isi, bukan ke halaman terlarang', function () {
    $this->get('/admin')->assertRedirect('/login');   // menyimpan url.intended = /admin

    $this->post('/login', ['username' => 'bapenda', 'password' => 'password'])->assertRedirect('/isi');
});

test('admin yang sebelumnya membuka /isi diarahkan ke /admin', function () {
    $this->get('/isi')->assertRedirect('/login');

    $this->post('/login', ['username' => 'admin', 'password' => 'password'])->assertRedirect('/admin');
});

test('tujuan yang boleh untuk perannya tetap dihormati', function () {
    $this->get('/admin/opds')->assertRedirect('/login');

    $this->post('/login', ['username' => 'admin', 'password' => 'password'])->assertRedirect('/admin/opds');
});
