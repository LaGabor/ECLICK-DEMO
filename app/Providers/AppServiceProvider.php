<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
