<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Http\Requests\Product\ValidatedProductListPrice;
use App\Models\Product;

/**
 * List price validation and two-decimal persistence ({@see ValidatedProductListPrice}, Filament product form, {@see Product}).
 */
final class ProductListPriceRules
{
    public static function normalizeTwoDecimalString(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * @return list<string>
     */
    public static function rulesWithoutRequired(): array
    {
        return [
            'numeric',
            'min:0',
            'decimal:0,2',
        ];
    }

    public static function rules(): array
    {
        return array_merge(['required'], self::rulesWithoutRequired());
    }
}
