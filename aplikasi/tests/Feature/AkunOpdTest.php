<?php

use App\Enums\Role;
use App\Models\Opd;
use App\Models\User;
use App\Pemetaan\AkunOpd;
use Illuminate\Support\Facades\Hash;

test('buat akun hanya untuk opd tanpa akun', function () {
    $opds = Opd::factory()->count(3)->create();
    User::factory()->untukOpd($opds[0])->create();

    $akun = app(AkunOpd::class)->buatAkun();

    expect($akun)->toHaveCount(2)
        ->and(User::where('role', Role::Opd)->count())->toBe(3)
        ->and(app(AkunOpd::class)->buatAkun())->toHaveCount(0);
});

test('username unik dan sesuai aturan', function () {
    $svc = app(AkunOpd::class);

    expect($svc->username(Opd::factory()->create(['nama' => 'KECAMATAN BANGKO'])))->toBe('kec-bangko')
        ->and($svc->username(Opd::factory()->create(['nama' => 'KECAMATAN BANGKO PUSAKO'])))->toBe('kec-bangko-pusako')
        ->and($svc->username(Opd::factory()->create(['nama' => 'BADAN PENDAPATAN DAERAH'])))->toBe('bapenda');

    $a = Opd::factory()->create(['nama' => 'DINAS X Y', 'urutan' => 1]);
    $b = Opd::factory()->create(['nama' => 'DINAS X, Y', 'urutan' => 2]);
    $hasil = $svc->buatAkun()->pluck('username', 'opd');

    expect($hasil['DINAS X Y'])->toBe('dinas-x-y')
        ->and($hasil['DINAS X, Y'])->toBe('dinas-x-y-2');
});

test('semua opd di data asli punya singkatan atau pola kecamatan', function () {
    $this->artisan('pemetaan:impor')->assertSuccessful();
    $akun = app(AkunOpd::class)->buatAkun();

    expect($akun)->toHaveCount(49)
        ->and($akun->pluck('username')->unique())->toHaveCount(49)
        ->and($akun->pluck('username')->every(fn ($u) => strlen($u) <= 30 && preg_match('/^[a-z0-9-]+$/', $u)))->toBeTrue();
});

test('password tersimpan hash dan bisa dipakai login', function () {
    Opd::factory()->create(['nama' => 'DINAS SOSIAL']);
    $akun = app(AkunOpd::class)->buatAkun()->first();
    $user = User::where('username', $akun['username'])->first();

    expect($user->password)->not->toBe($akun['password'])
        ->and(Hash::check($akun['password'], $user->password))->toBeTrue()
        ->and($akun['password'])->toHaveLength(10)
        ->and($akun['password'])->not->toMatch('/[0Oo1lI]/');
});

test('reset password mengganti hash dan password lama tidak berlaku', function () {
    $user = User::factory()->untukOpd(Opd::factory()->create())->create();
    $baru = app(AkunOpd::class)->resetPassword($user);

    $user->refresh();
    expect(Hash::check($baru, $user->password))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeFalse();
});

test('perintah buat-akun menulis excel', function () {
    Opd::factory()->create(['nama' => 'DINAS SOSIAL']);
    $path = sys_get_temp_dir().'/akun-test-'.uniqid().'.xlsx';

    $this->artisan('pemetaan:buat-akun', ['--output' => $path])->assertSuccessful();

    expect(file_exists($path))->toBeTrue();
    $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->toArray();
    expect($sheet[0])->toBe(['OPD', 'Username', 'Kata Sandi Awal'])
        ->and($sheet[1][1])->toBe('dinsos');
});
