<?php

declare(strict_types=1);

namespace App\Filament\User\Pages;

use App\Models\Faq;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

final class UserDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = -100;

    protected static string $routePath = '/';

    protected string $view = 'filament.user.pages.user-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('user.navigation.dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('user.navigation.dashboard');
    }

    /**
     * @return Collection<int, Faq>
     */
    public function getFaqsProperty()
    {
        return Faq::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
