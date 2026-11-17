<?php

use App\Models\Answer;
use App\Models\Opd;
use App\Models\Setting;
use App\Models\Unit;
use App\Pemetaan\Indikator;
use Illuminate\Database\QueryException;

test('answer per unit unik', function () {
    $unit = Unit::factory()->create();
    Answer::factory()->create(['unit_id' => $unit->id]);
    Answer::factory()->create(['unit_id' => $unit->id]);
})->throws(QueryException::class);

test('scope dinilai mengecualikan nonaktif dan tidak dinilai', function () {
    $opd = Opd::factory()->create();
    Unit::factory()->for($opd)->create();
    Unit::factory()->for($opd)->create(['aktif' => false]);
    Unit::factory()->for($opd)->create(['dinilai' => false]);

    expect(Unit::dinilai()->count())->toBe(1);
});

test('answer lengkap hanya bila ketiga indikator terisi', function () {
    $a = Answer::factory()->make(['mandat' => true, 'keluaran' => false, 'sumber_daya' => null]);
    expect($a->lengkap())->toBeFalse();

    $a->sumber_daya = false;
    expect($a->lengkap())->toBeTrue();
});

test('setting simpan dan ambil', function () {
    Setting::simpan('kontak_admin', 'WA 0812');

    expect(Setting::ambil('kontak_admin'))->toBe('WA 0812')
        ->and(Setting::ambil('lain', 'x'))->toBe('x');
});

test('indikator memakai teks spec', function () {
    expect(Indikator::KODE)->toBe(['mandat', 'keluaran', 'sumber_daya'])
        ->and(Indikator::label('mandat'))->toBe('Mandat Formal')
        ->and(Indikator::pertanyaan('mandat'))->toBe('Apakah terdapat mandat formal dari pimpinan atau peraturan tertulis yang mendasari unit ini sebagai unit digital?')
        ->and(Indikator::pertanyaan('keluaran'))->toBe('Apakah unit ini menghasilkan keluaran (output) digital sebagai produk atau layanan utamanya?')
        ->and(Indikator::pertanyaan('sumber_daya'))->toBe('Apakah unit ini memiliki alokasi sumber daya manusia atau anggaran khusus untuk pengelolaan teknologi informasi?');
});
