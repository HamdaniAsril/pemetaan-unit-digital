<?php

namespace App\Filament\Resources\OpdResource\Pages;

use App\Filament\Resources\OpdResource;
use App\Pemetaan\AkunOpd;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListOpds extends ListRecords
{
    protected static string $resource = OpdResource::class;

    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\RingkasanStatus::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('unduhRekap')->label('Unduh rekap Excel')->icon('heroicon-o-arrow-down-tray')
                ->url(route('unduh.rekap')),
            Actions\Action::make('unduhBackup')->label('Unduh backup')->icon('heroicon-o-circle-stack')->color('gray')
                ->url(route('unduh.backup')),
            Actions\Action::make('buatSemuaAkun')->label('Buat semua akun')->icon('heroicon-o-user-plus')->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Membuat akun untuk OPD yang belum punya akun, lalu mengunduh Excel berisi username dan kata sandi awal. File ini hanya bisa diunduh sekali; simpan baik-baik.')
                ->action(function () {
                    $svc = app(AkunOpd::class);
                    $akun = $svc->buatAkun();
                    if ($akun->isEmpty()) {
                        Notification::make()->title('Semua OPD sudah punya akun')->info()->send();

                        return null;
                    }
                    $path = tempnam(sys_get_temp_dir(), 'akun');
                    $svc->ekspor($akun, $path);

                    return response()->download($path, 'akun-opd.xlsx')->deleteFileAfterSend();
                }),
        ];
    }
}
