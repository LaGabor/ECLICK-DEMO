<?php

namespace App\Providers;

use App\Contracts\Receipts\ReceiptStatusNotificationServiceInterface;
use App\Contracts\Receipts\ReceiptWorkflowServiceInterface;
use App\Contracts\Refunds\ReceiptRefundTotalCalculatorInterface;
use App\Contracts\Refunds\RefundExportGeneratorInterface;
use App\Events\ReceiptSubmissionStatusChanged;
use App\Listeners\SendReceiptStatusNotificationListener;
use App\Models\Product;
use App\Models\Receipt;
use App\Observers\ProductObserver;
use App\Observers\ReceiptObserver;
use App\Services\Receipts\ReceiptStatusNotificationService;
use App\Services\Receipts\ReceiptWorkflowService;
use App\Services\Refunds\ReceiptPromotionalRefundTotalCalculator;
use App\Services\Refunds\RefundExportGenerator;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReceiptRefundTotalCalculatorInterface::class, ReceiptPromotionalRefundTotalCalculator::class);
        $this->app->bind(RefundExportGeneratorInterface::class, RefundExportGenerator::class);
        $this->app->bind(ReceiptWorkflowServiceInterface::class, ReceiptWorkflowService::class);
        $this->app->bind(ReceiptStatusNotificationServiceInterface::class, ReceiptStatusNotificationService::class);

        $this->app->booting(function (): void {
            $this->app['events']->listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
                $connection = $event->connection;
                if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
                    return;
                }

                $connection->statement('SET SESSION autocommit=1');
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthRateLimiters();

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): string => view('filament.hooks.receipt-status-filter-tag-colors')->render(),
        );

        Receipt::observe(ReceiptObserver::class);
        Product::observe(ProductObserver::class);

        Event::listen(
            ReceiptSubmissionStatusChanged::class,
            SendReceiptStatusNotificationListener::class,
        );
    }

    private function configureAuthRateLimiters(): void
    {
        RateLimiter::for('auth-login', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));

        RateLimiter::for('auth-register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('auth-password-email', fn (Request $request) => Limit::perMinute(4)->by($request->ip()));

        RateLimiter::for('auth-password-reset', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));

        RateLimiter::for('auth-verify-email', fn (Request $request) => Limit::perMinute(12)->by($request->ip()));

        RateLimiter::for('auth-verify-resend', fn (Request $request) => Limit::perMinute(4)->by($request->ip()));

        RateLimiter::for('auth-confirm-password', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        RateLimiter::for('auth-password-update', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));
    }
}
