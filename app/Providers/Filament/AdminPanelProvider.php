<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Controllers\Admin\DownloadRefundExportZipController;
use App\Http\Controllers\Admin\SignedDownloadRefundExportZipController;
use App\Http\Controllers\Media\StreamProductImageController;
use App\Http\Controllers\Media\StreamReceiptImageController;
use App\Http\Middleware\ValidateRefundExportSignedDownload;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(config('app.admin_domain'))
            ->path('')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ->routes(function (Panel $_panel): void {
                Route::get('/refund-exports/{refundExport}/download-signed', SignedDownloadRefundExportZipController::class)
                    ->middleware([
                        ValidateRefundExportSignedDownload::class,
                        Authenticate::class,
                        'throttle:60,1',
                    ])
                    ->name('refund-exports.download-signed');
            })
            ->authenticatedRoutes(function (Panel $_panel): void {
                Route::get('/refund-exports/{refundExport}/download', DownloadRefundExportZipController::class)
                    ->name('downloads.refund-export-zip');

                Route::get('/media/receipts/{receipt}/image', StreamReceiptImageController::class)
                    ->middleware('throttle:120,1')
                    ->name('media.receipts.image');

                Route::get('/media/products/{product}/image', StreamProductImageController::class)
                    ->middleware('throttle:120,1')
                    ->name('media.products.image');
            })
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): View => view('filament.hooks.vite-media-image-preview'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): View => view('components.media-image-preview-modal'),
            );
    }
}
