<?php

namespace App\Filament\Resources;

use App\Enums\StatusOpd;
use App\Filament\Resources\OpdResource\Pages;
use App\Models\Opd;
use App\Pemetaan\AkunOpd;
use App\Pemetaan\Format;
use App\Pemetaan\Penilaian;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class OpdResource extends Resource
{
    protected static ?string $model = Opd::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'OPD';

    protected static ?string $pluralModelLabel = 'OPD';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user')
                ->addSelect(['jawaban_terakhir' => \App\Models\Answer::query()->selectRaw('max(answers.updated_at)')
                    ->join('units', 'units.id', '=', 'answers.unit_id')->whereColumn('units.opd_id', 'opds.id')]))
            ->defaultSort('urutan')
            ->paginated([25, 50])
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('urutan')->label('No')->sortable()->visibleFrom('md'),
                Tables\Columns\TextColumn::make('nama')->label('OPD')->searchable()->wrap()
                    ->formatStateUsing(fn (string $state) => Format::namaUnit($state))
                    ->description(fn (Opd $record) => $record->user?->username, position: 'below'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (StatusOpd $state) => $state->label())
                    ->color(fn (StatusOpd $state) => match ($state) {
                        StatusOpd::Terkirim => 'success', StatusOpd::Mengisi => 'warning', StatusOpd::BelumMulai => 'gray',
                    })
                    ->description(function (Opd $record) {
                        $penilaian = app(Penilaian::class);
                        $p = $penilaian->progres($record);

                        return "{$p['terjawab']}/{$p['total']} unit".($penilaian->perluDilengkapi($record) ? ' · perlu dilengkapi' : '');
                    }),
                Tables\Columns\TextColumn::make('jawaban_terakhir')->label('Diubah terakhir')->dateTime('j M Y H:i')->placeholder('Belum ada')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('submitted_at')->label('Dikirim')->dateTime('j M Y H:i')->placeholder('Belum dikirim')->sortable()->visibleFrom('xl'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        StatusOpd::BelumMulai->value => 'Belum mulai',
                        StatusOpd::Mengisi->value => 'Mengisi',
                        StatusOpd::Terkirim->value => 'Terkirim',
                        'perlu_dilengkapi' => 'Perlu dilengkapi',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        null, '' => $query,
                        'perlu_dilengkapi' => $query->perluDilengkapi(),
                        default => $query->where('status', $data['value']),
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat jawaban')->iconButton()->tooltip('Lihat jawaban'),
                Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('bukaKunci')->label('Buka kunci')->icon('heroicon-o-lock-open')->color('warning')
                    ->visible(fn (Opd $record) => $record->status === StatusOpd::Terkirim)
                    ->requiresConfirmation()
                    ->modalDescription('OPD bisa mengubah jawabannya lagi dan harus mengirim ulang.')
                    ->action(function (Opd $record) {
                        app(Penilaian::class)->bukaKunci($record);
                        Notification::make()->title('Kunci dibuka')->success()->send();
                    }),
                Tables\Actions\Action::make('resetPassword')->label('Reset kata sandi')->icon('heroicon-o-key')->color('gray')
                    ->visible(fn (Opd $record) => $record->user !== null)
                    ->requiresConfirmation()
                    ->modalDescription('Kata sandi lama tidak berlaku lagi. Kata sandi baru hanya ditampilkan sekali.')
                    ->action(function (Opd $record) {
                        $baru = app(AkunOpd::class)->resetPassword($record->user);
                        Notification::make()->title('Kata sandi baru')
                            ->body(self::infoAkun($record->user->username, $baru))
                            ->persistent()->success()->send();
                    }),
                Tables\Actions\Action::make('buatAkun')->label('Buat akun')->icon('heroicon-o-user-plus')
                    ->visible(fn (Opd $record) => $record->user === null)
                    ->requiresConfirmation()
                    ->action(function (Opd $record) {
                        $akun = app(AkunOpd::class)->buatUntuk($record);
                        Notification::make()->title('Akun dibuat')
                            ->body(self::infoAkun($akun['username'], $akun['password']))
                            ->persistent()->success()->send();
                    }),
                ])->label('Aksi')->icon('heroicon-m-ellipsis-vertical')->tooltip('Aksi lain'),
            ])
            ->bulkActions([]);
    }

    private static function infoAkun(string $username, string $password): HtmlString
    {
        return new HtmlString('Username: <strong>'.e($username).'</strong><br>Kata sandi: <strong>'.e($password)
            .'</strong><br><br>Catat sekarang, tidak akan ditampilkan lagi.');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpds::route('/'),
            'view' => Pages\ViewOpd::route('/{record}'),
        ];
    }
}
