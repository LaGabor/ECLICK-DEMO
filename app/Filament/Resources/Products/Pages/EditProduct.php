<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\Concerns\DispatchesProductImageProcessingJob;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use DispatchesProductImageProcessingJob;

    protected static string $resource = ProductResource::class;

    private ?string $productImagePathBeforeEdit = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->productImagePathBeforeEdit = $this->record->product_image;
    }

    protected function afterSave(): void
    {
        $this->dispatchProductImageJobIfNeeded($this->productImagePathBeforeEdit);
        $this->productImagePathBeforeEdit = $this->record->fresh()->product_image;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->color('danger')
                ->outlined(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
