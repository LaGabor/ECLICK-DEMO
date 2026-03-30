<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label(__('filament.contact_messages.actions.back'))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(static::getResource()::getUrl('index'))
                ->color('gray')
                ->outlined(),
        ];
    }
}
