<?php

declare(strict_types=1);

namespace App\Filament\User\Resources\ParticipantReceipts\Pages;

use App\Filament\User\Resources\ParticipantReceipts\ParticipantReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListParticipantReceipts extends ListRecords
{
    protected static string $resource = ParticipantReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('user.receipts.create')),
        ];
    }
}
