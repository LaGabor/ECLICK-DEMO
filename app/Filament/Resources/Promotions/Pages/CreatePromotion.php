<?php

namespace App\Filament\Resources\Promotions\Pages;

use App\Filament\Resources\Promotions\Concerns\MapsPromotionValidationErrorsToFilamentData;
use App\Filament\Resources\Promotions\PromotionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePromotion extends CreateRecord
{
    use MapsPromotionValidationErrorsToFilamentData;

    protected static string $resource = PromotionResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (ValidationException $exception) {
            throw $this->wrapPromotionValidationExceptionForFilament($exception);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
