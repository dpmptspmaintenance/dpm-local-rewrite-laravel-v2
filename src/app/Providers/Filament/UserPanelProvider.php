<?php

namespace App\Providers\Filament;

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
 * User Management — a self-contained Filament panel replacing the old
 * Blade/Bootstrap /user module. Not ->default(): the app root and every
 * other module stay on the existing Blade/Controller stack. No ->login()
 * page here — this panel reuses the app's existing Google OAuth session
 * (see AuthController); an unauthenticated visit falls through to Laravel's
 * standard Authenticate::redirectTo(), which sends the user to the app's
 * own named 'login' route. Same pattern as KepegawaianPanelProvider.
 *
 * Access gated to role===1 only, via User::canAccessPanel() branching on
 * $panel->getId() === 'user' — see that method for why it's stricter here
 * than the Kepegawaian panel.
 */
class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('user')
            ->path('user')
            ->brandName('User Management — DPMPTSP')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\Filament\User\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\Filament\User\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Not a real sign-out: this panel shares the app's single Google
            // OAuth session with every other module, so "logging out" here
            // would end the whole session. Intentional, mirrors
            // KepegawaianPanelProvider — this just exits the module back to
            // the app's main dashboard.
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
