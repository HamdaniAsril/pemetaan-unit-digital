<?php

namespace App\Console\Commands;

use App\Pemetaan\AkunOpd;
use Illuminate\Console\Command;

class ImporAkunCommand extends Command
{
    protected $signature = 'pemetaan:impor-akun {path : File Excel berkolom OPD, Username, Password}';

    protected $description = 'Pasang username dan kata sandi akun OPD dari Excel (membuat atau memperbarui akun).';

    public function handle(AkunOpd $svc): int
    {
        $hasil = $svc->imporDariExcel($this->argument('path'));
        $this->info("Akun dibuat: {$hasil->dibuat}");
        $this->info("Akun diperbarui: {$hasil->diperbarui}");
        foreach ($hasil->dilewati as $baris) {
            $this->warn("Dilewati {$baris}");
        }

        return self::SUCCESS;
    }
}
