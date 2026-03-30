<?php

declare(strict_types=1);

namespace App\Support\Validation;

/**
 * Hungarian numbers in strict form: +36 followed by exactly 9 digits (no spaces), e.g. +36300705352.
 */
final class HungarianInternationalPhoneRules
{
    /** Delimited pattern for Laravel's {@code regex:…} validation rule string. */
    public const string LARAVEL_REGEX = '/^\+36\d{9}$/';

    /**
     * @return list<string>
     */
    public static function requiredRules(): array
    {
        return ['required', 'string', 'regex:'.self::LARAVEL_REGEX];
    }
}
