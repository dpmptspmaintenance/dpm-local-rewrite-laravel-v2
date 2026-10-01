<?php

namespace App\Providers\Filament;

use App\Filament\Arsip\Pages\Dashboard;
use App\Filament\Arsip\Widgets\StatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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
 * Arsip Digital — panel Filament berdiri sendiri, bolted-on seperti
 * Kepegawaian/User. Tidak ->default(): sisa aplikasi tetap Blade/Bootstrap.
 * Tidak ada ->login() sendiri — pakai sesi Google OAuth aplikasi yang sudah
 * ada (lihat AuthController); pengunjung yang belum login diarahkan ke rute
 * 'login' bawaan aplikasi lewat Authenticate::redirectTo() standar Laravel.
 *
 * Beda dengan panel Kepegawaian/User: gerbang canAccessPanel() untuk id
 * 'arsip' selalu true (lihat App\Models\User::canAccessPanel) — SEMUA
 * pengguna yang sudah login boleh masuk untuk mengunggah dokumen (peran
 * "Pengunggah/Staf" di AGENTS.md § 2.A). Pembatasan verifikasi/kelola
 * kategori-tag dilakukan per-Resource/Page lewat User::isArsipAdmin().
 */
class ArsipPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('arsip')
            ->path('arsip')
            ->brandName('Arsip Digital — DPMPTSP')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Arsip/Resources'), for: 'App\Filament\Arsip\Resources')
            ->discoverPages(in: app_path('Filament/Arsip/Pages'), for: 'App\Filament\Arsip\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Arsip/Widgets'), for: 'App\Filament\Arsip\Widgets')
            ->widgets([
                StatsOverview::class,
            ])
            // Bukan sign-out sungguhan: panel ini berbagi satu sesi Google
            // OAuth aplikasi dengan semua modul lain — "logout" di sini cuma
            // keluar dari modul, kembali ke dashboard utama. Pola sama
            // seperti KepegawaianPanelProvider/UserPanelProvider.
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
