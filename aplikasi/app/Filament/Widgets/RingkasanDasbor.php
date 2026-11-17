<?php

namespace App\Filament\Widgets;

use App\Pemetaan\Statistik;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RingkasanDasbor extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $s = app(Statistik::class)->ringkasan();
        $persen = fn (int $a, int $b) => $b ? round($a / $b * 100) : 0;

        return [
            Stat::make('Unit terisi lengkap', "{$s['unit_lengkap']}/{$s['unit_total']}")
                ->description($persen($s['unit_lengkap'], $s['unit_total']).'% dari semua unit yang dinilai')
                ->color('primary'),
            Stat::make('OPD terkirim', "{$s['opd_terkirim']}/{$s['opd_total']}")
                ->description(($s['opd_total'] - $s['opd_terkirim']).' OPD belum mengirim'),
            Stat::make('Memenuhi ketiga indikator', (string) $s['unit_semua_ya'])
                ->description('Unit dengan jawaban Ya di semua indikator')
                ->color('success'),
            Stat::make('Memenuhi minimal satu', (string) $s['unit_minimal_satu_ya'])
                ->description('Unit dengan setidaknya satu jawaban Ya'),
        ];
    }
}
