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
