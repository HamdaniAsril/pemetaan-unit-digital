<?php

namespace App\Console\Commands;

use App\Pemetaan\ImporStruktur;
use Illuminate\Console\Command;

class ImporStrukturCommand extends Command
{
    protected $signature = 'pemetaan:impor {path? : File Excel struktur (default database/data/struktur.xlsx)}';

    protected $description = 'Impor OPD dan unit struktural dari Excel. Data yang sudah ada tidak ditimpa.';

    public function handle(ImporStruktur $impor): int
    {
        $hasil = $impor->impor($this->argument('path') ?? database_path('data/struktur.xlsx'));

        $this->info("OPD baru: {$hasil->opdBaru}");
        $this->info("Unit baru: {$hasil->unitBaru}");

        if ($hasil->berbeda) {
            $this->warn('Berbeda dengan file (tidak diubah, periksa lewat panel admin):');
            foreach ($hasil->berbeda as $baris) {
                $this->line("  - {$baris}");
            }
        }
        if ($hasil->hilang) {
            $this->warn('Ada di database tetapi tidak ada di file (tidak dihapus):');
            foreach ($hasil->hilang as $baris) {
                $this->line("  - {$baris}");
            }
        }

        return self::SUCCESS;
    }
}
