<?php

namespace App\Providers\Filament;

use App\Filament\Kepegawaian\Widgets\CutiTerbaru;
use App\Filament\Kepegawaian\Widgets\LiburMendatang;
use App\Filament\Kepegawaian\Widgets\StatsOverview;
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
 * Kepegawaian module — a self-contained Filament panel bolted onto this
 * otherwise Blade/Bootstrap app. Not ->default(): the app root and every
 * other module stay on the existing Blade/Controller stack. No ->login()
 * page here — this panel reuses the app's existing Google OAuth session
 * (see AuthController); an unauthenticated visit falls through to Laravel's
 * standard Authenticate::redirectTo(), which sends the user to the app's
 * own named 'login' route.
 */
class KepegawaianPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('kepegawaian')
            ->path('kepegawaian')
            ->brandName('Kepegawaian — DPMPTSP')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Kepegawaian/Resources'), for: 'App\Filament\Kepegawaian\Resources')
            ->discoverPages(in: app_path('Filament/Kepegawaian/Pages'), for: 'App\Filament\Kepegawaian\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Kepegawaian/Widgets'), for: 'App\Filament\Kepegawaian\Widgets')
            ->widgets([
                StatsOverview::class,
                CutiTerbaru::class,
                LiburMendatang::class,
            ])
            // Not a real sign-out: this panel shares the app's single Google
            // OAuth session with every other module, so "logging out" here
            // would end the whole session. Intentional per user request —
            // this just exits the module back to the app's main dashboard.
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
