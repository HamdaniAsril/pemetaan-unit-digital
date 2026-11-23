<?php

use App\Enums\StatusOpd;
use App\Models\Answer;
use App\Models\Opd;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\User;
use App\Pemetaan\Format;

beforeEach(function () {
    $this->opd = Opd::factory()->create(['nama' => 'DINAS SATU']);
    $this->kepala = Unit::factory()->for($this->opd)->create(['level' => 0, 'nama_unit' => 'DINAS SATU', 'urutan' => 1]);
    $this->bidang = Unit::factory()->anakDari($this->kepala)->create(['nama_unit' => 'BIDANG PAJAK DAERAH II', 'urutan' => 2]);
    $this->user = User::factory()->untukOpd($this->opd)->create();

    $this->lain = Opd::factory()->create(['nama' => 'DINAS DUA']);
    $this->unitLain = Unit::factory()->for($this->lain)->create(['level' => 0, 'nama_unit' => 'UNIT RAHASIA LAIN']);
});

function jawab($test, $unitId, $indikator = 'mandat', $nilai = 'ya')
{
    return $test->postJson('/isi/jawaban', ['unit_id' => $unitId, 'indikator' => $indikator, 'nilai' => $nilai]);
}

test('kaki menu samping: nama akun dan tombol keluar, tanpa ganti sandi dan tanpa versi', function () {
    $this->user->update(['username' => 'dinas_satu@sipresisi.rohilkab.go.id']);

    $this->actingAs($this->user)->get('/isi')->assertOk()
        ->assertSee('dinas_satu@sipresisi.rohilkab.go.id')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertDontSee('/akun/password')
        ->assertDontSee('Versi ');
});

test('halaman isi menampilkan tab opd sendiri', function () {
    $this->actingAs($this->user)->get('/isi')->assertOk()
        ->assertSee('Bidang Pajak Daerah II')
        ->assertSee('Mandat Formal')
        ->assertDontSee('Unit Rahasia Lain');
});

test('admin tidak bisa membuka /isi', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/isi')->assertForbidden();
});

test('tamu diarahkan ke login', function () {
    $this->get('/isi')->assertRedirect('/login');
});

test('simpan jawaban berhasil mengembalikan progres', function () {
    $this->actingAs($this->user);
    jawab($this, $this->bidang->id)->assertOk()->assertJson(['progres' => ['terjawab' => 0, 'total' => 2], 'status' => 'mengisi']);
    jawab($this, $this->bidang->id, 'keluaran', 'tidak');
    jawab($this, $this->bidang->id, 'sumber_daya', 'ya')->assertJson(['progres' => ['terjawab' => 1, 'total' => 2]]);

    $a = $this->bidang->answer()->first();
    expect($a->mandat)->toBeTrue()->and($a->keluaran)->toBeFalse()->and($a->sumber_daya)->toBeTrue();
});

test('simpan jawaban unit opd lain ditolak 403', function () {
    $this->actingAs($this->user);
    jawab($this, $this->unitLain->id)->assertForbidden();
    expect(Answer::count())->toBe(0);
});

test('simpan jawaban unit tidak dinilai ditolak 403', function () {
    $this->bidang->update(['dinilai' => false]);
    $this->actingAs($this->user);
    jawab($this, $this->bidang->id)->assertForbidden();
});

test('nilai selain ya/tidak ditolak 422', function () {
    $this->actingAs($this->user);
    jawab($this, $this->bidang->id, 'mandat', 'mungkin')->assertUnprocessable();
    jawab($this, $this->bidang->id, 'lain', 'ya')->assertUnprocessable();
});

test('simpan jawaban saat terkirim ditolak 423', function () {
    $this->opd->update(['status' => StatusOpd::Terkirim]);
    $this->actingAs($this->user);
    jawab($this, $this->bidang->id)->assertStatus(423)->assertJson(['pesan' => 'Jawaban sudah dikirim dan terkunci.']);
});

test('jawaban tanpa sesi mengembalikan 401 JSON', function () {
    jawab($this, $this->bidang->id)->assertUnauthorized()->assertJsonPath('pesan', 'Sesi berakhir, silakan masuk lagi.');
});

test('kirim belum lengkap kembali dengan pesan jumlah unit', function () {
    $this->actingAs($this->user)->from('/isi')->post('/isi/kirim')
        ->assertRedirect('/isi')->assertSessionHasErrors(['kirim' => 'Masih ada 2 unit yang belum lengkap.']);
});

test('kirim lengkap mengunci dan halaman menampilkan banner terkirim', function () {
    foreach ([$this->kepala, $this->bidang] as $u) {
        Answer::factory()->lengkap()->create(['unit_id' => $u->id]);
    }
    $this->actingAs($this->user)->post('/isi/kirim')->assertRedirect('/isi');

    expect($this->opd->fresh()->status)->toBe(StatusOpd::Terkirim);
    $this->get('/isi')->assertSee('Jawaban sudah dikirim pada');
});

test('popup terkirim muncul sekali tepat setelah kirim, dengan tombol keluar', function () {
    foreach ([$this->kepala, $this->bidang] as $u) {
        Answer::factory()->lengkap()->create(['unit_id' => $u->id]);
    }
    $this->actingAs($this->user)->get('/isi')->assertDontSee('id="dialogTerkirim"', false);
    $this->post('/isi/kirim');

    $this->get('/isi')
        ->assertSee('id="dialogTerkirim"', false)
        ->assertSee('Anda sudah bisa keluar dari aplikasi.')
        ->assertSeeInOrder(['id="dialogTerkirim"', 'action="'.route('logout').'"'], false);
    $this->get('/isi')->assertDontSee('id="dialogTerkirim"', false)->assertSee('Jawaban sudah dikirim pada');
});

test('kontak admin tampil bila diisi', function () {
    $this->actingAs($this->user)->get('/isi')->assertDontSee('Hubungi admin:');
    Setting::simpan('kontak_admin', 'WA 0812-0000');
    $this->get('/isi')->assertSee('Unit tidak sesuai? Hubungi admin: WA 0812-0000');
});

test('format nama unit', function () {
    expect(Format::namaUnit('BIDANG PAJAK DAERAH II'))->toBe('Bidang Pajak Daerah II')
        ->and(Format::namaUnit('SUB BIDANG PENDAFTARAN PAJAK BUMI DAN BANGUNAN (PBB-P2)'))->toBe('Sub Bidang Pendaftaran Pajak Bumi dan Bangunan (PBB-P2)')
        ->and(Format::namaUnit('UPT PENDAPATAN WILAYAH IV'))->toBe('UPT Pendapatan Wilayah IV');
});

test('opd tanpa unit dinilai melihat keadaan kosong dan tidak bisa mengirim', function () {
    $this->kepala->update(['dinilai' => false]);
    $this->bidang->update(['aktif' => false]);

    $this->actingAs($this->user)->get('/isi')->assertOk()
        ->assertSee('Belum ada unit yang perlu dinilai')
        ->assertDontSee('id="petunjuk"', false);
    $this->post('/isi/kirim')->assertSessionHasErrors(['kirim' => 'Belum ada unit yang bisa dinilai. Hubungi admin.']);
    expect($this->opd->fresh()->status)->not->toBe(StatusOpd::Terkirim);
});

test('petunjuk tampil tanpa label draf dan memakai teks yang diubah admin', function () {
    $this->actingAs($this->user)->get('/isi')
        ->assertSee(\App\Pemetaan\Indikator::petunjuk('mandat'))
        ->assertDontSee('belum dikonfirmasi');

    Setting::simpan('petunjuk_mandat', 'Jawab Ya bila ada SK resmi.');

    $this->get('/isi')->assertSee('Jawab Ya bila ada SK resmi.');
});
