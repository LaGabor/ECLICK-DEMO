<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\User\Pages\UserDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('account')
            ->default(false)
            ->path('account')
            ->login(null)
            ->brandName(__('user.panel.name'))
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\\Filament\\User\\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
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
                EnsureEmailIsVerified::class,
            ]);

        $mainDomain = config('app.main_domain');

        if (filled($mainDomain)) {
            $panel->domain((string) $mainDomain);
        }

        return $panel
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): View => view('filament.hooks.user-panel-utilities'),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): View => view('filament.hooks.vite-media-image-preview'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): View => view('components.media-image-preview-modal'),
            )
            ->homeUrl(fn (): string => UserDashboard::getUrl(panel: 'account'));
    }
}
