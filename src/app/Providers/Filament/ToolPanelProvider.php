<?php

namespace App\Providers\Filament;

use App\Filament\Tool\Widgets\StatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Tool panel — wadah modul "Tool" (Surat Tugas, Surat Pengantar, Notulen,
 * Generator Sertifikat, Nomor Sertifikat, Master Font, Dasar Hukum, Convert
 * Excel ke PDF). Dipisah dari panel Kepegawaian supaya modul tool bisa
 * dibuka semua pengguna yang login tanpa membuka data kepegawaian.
 *
 * Not ->default(): app root dan modul lain tetap memakai stack Blade/
 * Controller. Tidak ada ->login() — sesi Google OAuth aplikasi dipakai ulang;
 * pengunjung tanpa sesi dilempar oleh Authenticate::redirectTo() ke route
 * 'login'.
 *
 * Izin akses: User::canAccessPanel() mengembalikan true untuk panel 'tool'
 * bagi semua user yang login (lihat method itu).
 */
class ToolPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tool')
            ->path('tool')
            ->brandName('Tool — DPMPTSP')
            ->favicon(asset('favicon.ico').'?v=2')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Tool/Resources'), for: 'App\Filament\Tool\Resources')
            ->discoverPages(in: app_path('Filament/Tool/Pages'), for: 'App\Filament\Tool\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tool/Widgets'), for: 'App\Filament\Tool\Widgets')
            ->widgets([
                StatsOverview::class,
            ])
            ->userMenuItems([
                'logout' => MenuItem::make()
                    ->label('Kembali ke Dashboard')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url(fn () => route('dashboard')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
