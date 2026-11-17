<?php

namespace App\Exports;

use App\Models\Opd;
use App\Models\Unit;
use App\Pemetaan\Indikator;
use App\Pemetaan\Penilaian;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Rekap Excel seluruh kabupaten (spec bagian 6). */
final class RekapExport
{
    public function __construct(private Penilaian $penilaian) {}

    public function simpan(string $path): void
    {
        $ringkasan = [['No', 'OPD', 'Status', 'Unit Dinilai', 'Unit Lengkap', 'Tanggal Kirim',
            ...array_map(fn ($k) => 'Ya '.Indikator::label($k), Indikator::KODE)]];
        $perUnit = [['OPD', 'Level', 'Unit Organisasi', 'Jabatan', 'Kategori', 'Eselon', 'Induk',
            ...array_map(fn ($k) => Indikator::label($k), Indikator::KODE), 'Jumlah Ya']];

        foreach (Opd::query()->orderBy('urutan')->orderBy('id')->get() as $opd) {
            $units = $this->penilaian->tabs($opd)->flatMap->units();
            $indukNama = $opd->units()->pluck('nama_unit', 'id');
            $ya = array_fill_keys(Indikator::KODE, 0);

            foreach ($units as $u) {
                $jawaban = [];
                foreach (Indikator::KODE as $k) {
                    $v = $u->answer?->{$k};
                    $jawaban[] = $v === null ? null : ($v ? 'Ya' : 'Tidak');
                    $ya[$k] += $v === true ? 1 : 0;
                }
                $perUnit[] = [
                    $opd->nama, $u->level, str_repeat('    ', (int) $u->level).$u->nama_unit,
                    $u->nama_jabatan, $u->kategori, $u->eselon, $indukNama[$u->parent_id] ?? null,
                    ...$jawaban, count(array_filter($jawaban, fn ($j) => $j === 'Ya')),
                ];
            }

            $progres = $this->penilaian->progres($opd);
            $ringkasan[] = [
                $opd->urutan, $opd->nama, $opd->status->label(), $progres['total'], $progres['terjawab'],
                $opd->submitted_at?->format('d/m/Y H:i'), ...array_values($ya),
            ];
        }

        $book = new Spreadsheet;
        $this->isi($book->getActiveSheet()->setTitle('Ringkasan OPD'), $ringkasan, [6, 60, 14, 12, 12, 18, 18, 18, 22]);
        $this->isi($book->createSheet()->setTitle('Jawaban per Unit'), $perUnit, [40, 7, 70, 55, 20, 8, 45, 16, 16, 20, 10]);
        $book->setActiveSheetIndex(0);
        (new Xlsx($book))->save($path);
    }

    private function isi(Worksheet $sheet, array $baris, array $lebar): void
    {
        $sheet->fromArray($baris, null, 'A1', true);
        $kolomAkhir = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$kolomAkhir}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$kolomAkhir}".count($baris));
        foreach ($lebar as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
    }
}
