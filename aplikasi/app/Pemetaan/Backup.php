<?php

namespace App\Pemetaan;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/** Salinan database SQLite yang konsisten walau aplikasi sedang dipakai (VACUUM INTO). */
final class Backup
{
    public function buat(?string $folder = null): string
    {
        $folder ??= storage_path('app/backup');
        File::ensureDirectoryExists($folder);
        $path = $folder.'/pemetaan-'.now()->format('Ymd-His').'.sqlite';
        if (file_exists($path)) {
            $path = $folder.'/pemetaan-'.now()->format('Ymd-His').'-'.uniqid().'.sqlite';
        }
        DB::statement('VACUUM INTO ?', [$path]);

        return $path;
    }

    public function bersihkan(int $simpanHari = 7, ?string $folder = null): int
    {
        $folder ??= storage_path('app/backup');
        if (! is_dir($folder)) {
            return 0;
        }
        $batas = now()->subDays($simpanHari)->getTimestamp();
        $dihapus = 0;
        foreach (glob($folder.'/pemetaan-*.sqlite') as $file) {
            if (filemtime($file) < $batas && unlink($file)) {
                $dihapus++;
            }
        }

        return $dihapus;
    }
}
