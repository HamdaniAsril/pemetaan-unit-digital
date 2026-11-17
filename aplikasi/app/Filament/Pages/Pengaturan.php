<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Pengaturan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = 'Pengaturan';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.pengaturan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['kontak_admin' => Setting::ambil('kontak_admin')]);
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\TextInput::make('kontak_admin')->label('Kontak admin')->maxLength(200)
                ->helperText('Tampil di form OPD: "Unit tidak sesuai? Hubungi admin: …". Kosongkan untuk menyembunyikan.'),
        ]);
    }

    public function simpan(): void
    {
        $data = $this->form->getState();
        Setting::simpan('kontak_admin', filled($data['kontak_admin'] ?? null) ? $data['kontak_admin'] : null);
        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}
