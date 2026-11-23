<?php

use App\Models\Opd;
use Illuminate\Database\Migrations\Migration;

/**
 * Menambah unit yang juga sudah ada di database/data/struktur.xlsx ke database yang sudah berjalan.
 * Database kosong dilewati: unitnya ikut lewat impor struktur awal.
 */
return new class extends Migration
{
    private const UNIT = [
        'DINAS KESEHATAN' => 'FASILITAS KESEHATAN',
        'DINAS PENDIDIKAN DAN KEBUDAYAAN' => 'SATUAN PENDIDIKAN',
    ];

    public function up(): void
    {
        foreach (self::UNIT as $namaOpd => $namaUnit) {
            $opd = Opd::query()->where('nama', $namaOpd)->first();
            if (! $opd || $opd->units()->where('nama_impor', $namaUnit)->exists()) {
                continue;
            }
            $opd->units()->create([
                'parent_id' => $opd->units()->where('level', 0)->orderBy('urutan')->value('id'),
                'level' => 1,
                'nama_unit' => $namaUnit,
                'nama_impor' => $namaUnit,
                'kategori' => 'Unit Layanan',
                'urutan' => (int) $opd->units()->max('urutan') + 1,
            ]);
        }
    }

    public function down(): void
    {
        // Sengaja kosong: unit mungkin sudah punya jawaban.
    }
};
