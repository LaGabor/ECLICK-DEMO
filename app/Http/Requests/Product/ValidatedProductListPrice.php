<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Support\Validation\ProductListPriceRules;
use Illuminate\Foundation\Http\FormRequest;

final class ValidatedProductListPrice extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('price')) {
            $this->merge([
                'price' => ProductListPriceRules::normalizeTwoDecimalString($this->input('price')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'price' => ProductListPriceRules::rules(),
        ];
    }
}
