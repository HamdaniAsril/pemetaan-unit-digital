<?php

namespace App\Console\Commands;

use App\Pemetaan\Backup;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'pemetaan:backup {--simpan=7 : Hapus backup yang lebih lama dari N hari}';

    protected $description = 'Buat salinan database ke storage/app/backup dan hapus salinan lama.';

    public function handle(Backup $backup): int
    {
        $path = $backup->buat();
        $dihapus = $backup->bersihkan((int) $this->option('simpan'));
        $this->info("Backup: {$path} (dihapus {$dihapus} file lama)");

        return self::SUCCESS;
    }
}
