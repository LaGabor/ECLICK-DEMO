<?php

use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (Request $request): string {
            $adminPanel = Filament::getPanel('admin');
            $adminLoginUrl = $adminPanel?->getLoginUrl();

            $adminDomain = (string) config('app.admin_domain');
            $hostMatchesAdmin = $adminDomain !== ''
                && strcasecmp((string) $request->getHost(), $adminDomain) === 0;

            $routeName = $request->route()?->getName() ?? '';
            $isFilamentAdminRoute = str_starts_with($routeName, 'filament.admin.');

            if (filled($adminLoginUrl) && ($hostMatchesAdmin || $isFilamentAdminRoute)) {
                return $adminLoginUrl;
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
