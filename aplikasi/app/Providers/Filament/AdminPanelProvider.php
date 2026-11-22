<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Pemetaan Unit Digital')
            ->sidebarWidth('15rem')
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::hex(config('tema.warna.aksen')),
                'gray' => Color::Slate,
                'danger' => Color::hex(config('tema.warna.galat')),
                'warning' => Color::Amber,
                'success' => Color::hex(config('tema.warna.ya')),
            ])
            ->font('Manrope', url: '/fonts/fonts.css', provider: \Filament\FontProviders\LocalFontProvider::class)
            ->darkMode(false)
            ->renderHook(\Filament\View\PanelsRenderHook::HEAD_END, fn () => view('filament.tema'))
            // Versi yang berjalan (commit + waktu deploy), untuk memastikan server ikut terbarui setelah push.
            ->renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER, fn () => new \Illuminate\Support\HtmlString(
                '<div style="padding:.75rem 1.5rem;font-size:.75rem;color:var(--tinta-2)">'.e(\App\Pemetaan\Versi::teks()).'</div>'
            ))
            // Di HP menu samping selalu tertutup saat halaman dibuka (Filament menyimpan status terakhirnya).
            ->renderHook(\Filament\View\PanelsRenderHook::BODY_END, fn () => new \Illuminate\Support\HtmlString(
                '<script>document.addEventListener("alpine:initialized",()=>{if(innerWidth<1024)Alpine.store("sidebar").close()})</script>'
            ))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dasbor::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
