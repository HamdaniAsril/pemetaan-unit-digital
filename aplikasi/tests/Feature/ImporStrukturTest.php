<?php

use App\Models\Answer;
use App\Models\Opd;
use App\Models\Unit;
use App\Pemetaan\ImporStruktur;
use Tests\Support\BuatXlsx;

function impor(array $baris)
{
    return app(ImporStruktur::class)->impor(BuatXlsx::struktur($baris));
}

test('impor membuat opd, unit, dan induk', function () {
    $hasil = impor(BuatXlsx::contoh());

    expect($hasil->opdBaru)->toBe(1)
        ->and($hasil->unitBaru)->toBe(5)
        ->and(Opd::first()->urutan)->toBe(1);

    $subbag = Unit::where('nama_impor', 'SUB BAGIAN UMUM')->first();
    expect($subbag->parent->nama_unit)->toBe('SEKRETARIAT')
        ->and($subbag->parent->parent->nama_unit)->toBe('DINAS CONTOH')
        ->and($subbag->level)->toBe(2)
        ->and($subbag->nama_jabatan)->toBe('KEPALA SUB BAGIAN UMUM')
        ->and(Unit::where('level', 0)->first()->parent_id)->toBeNull();
});

test('staf ahli tidak dinilai', function () {
    impor(BuatXlsx::contoh());

    expect(Unit::where('kategori', 'Staf Ahli')->first()->dinilai)->toBeFalse()
        ->and(Unit::where('dinilai', true)->count())->toBe(4);
});

test('impor ulang tidak membuat duplikat', function () {
    impor(BuatXlsx::contoh());
    $hasil = impor(BuatXlsx::contoh());

    expect(Unit::count())->toBe(5)
        ->and(Opd::count())->toBe(1)
        ->and($hasil->unitBaru)->toBe(0)
        ->and($hasil->opdBaru)->toBe(0)
        ->and($hasil->berbeda)->toBe([])
        ->and($hasil->hilang)->toBe([]);
});

test('impor tidak menimpa perubahan admin', function () {
    impor(BuatXlsx::contoh());
    $bidang = Unit::where('nama_impor', 'BIDANG A')->first();
    $bidang->update(['nama_unit' => 'BIDANG A BARU', 'dinilai' => false]);

    impor(BuatXlsx::contoh());

    $bidang->refresh();
    expect($bidang->nama_unit)->toBe('BIDANG A BARU')
        ->and($bidang->dinilai)->toBeFalse()
        ->and(Unit::count())->toBe(5);
});

test('impor tidak menyentuh jawaban', function () {
    impor(BuatXlsx::contoh());
    $unit = Unit::where('nama_impor', 'BIDANG A')->first();
    Answer::factory()->lengkap()->create(['unit_id' => $unit->id]);

    impor(BuatXlsx::contoh());

    expect(Answer::count())->toBe(1)
        ->and(Answer::first()->lengkap())->toBeTrue();
});

test('perbedaan dan unit hilang dilaporkan tanpa mengubah db', function () {
    impor(BuatXlsx::contoh());
    $baris = BuatXlsx::contoh();
    $baris[3][4] = 'Bidang Baru';      // kategori BIDANG A berubah
    unset($baris[4]);                  // STAF AHLI X hilang

    $hasil = impor(array_values($baris));

    expect($hasil->berbeda)->toHaveCount(1)
        ->and($hasil->berbeda[0])->toContain('BIDANG A')->toContain('kategori')
        ->and($hasil->hilang)->toHaveCount(1)
        ->and($hasil->hilang[0])->toContain('STAF AHLI X')
        ->and(Unit::where('nama_impor', 'BIDANG A')->first()->kategori)->toBe('Bidang')
        ->and(Unit::count())->toBe(5);
});

test('data asli terimpor sesuai angka spec', function () {
    app(ImporStruktur::class)->impor(database_path('data/struktur.xlsx'));

    expect(Opd::count())->toBe(49)
        ->and(Unit::count())->toBe(603)
        ->and(Unit::where('dinilai', false)->count())->toBe(3)
        ->and(Unit::where('level', 3)->count())->toBe(1)
        ->and(Unit::where('level', '>', 0)->whereNull('parent_id')->count())->toBe(0);
});

test('perintah artisan impor mencetak ringkasan', function () {
    $this->artisan('pemetaan:impor', ['path' => BuatXlsx::struktur(BuatXlsx::contoh())])
        ->expectsOutputToContain('OPD baru: 1')
        ->expectsOutputToContain('Unit baru: 5')
        ->assertSuccessful();
});
