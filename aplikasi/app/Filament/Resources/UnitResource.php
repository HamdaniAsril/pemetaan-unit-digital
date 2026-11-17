<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use App\Pemetaan\Format;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $modelLabel = 'unit';

    protected static ?string $pluralModelLabel = 'Kelola unit';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('opd_id')->label('OPD')->relationship('opd', 'nama')
                ->searchable()->preload()->required()->live()
                ->disabledOn('edit'),
            Forms\Components\TextInput::make('nama_unit')->label('Nama unit')->required()->maxLength(255),
            Forms\Components\TextInput::make('nama_jabatan')->label('Nama jabatan')->maxLength(255),
            Forms\Components\TextInput::make('kategori')->maxLength(100),
            Forms\Components\TextInput::make('eselon')->maxLength(20),
            Forms\Components\Select::make('level')->options([0 => '0 · OPD', 1 => '1 · Bagian/Bidang/UPT', 2 => '2 · Subbagian/Subbidang/Seksi', 3 => '3'])
                ->required()->default(1),
            Forms\Components\Select::make('parent_id')->label('Induk')
                ->options(fn (Get $get, ?Unit $record) => Unit::query()
                    ->where('opd_id', $get('opd_id') ?? $record?->opd_id)
                    ->when($record, fn ($query) => $query->whereNotIn('id', self::idDiriDanTurunan($record)))
                    ->orderBy('urutan')->pluck('nama_unit', 'id'))
                ->searchable()
                ->rules([
                    fn (Get $get, ?Unit $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record) {
                        if ($value === null) {
                            return;
                        }
                        $opdId = $get('opd_id') ?? $record?->opd_id;
                        $induk = Unit::query()->find($value);
                        if (! $induk || (int) $induk->opd_id !== (int) $opdId) {
                            $fail('Induk harus unit di OPD yang sama.');
                        } elseif ($record && in_array((int) $value, self::idDiriDanTurunan($record), true)) {
                            $fail('Induk tidak boleh unit itu sendiri atau unit di bawahnya.');
                        }
                    },
                ]),
            Forms\Components\TextInput::make('urutan')->numeric()->required()->default(999)
                ->helperText('Urutan tampil di form. Angka kecil tampil lebih dulu.'),
            Forms\Components\Toggle::make('dinilai')->default(true)->helperText('Matikan untuk unit yang bukan unit kerja, misalnya Staf Ahli.'),
            Forms\Components\Toggle::make('aktif')->default(true)->helperText('Unit nonaktif disembunyikan dari form dan rekap; jawabannya tetap tersimpan.'),
        ])->columns(2);
    }

    /** @return list<int> */
    public static function idDiriDanTurunan(Unit $unit): array
    {
        $anakDari = Unit::query()->where('opd_id', $unit->opd_id)->get(['id', 'parent_id'])->groupBy('parent_id');
        $hasil = [];
        $antre = [$unit->id];
        while ($antre) {
            $id = array_shift($antre);
            if (in_array($id, $hasil, true)) {
                continue;
            }
            $hasil[] = $id;
            foreach ($anakDari->get($id, collect()) as $anak) {
                $antre[] = (int) $anak->id;
            }
        }

        return $hasil;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['opd', 'parent'])->withExists('answer'))
            ->defaultSort(fn (Builder $query) => $query->orderBy('opd_id')->orderBy('urutan'))
            ->columns([
                Tables\Columns\TextColumn::make('opd.nama')->label('OPD')->formatStateUsing(fn ($state) => Format::namaUnit($state))->wrap()->toggleable()->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('nama_unit')->label('Unit')->searchable()->wrap()
                    ->formatStateUsing(fn ($state, Unit $record) => str_repeat('— ', $record->level).Format::namaUnit($state)),
                Tables\Columns\TextColumn::make('kategori')->toggleable()->visibleFrom('md'),
                Tables\Columns\TextColumn::make('parent.nama_unit')->label('Induk')->formatStateUsing(fn ($state) => Format::namaUnit($state))->wrap()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('dinilai')->boolean()->visibleFrom('sm'),
                Tables\Columns\IconColumn::make('aktif')->boolean()->visibleFrom('sm'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('opd_id')->label('OPD')->relationship('opd', 'nama')->searchable()->preload(),
                Tables\Filters\TernaryFilter::make('dinilai'),
                Tables\Filters\TernaryFilter::make('aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Ubah'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Hapus')
                    ->hidden(fn (Unit $record) => $record->answer_exists ?? $record->answer()->exists())
                    ->modalDescription('Hanya unit tanpa jawaban yang bisa dihapus. Untuk unit lain, nonaktifkan saja.'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
