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
            'petunjuk' => 'Jawab <b>Ya</b> bila ada dokumen tertulis, misalnya SK, Peraturan Bupati, surat tugas, atau uraian tugas, yang menyebut unit ini mengelola layanan atau sistem digital. Jawab Tidak bila hanya kebiasaan tanpa dokumen.',
        ],
        'keluaran' => [
            'label' => 'Keluaran Utama',
            'pertanyaan' => 'Apakah unit ini menghasilkan keluaran (output) digital sebagai produk atau layanan utamanya?',
            'ringkas' => 'Output digital sebagai produk/layanan utama?',
            'petunjuk' => 'Jawab <b>Ya</b> bila hasil kerja utama unit ini berbentuk digital, misalnya aplikasi, situs web, sistem informasi, data, atau layanan daring. Jawab Tidak bila komputer hanya alat bantu, seperti mengetik surat.',
        ],
        'sumber_daya' => [
            'label' => 'Sumber Daya Khusus',
            'pertanyaan' => 'Apakah unit ini memiliki alokasi sumber daya manusia atau anggaran khusus untuk pengelolaan teknologi informasi?',
            'ringkas' => 'Ada SDM atau anggaran khusus untuk TI?',
            'petunjuk' => 'Jawab <b>Ya</b> bila ada pegawai yang ditugaskan khusus mengurus TI, misalnya pranata komputer atau admin sistem, atau ada anggaran khusus TI seperti belanja aplikasi, hosting, atau jaringan. Jawab Tidak bila urusan TI hanya dikerjakan sambilan tanpa anggaran khusus.',
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

    /** Penjelasan sederhana untuk pengisi awam; berisi HTML tebal (<b>) yang aman karena konstan. */
    public static function petunjuk(string $kode): string
    {
        return self::ambil($kode)['petunjuk'];
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
