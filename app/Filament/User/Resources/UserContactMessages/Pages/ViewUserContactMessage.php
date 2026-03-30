<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages\Pages;

use App\Filament\User\Resources\UserContactMessages\UserContactMessageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

final class ViewUserContactMessage extends ViewRecord
{
    protected static string $resource = UserContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label(__('user.contact.infolist.back_to_list'))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(self::getResource()::getUrl('index', panel: 'account'))
                ->color('gray')
                ->outlined(),
        ];
    }
}
