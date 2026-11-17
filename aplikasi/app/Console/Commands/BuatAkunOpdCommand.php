<?php

namespace App\Console\Commands;

use App\Pemetaan\AkunOpd;
use Illuminate\Console\Command;

class BuatAkunOpdCommand extends Command
{
    protected $signature = 'pemetaan:buat-akun {--output= : Path file Excel hasil (default storage/app/akun-opd.xlsx)}';

    protected $description = 'Buat akun untuk OPD yang belum punya akun dan simpan kata sandi awal ke Excel.';

    public function handle(AkunOpd $svc): int
    {
        $akun = $svc->buatAkun();
        if ($akun->isEmpty()) {
            $this->info('Semua OPD sudah punya akun.');

            return self::SUCCESS;
        }
        $path = $this->option('output') ?: storage_path('app/akun-opd.xlsx');
        $svc->ekspor($akun, $path);
        $this->info("{$akun->count()} akun dibuat. Kata sandi awal disimpan di: {$path}");
        $this->warn('Bagikan file ini ke OPD lalu hapus dari server.');

        return self::SUCCESS;
    }
}
