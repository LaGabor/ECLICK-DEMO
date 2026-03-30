<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\Concerns\MapsPromotionValidationErrorsToFilamentData;
use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPromotion extends EditRecord
{
    use MapsPromotionValidationErrorsToFilamentData;

    protected static string $resource = PromotionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (ValidationException $exception) {
            throw $this->wrapPromotionValidationExceptionForFilament($exception);
        }
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
