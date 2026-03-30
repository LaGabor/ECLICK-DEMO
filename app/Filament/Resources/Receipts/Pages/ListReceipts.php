<?php

declare(strict_types=1);

namespace App\Filament\Resources\Receipts\Pages;

use App\Filament\Resources\Receipts\ReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
