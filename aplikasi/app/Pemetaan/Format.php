<?php

namespace App\Pemetaan;

final class Format
{
    private const KAPITAL = ['Upt', 'Uptd', 'Ii', 'Iii', 'Iv', 'Vi', 'Vii', 'Mblb', 'Pkb', 'Bbnkb', 'Pbb', 'Pbjt', 'Ppj', 'Bphtb',
        'Dprd', 'Rsud', 'Tik', 'Ti', 'Opd', 'Umkm', 'Kb', 'Pp', 'Sdm'];

    private const KECIL = ['Dan', 'Di', 'Ke', 'Dari', 'Yang', 'Untuk'];

    /** "BIDANG PAJAK DAERAH II" → "Bidang Pajak Daerah II", seperti pretty() di prototipe. */
    public static function namaUnit(string $nama): string
    {
        $s = mb_convert_case(mb_strtolower($nama), MB_CASE_TITLE);
        $s = preg_replace_callback('/\(([^)]+)\)/u', fn ($m) => '('.mb_strtoupper($m[1]).')', $s);
        $s = preg_replace_callback('/\b(\p{L}+)\b/u', function ($m) {
            if (in_array($m[1], self::KAPITAL, true)) {
                return mb_strtoupper($m[1]);
            }

            return in_array($m[1], self::KECIL, true) ? mb_strtolower($m[1]) : $m[1];
        }, $s);

        return mb_strtoupper(mb_substr($s, 0, 1)).mb_substr($s, 1);
    }
}
