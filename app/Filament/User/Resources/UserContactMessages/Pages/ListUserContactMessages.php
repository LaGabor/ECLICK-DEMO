<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\UserContactMessages\Pages;

use App\Filament\User\Resources\UserContactMessages\UserContactMessageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListUserContactMessages extends ListRecords
{
    protected static string $resource = UserContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('user.contact.create')),
        ];
    }
}
