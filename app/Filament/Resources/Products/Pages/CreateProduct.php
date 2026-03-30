<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Concerns\DispatchesProductImageProcessingJob;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use DispatchesProductImageProcessingJob;

    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $this->dispatchProductImageJobIfNeeded(null);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
