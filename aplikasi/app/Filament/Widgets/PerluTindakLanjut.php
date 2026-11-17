<?php

namespace App\Filament\Widgets;

use App\Enums\StatusOpd;
use App\Filament\Resources\OpdResource;
use App\Models\Opd;
use App\Pemetaan\Format;
use App\Pemetaan\Statistik;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** OPD yang belum selesai (belum kirim, atau terkirim tapi ada unit baru), progres terendah dulu. */
class PerluTindakLanjut extends TableWidget
{
    protected static ?string $heading = 'Perlu ditindaklanjuti';

    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->description('OPD yang belum mengirim atau perlu melengkapi unit baru. Progres paling rendah di atas.')
            ->query(fn () => Statistik::denganProgres(Opd::query()->select('opds.*')->with('user'))
                ->where(fn (Builder $q) => $q->where('status', '!=', StatusOpd::Terkirim)->orWhere(fn (Builder $q) => $q->perluDilengkapi())))
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('(unit_lengkap * 1.0) / max(unit_total, 1) asc')->orderBy('urutan'))
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('nama')->label('OPD')->wrap()
                    ->formatStateUsing(fn (string $state) => Format::namaUnit($state))
                    ->description(fn (Opd $record) => $record->user?->username),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (StatusOpd $state) => $state === StatusOpd::Terkirim ? 'Perlu dilengkapi' : $state->label())
                    ->color(fn (StatusOpd $state) => match ($state) {
                        StatusOpd::Terkirim => 'danger', StatusOpd::Mengisi => 'warning', StatusOpd::BelumMulai => 'gray',
                    }),
                Tables\Columns\TextColumn::make('unit_lengkap')->label('Progres')->html()
                    ->formatStateUsing(fn ($state, Opd $record) => '<span class="butir-progres" aria-hidden="true">'
                        .str_repeat('<i class="isi"></i>', (int) $state).str_repeat('<i></i>', max(0, (int) $record->unit_total - (int) $state))
                        .'</span><span class="butir-angka">'.e("{$state}/{$record->unit_total} unit").'</span>'),
            ])
            ->recordUrl(fn (Opd $record) => OpdResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Semua OPD sudah mengirim')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
