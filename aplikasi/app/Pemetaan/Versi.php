<?php

namespace App\Pemetaan;

use Carbon\CarbonImmutable;

/**
 * Versi yang sedang berjalan, dari berkas versi.txt yang ditulis skrip deploy
 * (isi: "<commit singkat> <waktu ISO-8601>"). Dipakai untuk memastikan server ikut terbarui setelah push.
 */
final class Versi
{
    public static function teks(): string
    {
        $isi = @file_get_contents(base_path('versi.txt'));
        if (! $isi || ! preg_match('/^([0-9a-f]{7,40})\s+(\S+)/', trim($isi), $m)) {
            return 'Versi pengembangan';
        }
        try {
            $waktu = CarbonImmutable::parse($m[2])->setTimezone(config('app.timezone'))->format('j M Y H:i');
        } catch (\Throwable) {
            $waktu = $m[2];
        }

        return "Versi {$m[1]} · {$waktu}";
    }
}
