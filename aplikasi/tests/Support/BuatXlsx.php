<?php

namespace Tests\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class BuatXlsx
{
    public const HEADER = ['No', 'Nama OPD', 'Level', 'Nama Jabatan (berjenjang)', 'Nama Jabatan',
        'Unit Organisasi', 'Kategori', 'Eselon', 'Atasan Langsung', 'Kelas Jabatan'];

    /**
     * @param  list<array{0:int,1:string,2:int,3:string,4:string,5:string,6:?string}>  $baris
     *                                                                                        [no, opd, level, unit, kategori, eselon, atasan]
     */
    public static function struktur(array $baris): string
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->setTitle('Rekap OPD');
        $sheet = $book->createSheet()->setTitle('Struktur per OPD');
        $sheet->fromArray(self::HEADER, null, 'A1');
        $i = 2;
        foreach ($baris as [$no, $opd, $level, $unit, $kategori, $eselon, $atasan]) {
            $jabatan = 'KEPALA '.$unit;
            $sheet->fromArray([$no, $opd, $level, $jabatan, $jabatan, $unit, $kategori, $eselon, $atasan, null], null, "A{$i}");
            $i++;
        }
        $path = tempnam(sys_get_temp_dir(), 'struktur').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    /** Satu OPD contoh: kepala → Sekretariat (1 subbag) + Bidang A, ditambah Staf Ahli. */
    public static function contoh(): array
    {
        return [
            [1, 'DINAS CONTOH', 0, 'DINAS CONTOH', 'Dinas', 'II.b', 'PEMERINTAH KABUPATEN'],
            [1, 'DINAS CONTOH', 1, 'SEKRETARIAT', 'Sekretariat Dinas', 'III.a', 'DINAS CONTOH'],
            [1, 'DINAS CONTOH', 2, 'SUB BAGIAN UMUM', 'Sub Bagian', 'IV.a', 'SEKRETARIAT'],
            [1, 'DINAS CONTOH', 1, 'BIDANG A', 'Bidang', 'III.b', 'DINAS CONTOH'],
            [1, 'DINAS CONTOH', 1, 'STAF AHLI X', 'Staf Ahli', 'II.b', 'DINAS CONTOH'],
        ];
    }
}
