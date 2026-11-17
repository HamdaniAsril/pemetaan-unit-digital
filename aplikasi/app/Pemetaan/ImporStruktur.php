<?php

namespace App\Pemetaan;

use App\Models\Opd;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Impor struktur dari Excel hasil scripts/buat_struktur_opd.py.
 * Hanya membuat yang belum ada; data yang sudah ada tidak pernah ditimpa (spec bagian 6).
 */
final class ImporStruktur
{
    private const SHEET = 'Struktur per OPD';

    private const KOLOM = [
        'no' => 'No',
        'opd' => 'Nama OPD',
        'level' => 'Level',
        'jabatan' => 'Nama Jabatan',
        'unit' => 'Unit Organisasi',
        'kategori' => 'Kategori',
        'eselon' => 'Eselon',
        'atasan' => 'Atasan Langsung',
    ];

    private const TIDAK_DINILAI = ['Staf Ahli'];

    public function impor(string $path): HasilImpor
    {
        $perOpd = $this->baca($path)->groupBy('opd');
        $hasil = new HasilImpor;

        DB::transaction(function () use ($perOpd, $hasil) {
            foreach ($perOpd as $namaOpd => $baris) {
                $this->imporOpd((string) $namaOpd, $baris->values(), $hasil);
            }
            $this->laporHilang($perOpd, $hasil);
        });

        return $hasil;
    }

    private function imporOpd(string $namaOpd, Collection $baris, HasilImpor $hasil): void
    {
        $opd = Opd::query()->where('nama', $namaOpd)->first();
        if (! $opd) {
            $opd = Opd::query()->create(['nama' => $namaOpd, 'urutan' => (int) $baris[0]['no']]);
            $hasil->opdBaru++;
        }

        $ada = $opd->units()->whereNotNull('nama_impor')->get()->keyBy('nama_impor');
        $baru = [];

        foreach ($baris as $i => $b) {
            $unit = $ada->get($b['unit']);
            if ($unit) {
                $this->bandingkan($opd, $unit, $b, $ada, $hasil);

                continue;
            }
            $unit = $opd->units()->create([
                'level' => (int) $b['level'],
                'nama_unit' => $b['unit'],
                'nama_impor' => $b['unit'],
                'nama_jabatan' => $b['jabatan'],
                'kategori' => $b['kategori'],
                'eselon' => $b['eselon'],
                'urutan' => $i + 1,
                'dinilai' => ! in_array($b['kategori'], self::TIDAK_DINILAI, true),
            ]);
            $ada->put($b['unit'], $unit);
            $baru[] = [$unit, $b['atasan']];
            $hasil->unitBaru++;
        }

        // Induk di-resolve setelah semua unit OPD ada, karena atasan bisa muncul setelah bawahannya.
        foreach ($baru as [$unit, $atasan]) {
            $induk = $ada->get($atasan);
            if ($induk && $induk->isNot($unit)) {
                $unit->update(['parent_id' => $induk->id]);
            }
        }
    }

    private function bandingkan(Opd $opd, Unit $unit, array $b, Collection $ada, HasilImpor $hasil): void
    {
        $indukDb = $unit->parent_id ? $ada->firstWhere('id', $unit->parent_id)?->nama_impor : null;
        $indukFile = $ada->has($b['atasan']) ? $b['atasan'] : null;

        $pasangan = [
            'nama_jabatan' => [$unit->nama_jabatan, $b['jabatan']],
            'kategori' => [$unit->kategori, $b['kategori']],
            'eselon' => [$unit->eselon, $b['eselon']],
            'level' => [(string) $unit->level, (string) $b['level']],
            'induk' => [$indukDb, $indukFile],
        ];

        foreach ($pasangan as $atribut => [$db, $file]) {
            if ((string) $db !== (string) $file) {
                $hasil->berbeda[] = "{$opd->nama} / {$unit->nama_impor}: {$atribut} '{$db}' → '{$file}'";
            }
        }
    }

    private function laporHilang(Collection $perOpd, HasilImpor $hasil): void
    {
        $diFile = $perOpd->map(fn (Collection $b) => $b->pluck('unit')->flip());

        Unit::query()->with('opd')->whereNotNull('nama_impor')->orderBy('opd_id')->orderBy('urutan')
            ->each(function (Unit $unit) use ($diFile, $hasil) {
                if (! $diFile->get($unit->opd->nama)?->has($unit->nama_impor)) {
                    $hasil->hilang[] = "{$unit->opd->nama} / {$unit->nama_impor}";
                }
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function baca(string $path): Collection
    {
        if (! is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([self::SHEET]);
        $sheet = $reader->load($path)->getSheetByName(self::SHEET)
            ?? throw new RuntimeException('Sheet "'.self::SHEET.'" tidak ada di file.');

        $rows = $sheet->toArray(null, true, false, false);
        $header = array_map(fn ($h) => trim((string) $h), array_shift($rows));
        $indeks = [];
        foreach (self::KOLOM as $kunci => $judul) {
            $pos = array_search($judul, $header, true);
            if ($pos === false) {
                throw new RuntimeException("Kolom \"{$judul}\" tidak ada di sheet.");
            }
            $indeks[$kunci] = $pos;
        }

        return collect($rows)
            ->filter(fn ($r) => trim((string) ($r[$indeks['opd']] ?? '')) !== '' && trim((string) ($r[$indeks['unit']] ?? '')) !== '')
            ->map(function ($r) use ($indeks) {
                $v = fn ($k) => ($x = trim((string) ($r[$indeks[$k]] ?? ''))) === '' ? null : $x;

                return [
                    'no' => $v('no'), 'opd' => $v('opd'), 'level' => (int) $v('level'),
                    'jabatan' => $v('jabatan'), 'unit' => $v('unit'), 'kategori' => $v('kategori'),
                    'eselon' => $v('eselon'), 'atasan' => $v('atasan'),
                ];
            })
            ->values();
    }
}
