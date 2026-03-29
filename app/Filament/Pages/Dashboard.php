<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * The stock Filament dashboard uses `/`, but the panel already registers `GET /` as
     * `filament.admin.home` (redirect). A second `GET /` for the dashboard is dropped by
     * the route collection, so `filament.admin.pages.dashboard` is never defined and
     * navigation / redirects that call `route()` throw RouteNotFoundException.
     */
    protected static string $routePath = '/dashboard';
}
