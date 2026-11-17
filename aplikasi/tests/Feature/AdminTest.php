<?php

use App\Enums\StatusOpd;
use App\Filament\Pages\Pengaturan;
use App\Filament\Resources\OpdResource\Pages\ListOpds;
use App\Filament\Resources\OpdResource\Pages\ViewOpd;
use App\Filament\Resources\UnitResource\Pages\EditUnit;
use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Models\Answer;
use App\Models\Opd;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->opd = Opd::factory()->create(['nama' => 'DINAS SATU']);
    $this->kepala = Unit::factory()->for($this->opd)->create(['level' => 0, 'nama_unit' => 'DINAS SATU']);
    $this->bidang = Unit::factory()->anakDari($this->kepala)->create(['nama_unit' => 'BIDANG X']);
    $this->userOpd = User::factory()->untukOpd($this->opd)->create(['username' => 'satu']);
});

test('opd tidak bisa membuka panel admin', function () {
    $this->actingAs($this->userOpd)->get('/admin')->assertForbidden();
});

test('tamu diarahkan ke login aplikasi', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('admin bisa membuka dasbor dan halaman unit', function () {
    $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('OPD terkirim');
    $this->get('/admin/opds')->assertOk();
    $this->get('/admin/units')->assertOk();
    $this->get('/admin/pengaturan')->assertOk();
});

test('daftar opd menampilkan status dan progres', function () {
    Answer::factory()->lengkap()->create(['unit_id' => $this->kepala->id]);
    $this->actingAs($this->admin);

    Livewire::test(ListOpds::class)
        ->assertCanSeeTableRecords([$this->opd])
        ->assertSee('1/2')
        ->assertSee('satu')
        ->assertSee('Belum mulai');
});

test('aksi buka kunci mengubah status ke mengisi', function () {
    $this->opd->update(['status' => StatusOpd::Terkirim, 'submitted_at' => now()]);
    $this->actingAs($this->admin);

    Livewire::test(ListOpds::class)->callTableAction('bukaKunci', $this->opd);

    expect($this->opd->fresh()->status)->toBe(StatusOpd::Mengisi);
});

test('aksi buka kunci tersembunyi bila belum terkirim', function () {
    $this->actingAs($this->admin);
    Livewire::test(ListOpds::class)->assertTableActionHidden('bukaKunci', $this->opd);
});

test('aksi reset kata sandi mengganti hash', function () {
    $lama = $this->userOpd->password;
    $this->actingAs($this->admin);

    Livewire::test(ListOpds::class)->callTableAction('resetPassword', $this->opd);

    expect($this->userOpd->fresh()->password)->not->toBe($lama)
        ->and(Hash::check('password', $this->userOpd->fresh()->password))->toBeFalse();
});

test('aksi buat akun hanya untuk opd tanpa akun', function () {
    $baru = Opd::factory()->create(['nama' => 'DINAS SOSIAL']);
    $this->actingAs($this->admin);

    Livewire::test(ListOpds::class)
        ->assertTableActionHidden('buatAkun', $this->opd)
        ->callTableAction('buatAkun', $baru);

    expect($baru->fresh()->user->username)->toBe('dinsos');
});

test('filter perlu dilengkapi hanya menampilkan opd terkirim yang kurang', function () {
    Answer::factory()->lengkap()->create(['unit_id' => $this->kepala->id]);
    $this->opd->update(['status' => StatusOpd::Terkirim]);   // bidang belum terjawab → perlu dilengkapi

    $lengkap = Opd::factory()->create(['status' => StatusOpd::Terkirim]);
    $u = Unit::factory()->for($lengkap)->create(['level' => 0]);
    Answer::factory()->lengkap()->create(['unit_id' => $u->id]);

    $this->actingAs($this->admin);
    Livewire::test(ListOpds::class)
        ->filterTable('status', 'perlu_dilengkapi')
        ->assertCanSeeTableRecords([$this->opd])
        ->assertCanNotSeeTableRecords([$lengkap]);
});

test('lihat jawaban menampilkan unit dan jawaban', function () {
    Answer::factory()->create(['unit_id' => $this->bidang->id, 'mandat' => true, 'keluaran' => false]);
    $this->actingAs($this->admin);

    Livewire::test(ViewOpd::class, ['record' => $this->opd->id])
        ->assertSee('Bidang X')->assertSee('Ya')->assertSee('Tidak');
});

test('unit dengan jawaban tidak bisa dihapus', function () {
    Answer::factory()->create(['unit_id' => $this->bidang->id]);
    $tanpa = Unit::factory()->anakDari($this->kepala)->create();
    $this->actingAs($this->admin);

    Livewire::test(ListUnits::class)
        ->assertTableActionHidden('delete', $this->bidang)
        ->assertTableActionVisible('delete', $tanpa);
});

test('ubah induk unit hanya bisa ke unit di opd yang sama', function () {
    $lain = Unit::factory()->create(['level' => 0]);
    $this->actingAs($this->admin);

    Livewire::test(EditUnit::class, ['record' => $this->bidang->id])
        ->fillForm(['parent_id' => $lain->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);

    Livewire::test(EditUnit::class, ['record' => $this->bidang->id])
        ->fillForm(['nama_unit' => 'BIDANG X BARU', 'dinilai' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->bidang->fresh()->nama_unit)->toBe('BIDANG X BARU')
        ->and($this->bidang->fresh()->dinilai)->toBeFalse();
});

test('pengaturan menyimpan kontak admin', function () {
    $this->actingAs($this->admin);
    Livewire::test(Pengaturan::class)
        ->fillForm(['kontak_admin' => 'WA 0812-1111'])
        ->call('simpan')
        ->assertHasNoFormErrors();

    expect(Setting::ambil('kontak_admin'))->toBe('WA 0812-1111');
});

test('induk tidak boleh turunan unit itu sendiri', function () {
    $anak = Unit::factory()->anakDari($this->bidang)->create();
    $this->actingAs($this->admin);

    Livewire::test(EditUnit::class, ['record' => $this->bidang->id])
        ->fillForm(['parent_id' => $anak->id])
        ->call('save')
        ->assertHasFormErrors(['parent_id']);
});

test('notifikasi kata sandi baru menampilkan username dan kata sandi di baris terpisah', function () {
    $this->actingAs($this->admin);
    Livewire::test(ListOpds::class)->callTableAction('resetPassword', $this->opd);

    $body = (string) collect(session('filament.notifications'))->last()['body'];
    expect($body)->toContain('Username: <strong>satu</strong><br>')->toContain('Kata sandi: <strong>');
});

test('pengaturan menyimpan petunjuk indikator dan menandainya dikonfirmasi', function () {
    $this->actingAs($this->admin);
    Livewire::test(Pengaturan::class)
        ->assertFormSet(['petunjuk_mandat' => \App\Pemetaan\Indikator::petunjuk('mandat')])
        ->fillForm(['petunjuk_keluaran' => 'Jawab Ya bila unit mengelola aplikasi.'])
        ->call('simpan')
        ->assertHasNoFormErrors();

    expect(Setting::ambil('petunjuk_keluaran'))->toBe('Jawab Ya bila unit mengelola aplikasi.')
        ->and(Setting::ambil('petunjuk_dikonfirmasi'))->toBe('1');
});
