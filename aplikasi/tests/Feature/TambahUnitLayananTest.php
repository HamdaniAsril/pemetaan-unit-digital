<?php

use App\Models\Opd;
use App\Models\Unit;
use App\Pemetaan\ImporStruktur;

function jalankanMigrasiUnitLayanan(): void
{
    (require database_path('migrations/2026_11_23_000001_tambah_unit_layanan.php'))->up();
}

test('struktur asli memuat fasilitas kesehatan dan satuan pendidikan di bawah dinasnya', function () {
    app(ImporStruktur::class)->impor(database_path('data/struktur.xlsx'));

    foreach (['DINAS KESEHATAN' => 'FASILITAS KESEHATAN', 'DINAS PENDIDIKAN DAN KEBUDAYAAN' => 'SATUAN PENDIDIKAN'] as $opd => $unit) {
        $u = Unit::where('nama_impor', $unit)->sole();
        expect($u->opd->nama)->toBe($opd)
            ->and($u->level)->toBe(1)
            ->and($u->parent->level)->toBe(0)
            ->and($u->dinilai)->toBeTrue()
            ->and($u->aktif)->toBeTrue();
    }
});

test('migrasi menambah unit ke database yang sudah berjalan, sekali saja', function () {
    $opd = Opd::create(['nama' => 'DINAS KESEHATAN', 'urutan' => 29]);
    $kepala = $opd->units()->create(['level' => 0, 'nama_unit' => 'DINAS KESEHATAN', 'nama_impor' => 'DINAS KESEHATAN', 'urutan' => 1]);
    $opd->units()->create(['level' => 1, 'parent_id' => $kepala->id, 'nama_unit' => 'UPT X', 'nama_impor' => 'UPT X', 'urutan' => 9]);

    jalankanMigrasiUnitLayanan();
    jalankanMigrasiUnitLayanan();

    $u = Unit::where('nama_impor', 'FASILITAS KESEHATAN')->sole();
    expect($u->parent_id)->toBe($kepala->id)
        ->and($u->level)->toBe(1)
        ->and($u->urutan)->toBe(10)
        ->and($u->kategori)->toBe('Unit Layanan')
        ->and(Unit::where('nama_impor', 'SATUAN PENDIDIKAN')->exists())->toBeFalse();
});

test('migrasi tidak menyentuh database kosong', function () {
    jalankanMigrasiUnitLayanan();

    expect(Unit::count())->toBe(0);
});
