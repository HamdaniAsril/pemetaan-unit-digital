<?php

namespace App\Filament\Widgets;

use App\Enums\StatusOpd;
use App\Models\Opd;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RingkasanStatus extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $jumlah = Opd::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            Stat::make('Terkirim', $jumlah[StatusOpd::Terkirim->value] ?? 0)->color('success'),
            Stat::make('Mengisi', $jumlah[StatusOpd::Mengisi->value] ?? 0)->color('warning'),
            Stat::make('Belum mulai', $jumlah[StatusOpd::BelumMulai->value] ?? 0),
            Stat::make('Perlu dilengkapi', Opd::query()->perluDilengkapi()->count())->color('danger'),
        ];
    }
}
