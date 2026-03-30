<?php

declare(strict_types=1);

namespace App\Filament\Resources\Promotions\Concerns;

use Illuminate\Validation\ValidationException;

/**
 * Filament promotion forms use statePath `data`; model validation errors use flat attribute names.
 * Remap so Livewire can attach messages under the correct form fields.
 */
trait MapsPromotionValidationErrorsToFilamentData
{
    protected function wrapPromotionValidationExceptionForFilament(ValidationException $exception): ValidationException
    {
        $prefixed = [];

        foreach ($exception->errors() as $key => $messages) {
            $prefixed['data.'.$key] = $messages;
        }

        return ValidationException::withMessages($prefixed);
    }
}
