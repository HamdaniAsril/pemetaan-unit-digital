<?php

namespace App\Pemetaan;

use InvalidArgumentException;

final class Indikator
{
    public const KODE = ['mandat', 'keluaran', 'sumber_daya'];

    private const DATA = [
        'mandat' => [
            'label' => 'Mandat Formal',
            'pertanyaan' => 'Apakah terdapat mandat formal dari pimpinan atau peraturan tertulis yang mendasari unit ini sebagai unit digital?',
            'ringkas' => 'Ada mandat formal / peraturan tertulis sebagai unit digital?',
        ],
        'keluaran' => [
            'label' => 'Keluaran Utama',
            'pertanyaan' => 'Apakah unit ini menghasilkan keluaran (output) digital sebagai produk atau layanan utamanya?',
            'ringkas' => 'Output digital sebagai produk/layanan utama?',
        ],
        'sumber_daya' => [
            'label' => 'Sumber Daya Khusus',
            'pertanyaan' => 'Apakah unit ini memiliki alokasi sumber daya manusia atau anggaran khusus untuk pengelolaan teknologi informasi?',
            'ringkas' => 'Ada SDM atau anggaran khusus untuk TI?',
        ],
    ];

    public static function label(string $kode): string
    {
        return self::ambil($kode)['label'];
    }

    public static function pertanyaan(string $kode): string
    {
        return self::ambil($kode)['pertanyaan'];
    }

    public static function ringkas(string $kode): string
    {
        return self::ambil($kode)['ringkas'];
    }

    public static function valid(string $kode): bool
    {
        return isset(self::DATA[$kode]);
    }

    private static function ambil(string $kode): array
    {
        return self::DATA[$kode] ?? throw new InvalidArgumentException("Indikator tidak dikenal: {$kode}");
    }
}
