<?php

use App\Enums\Role;
use App\Models\Opd;
use App\Models\User;
use App\Pemetaan\AkunOpd;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Excel bentuk "USER PENDATAAN": No | OPD | Username | Password */
function berkasAkun(array $baris): string
{
    $book = new Spreadsheet;
    $book->getActiveSheet()->fromArray([['No', 'OPD', 'Username', 'Password'], ...$baris]);
    $path = tempnam(sys_get_temp_dir(), 'akun').'.xlsx';
    (new Xlsx($book))->save($path);

    return $path;
}

beforeEach(function () {
    $this->sosial = Opd::factory()->create(['nama' => 'DINAS SOSIAL']);
    $this->rsud = Opd::factory()->create(['nama' => 'RSUD dr. RM. PRATOMO BAGANSIAPIAPI']);
});

test('impor membuat akun OPD dengan username dan kata sandi dari berkas', function () {
    $hasil = app(AkunOpd::class)->imporDariExcel(berkasAkun([
        [1, 'DINAS SOSIAL', 'dinsos_rohil@sipresisi.rohilkab.go.id', 'sandi-bersama'],
        [2, 'RSUD DR. RM. PRATOMO BAGANSIAPIAPI', 'rsud_rohil@sipresisi.rohilkab.go.id', 'sandi-bersama'],
    ]));

    expect($hasil->dibuat)->toBe(2)->and($hasil->diperbarui)->toBe(0)->and($hasil->dilewati)->toBe([]);
    $u = $this->sosial->fresh()->user;
    expect($u->username)->toBe('dinsos_rohil@sipresisi.rohilkab.go.id')
        ->and($u->role)->toBe(Role::Opd)
        ->and(Hash::check('sandi-bersama', $u->password))->toBeTrue()
        ->and($this->rsud->fresh()->user->username)->toBe('rsud_rohil@sipresisi.rohilkab.go.id');
});

test('impor memperbarui akun OPD yang sudah ada, tanpa membuat akun ganda', function () {
    User::factory()->untukOpd($this->sosial)->create(['username' => 'dinsos']);

    $hasil = app(AkunOpd::class)->imporDariExcel(berkasAkun([
        [1, 'DINAS SOSIAL', 'dinsos_rohil@sipresisi.rohilkab.go.id', 'sandi-baru'],
    ]));

    expect($hasil->diperbarui)->toBe(1)
        ->and(User::where('opd_id', $this->sosial->id)->count())->toBe(1)
        ->and($this->sosial->fresh()->user->username)->toBe('dinsos_rohil@sipresisi.rohilkab.go.id')
        ->and(Hash::check('sandi-baru', $this->sosial->fresh()->user->password))->toBeTrue();
});

test('baris OPD yang tidak dikenal dan username milik akun lain dilewati dengan alasan', function () {
    User::factory()->admin()->create(['username' => 'dipakai@contoh.go.id']);

    $hasil = app(AkunOpd::class)->imporDariExcel(berkasAkun([
        [1, 'DINAS TIDAK ADA', 'x@contoh.go.id', 'p'],
        [2, 'DINAS SOSIAL', 'dipakai@contoh.go.id', 'p'],
        [3, 'RSUD dr. RM. PRATOMO BAGANSIAPIAPI', '', 'p'],
    ]));

    expect($hasil->dibuat)->toBe(0)
        ->and($hasil->dilewati)->toHaveCount(3)
        ->and(implode("\n", $hasil->dilewati))->toContain('DINAS TIDAK ADA')->toContain('dipakai@contoh.go.id')->toContain('kosong');
});

test('username dari berkas bisa dipakai login dan tetap huruf kecil', function () {
    app(AkunOpd::class)->imporDariExcel(berkasAkun([[1, 'DINAS SOSIAL', 'Dinsos_Rohil@SIPRESISI.go.id', 'sandi-bersama']]));

    $this->post('/login', ['username' => 'dinsos_rohil@sipresisi.go.id', 'password' => 'sandi-bersama'])->assertRedirect('/isi');
});

test('perintah artisan impor-akun mencetak ringkasan', function () {
    $this->artisan('pemetaan:impor-akun', ['path' => berkasAkun([[1, 'DINAS SOSIAL', 'dinsos_rohil@sipresisi.rohilkab.go.id', 'p']])])
        ->expectsOutputToContain('Akun dibuat: 1')
        ->assertSuccessful();
});

test('admin bisa mengimpor akun lewat tombol di panel OPD', function () {
    $this->actingAs(User::factory()->admin()->create());
    $berkas = \Illuminate\Http\UploadedFile::fake()->createWithContent('USER PENDATAAN.xlsx', file_get_contents(berkasAkun([
        [1, 'DINAS SOSIAL', 'dinsos_rohil@sipresisi.rohilkab.go.id', 'sandi-bersama'],
    ])));

    \Livewire\Livewire::test(\App\Filament\Resources\OpdResource\Pages\ListOpds::class)
        ->callAction('imporAkun', data: ['berkas' => $berkas])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($this->sosial->fresh()->user?->username)->toBe('dinsos_rohil@sipresisi.rohilkab.go.id');
});
