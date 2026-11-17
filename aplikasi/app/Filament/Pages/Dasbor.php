<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GrafikIndikator;
use App\Filament\Widgets\PerluTindakLanjut;
use App\Filament\Widgets\RingkasanDasbor;
use Filament\Pages\Dashboard;

/** Dasbor: gambaran hasil dan tindak lanjut. Pengelolaan OPD ada di menu OPD. */
class Dasbor extends Dashboard
{
    protected static ?string $title = 'Dasbor';

    public function getWidgets(): array
    {
        return [RingkasanDasbor::class, GrafikIndikator::class, PerluTindakLanjut::class];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
