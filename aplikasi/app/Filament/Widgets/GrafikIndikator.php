<?php

namespace App\Filament\Widgets;

use App\Pemetaan\Indikator;
use App\Pemetaan\Statistik;
use Filament\Widgets\ChartWidget;

class GrafikIndikator extends ChartWidget
{
    protected static ?string $heading = 'Jawaban per indikator';

    protected static ?string $description = 'Jumlah unit yang menjawab Ya dan Tidak (hanya unit yang sudah menjawab indikator tersebut).';

    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected static ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $per = app(Statistik::class)->ringkasan()['per_indikator'];

        return [
            'labels' => array_map(fn ($k) => Indikator::label($k), Indikator::KODE),
            'datasets' => [
                ['label' => 'Ya', 'data' => array_map(fn ($k) => $per[$k]['ya'], Indikator::KODE),
                    'backgroundColor' => config('tema.warna.ya'), 'borderRadius' => 6, 'borderWidth' => 0, 'maxBarThickness' => 40],
                ['label' => 'Tidak', 'data' => array_map(fn ($k) => $per[$k]['tidak'], Indikator::KODE),
                    'backgroundColor' => config('tema.warna.tidak'), 'borderRadius' => 6, 'borderWidth' => 0, 'maxBarThickness' => 40],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
