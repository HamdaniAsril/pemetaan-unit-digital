<?php

namespace App\Filament\Resources\OpdResource\Pages;

use App\Filament\Resources\OpdResource;
use App\Pemetaan\Format;
use App\Pemetaan\Indikator;
use App\Pemetaan\Penilaian;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewOpd extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OpdResource::class;

    protected static string $view = 'filament.opd.lihat-jawaban';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return Format::namaUnit($this->record->nama);
    }

    protected function getViewData(): array
    {
        $penilaian = app(Penilaian::class);

        return [
            'tabs' => $penilaian->tabs($this->record),
            'progres' => $penilaian->progres($this->record),
            'indikator' => Indikator::KODE,
        ];
    }
}
